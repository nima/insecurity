#!/usr/bin/python
#. ******* coding:utf-8 AUTOHEADER START v1.3 *******
#. vim: fileencoding=utf-8 syntax=python sw=4 ts=4 et
#.
#. © 2007-2011 Nima Talebi <nima at autonomy dot net dot au>
#.                         <nt at securusglobal dot com dot au>
#.
#. $HeadURL::                                                                  $
#. $LastChangedBy::                                                            $
#. $LastChangedDate::                                                          $
#. $LastChangedRevision::                                                      $
#. $                                                                           $
#. $AutoHeaderSerial::20110315                                                 $
#.
#. This file is part of the Insecurity Suite.
#.
#.     Insecurity is free software: you can redistribute it and/or modify
#.     it under the terms of the GNU General Public License as published by
#.     the Free Software Foundation, either version 3 of the License, or
#.     (at your option) any later version.
#.
#.     Insecurity is distributed in the hope that it will be useful,
#.     but WITHOUT ANY WARRANTY; without even the implied warranty of
#.     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#.     GNU General Public License for more details.
#.
#.     You should have received a copy of the GNU General Public License
#.     along with Insecurity.  If not, see <http://www.gnu.org/licenses/>.
#.
#. THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED
#. WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
#. MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO
#. EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
#. INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
#. LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
#. PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
#. LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
#. OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
#. ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#.
#. ADAPTED M. STONE & T. PARKER DISCLAIMER: THIS SOFTWARE COULD RESULT IN INJURY
#. AND/OR DEATH, AND AS SUCH, IT SHOULD NOT BE BUILT, INSTALLED OR USED BY ANYONE.
#. ******* AUTOHEADER END v1.3 *******

from sys import stdout, stderr, exit

import urllib, urllib2
from urlparse import urlparse, urlunparse
from ntlm import HTTPNtlmAuthHandler
from cookielib import CookieJar

from lxml import etree
from lxml.etree import XMLSyntaxError

QAPI_VERSION = 0.3

#. TODO: Cheap means of checking if session has expired/connection is still up

#. QualysGuard API Servers:
REALMS = {
    'US' : 'qualysapi.qualys.com',
    'EU' : 'qualysapi.qualys.eu',
}

PERMS = [
    'Manager',
    'Unit Managers',
    'Scanner',
    'Reader',
    'Auditor',
]

#. DTDs
DTD = {
    'vuln'                  : 'https://%s/scan-1.dtd',
    'report'                : 'https://%s/scan-1.dtd',
    'asset'                 : 'https://%s/scan-1.dtd',
    'compliance'            : 'https://%s/scan-1.dtd',
    'scan'                  : 'https://%s/scan-1.dtd',
    'scan:action=list'      : 'https://%s/api/2.0/fo/scan/scan_list_output.dtd',
    'scan:action=list'      : 'https://%s/api/2.0/fo/scan/appliance_list_output.dtd',
    'scan:action=cancel'    : 'https://%s/api/2.0/fo/scan/scan_return.dtd',
    'scan:action=pause'     : 'https://%s/api/2.0/fo/scan/scan_return.dtd',
    'scan:action=resume'    : 'https://%s/api/2.0/fo/scan/scan_return.dtd',
    'report:action=list'    : 'https://%s/api/2.0/fo/report/report_list_output.dtd',
    'was'                   : 'https://%s/scan-1.dtd',
}

URI_DATA = {
    'application' : 'api',
    'version'     : 2,
    'interface'   : 'fo',
}

class QAPIData:
    def __init__(self, data=dict()):
        self._data = data
        self._type = type(self._data)

    def __getitem__(self, key):
        return self._data[key]

    def __setitem__(self, key, value):
        self._data[key] = value

    def __str__(self):
        return self._pp(self._data)

    def __iter__(self):
        return (_ for _ in self._data)

    def __next__(self):
        yield self.next()
    def next(self):
        for item in self._data:
            yield item
    def get(self, key, default=None):
        return self._data.get(key, default)

    def keys(self):
        return self._data.keys()

    def values(self):
        return self._data.values()

    def xpath(self, path):
        return self._data.xpath(path)

    def _pp(self, data, indent=0):
        from lxml.etree import _Element
        pretty = list()
        if type(data) is dict:
            prefix = ''
            if indent > 0:
                prefix = '%s\\___' % (' '*(1+indent*3))

            for key in data:
                line = ['%s%s:' % (prefix, key)]
                if type(data[key]) is dict:
                    pretty.append(' '.join(line))
                    pretty.append(self._pp(data[key], indent+1))
                else:
                    line.append(' %s' % data[key])
                    pretty.append(' '.join(line))
            return '\n'.join(pretty)
        elif type(data) is _Element:
            from lxml.etree import tostring
            return tostring(data, pretty_print=True)
        else:
            return str(data)

class QAPIError(Exception):
    '''Base class for local exceptions.'''
    def __init__(self, fmt, *args):
        if args:
            self.message = fmt % args
        else:
            self.message = fmt
        Exception.__init__(self, self.message)

    #def __del__(self):
    #    stderr.write("<Errors: %s>\n" % self.message)


class QAPIHTTPError(QAPIError):
    def __init__(self, e):
        xml = e.read()
        doc = etree.fromstring(xml)
        code = doc.xpath('/SIMPLE_RETURN/RESPONSE/CODE').pop().text
        reason = doc.xpath('/SIMPLE_RETURN/RESPONSE/TEXT').pop().text
        self.message = "QAPI HTTP error %s: %s - %s" % (code, e, reason)
        QAPIError.__init__(self, self.message)

class QAPIInteralError(QAPIError):
    def __init__(self, fmt, *args):
        QAPIError.__init__(self, fmt, *args)
        self.message = "QAPI Internal error: %s" % self.message

class QAPILocalValidationError(QAPIError):
    def __init__(self, fmt, *args):
        QAPIError.__init__(self, fmt, *args)
        self.message = "QAPI Validation Error: %s" % self.message

class QAPISessionTimeoutError(QAPIError):
    def __init__(self, fmt, *args):
        QAPIError.__init__(self, fmt, *args)
        self.message = "QAPI Session Timeout: %s" % self.message

class QAPIBadAPICallError(QAPIError):
    def __init__(self, fmt, *args):
        QAPIError.__init__(self, fmt, *args)
        self.message = "QAPI Bad API Call: %s" % self.message

class QualysGuard:
    def __init__(self, username, realm_id, config, debug=False):
        self._version = QAPI_VERSION
        self._cookiejar = CookieJar()
        self._username = username
        self._realm_id = realm_id
        self._profile = '@'.join((username, realm_id))
        self._realm = REALMS[self._realm_id]
        self._proxy = None
        self._templates = None
        self._debug = debug
        self._config = None #. User configuration file for scripted mode
        self._connected = False
        self._username = 'nobody'
        self._cFM = None
        try:
            from ConfigFileManager import ConfigFileManager, InternalConfigError
            try:
                self._config = ConfigFileManager(config)
                self._qapi_ini = self._config.option('qapi', 'ini')
                self._cFM = ConfigFileManager(self._qapi_ini)
            except InternalConfigError as e:
                raise Exception("Sorry, %s" % e)
        except ImportError as e:
            raise Exception("Sorry, %s" % e)

        urllib2.install_opener(self._opener())

    def __del__(self):
        self.logout()

    def __repr__(self):
        return "<QualysGuard conn:%s user:%s>" % (
            self._connected,
            self._username
        )

    def _opener(self):
        ntlm = False
        proxy = False
        if 'proxy' in self._config.sections():
            try:
                self._proxy = {
                    'proto'   : self._config.option('proxy', 'proto'),
                    'host'    : self._config.option('proxy', 'host'),
                    'port'    : int(self._config.option('proxy', 'port', 8080)),
                    'user'    : self._config.option('proxy', 'user', None),
                    'pass'    : self._config.option('proxy', 'pass', None),
                }
                if self._proxy['user'] is not None:
                    ntlm = self._proxy['user'].count('\\') == 1

                if self._proxy['host'] and self._proxy['port']:
                    proxy = True

            except InternalConfigError as e:
                pass

        proxy_handler_dict = {}
        if proxy:
            proxy_handler_uri = None
            if self._proxy['user']:
                if self._proxy['pass'] is not None:
                    proxy_handler_uri = "%s://%s:%s@%s:%d" % (
                        "http",
                        self._proxy['user'],
                        self._proxy['pass'],
                        self._proxy['host'],
                        self._proxy['port']
                    )
                else:
                    proxy_handler_uri = "%s://%s@%s:%d" % (
                        "http",
                        self._proxy['user'],
                        self._proxy['host'],
                        self._proxy['port']
                    )
            else:
                proxy_handler_uri = "%s://%s:%d" % (
                    "http",
                    self._proxy['host'],
                    self._proxy['port']
                )

            proxy_handler_dict[self._proxy['proto']] = proxy_handler_uri

        self._pwmgr = urllib2.HTTPPasswordMgrWithDefaultRealm()
        cookiemonster = urllib2.HTTPCookieProcessor(self._cookiejar)
        auth_NTLM   = HTTPNtlmAuthHandler.HTTPNtlmAuthHandler(self._pwmgr)
        auth_basic  = urllib2.HTTPBasicAuthHandler(self._pwmgr)
        auth_digest = urllib2.HTTPDigestAuthHandler(self._pwmgr)
        auth_proxy  = urllib2.ProxyHandler(proxy_handler_dict)

        openers = [
            auth_basic,
            auth_digest,
            auth_proxy,
            cookiemonster,
        ]
        if ntlm:
            openers.append(auth_NTLM)

        opener = urllib2.build_opener(*openers)
        return opener

    def _cookies(self):
        return self._cookiejar._cookies[self._realm]['/api']['QualysSession']

    def _validate(self, resource, **parameters):
        valid = False
        action = parameters.get('action', None)
        if action is not None:
            valid = resource in self._cFM.option('resource', 'valid')
        else:
            raise NameError(
                "Missing mandarory option `action'"
            )

        msg = None
        if valid:
            for param in self._cFM.section('%s-options' % resource):
                meaning = self._cFM.option(
                    '%s-options' % resource,
                    param
                )
                if meaning.has_key(action):
                    if meaning[action] is 1 and param not in parameters.keys():
                        raise NameError(
                            "Missing mandarory option `%s' for `%s'" % (
                                param, resource
                            )
                        )
                else:
                    pass

            validators = self._cFM.section('%s-validators' % resource)
            for param in parameters:
                if param in self._cFM.options('%s-options' % resource):
                    validator = validators.get(param, None)
                    if validator is not None:
                        if type(validator) is list:
                            valid = parameters[param] in validator
                            msg = "`%s=%s' (required) not in %s" % (
                                param,
                                parameters[param],
                                validator
                            )
                        else:
                            validator_fn = eval(validator)
                            valid = validator_fn(parameters[param])
                            msg = "`%s=%s' failed on %s" % (
                                param,
                                validator,
                                parameters[param]
                            )

                        if not valid:
                            raise QAPILocalValidationError(
                                "`%s:%s=%s': %s",
                                resource, param, parameters[param], msg
                            )
                else:
                    raise NameError(
                        "Invalid option `%s' for `%s'" % (
                            param, resource
                        )
                    )
        else:
            raise NameError(
                "Invalid resource `%s'" % resource
            )

        return valid

    def _request(self, resource, application, data=dict()):
        '''
        application:
            api: QualysGuard API v1
            msp: QualysGuard API v2
        '''
        if application == 'api':
            assert self._validate(resource, **data) is True
        elif application != 'msp':
            raise QAPIInteralError(
                "%s not in `api' or `msp'", application,
            )

        uri = None
        if application == 'api':
            uri = 'https://%s:443/%s/%0.1f/%s/%s/' % (
                self._realm,
                application, URI_DATA['version'], 'fo',
                resource
            )
        elif application == 'msp':
            uri = 'https://%s:443/%s/%s.php' % (
                self._realm,
                application,
                resource
            )

        if self._debug:
            print "\n#. %-8s: %s (%s)" % ('URI', uri, application)
            if application is 'api':
                print "#. %-8s: %s" % ('POST', urllib.urlencode(data))

        encdata = urllib.urlencode(data)
        headers = { 'X-Requested-With' : 'qapi-v%s' % QAPI_VERSION }
        response = None
        error = None
        try:
            request = urllib2.Request(uri, encdata, headers)
            if self._debug:
                print "#. REQUEST HEADERS"
                print request.headers
                print "#. REQUEST DATA"
                print request.data

            response = urllib2.urlopen(request)
            if self._debug:
                print "#. RESPONSE HEADERS"
                print response.msg
                print response.headers
                #print "#. RESPONSE DATA"
                #print '\n'.join(response.readlines())
                #print dir(response.fp)
                #print dir(response)

        except urllib2.HTTPError, e:
            if not self._debug: raise e #. TODO: Cleanup - Not XML, so can't use QAPIHTTPError(e)
            else: error = e
        except urllib2.URLError, e:
            #if not self._debug: raise QAPISessionTimeoutError(e)
            #else:
            error = e
            if hasattr(e, 'reason'):
                print 'We failed to reach a server.'
                print 'Reason: ', e.reason
            elif hasattr(e, 'code'):
                print 'The server couldn\'t fulfill the request.'
                print 'Error code: ', e.code
        else:
            assert response is not None

        if error is not None:
            print "Exception: %s; see /tmp/qapi-debug* for details" % e
            fh = open("/tmp/qapi-debug-data.html", 'w')
            fh.write(data)
            fh.close()

        return response

    def is_proxied(self):
        return self._proxy is not None

    def debug(self):
        return self._debug

    def set_debug(self, switch):
        if switch in (0, 1, True, False):
            self._debug = switch and True or False
        else:
            raise QAPIBadAPICallError(
                "set_debug() only takes 0, 1, False, or True, not %s" % (
                    str(switch)
                )
            )

    def connected(self):
        return self._connected

    def version(self):
        return self._version

    def sessionid(self):
        return self._cookies().value

    def logout(self):
        '''
        <SIMPLE_RETURN>
          <RESPONSE>
            <DATETIME>2011-03-07T04:59:14Z</DATETIME>
            <TEXT>Logged out</TEXT>
          </RESPONSE>
        </SIMPLE_RETURN>
        '''

        success = False
        post = { 'action' : 'logout' }
        response = self._request('session', 'api', post)
        #xml = response.read()
        #doc = etree.fromstring(xml) #. SegFaults!
        success = True
        self._connected = False
        self._username = 'nobody'

        return success

    def login(self, username, password):
        '''
        <SIMPLE_RETURN>
          <RESPONSE>
            <DATETIME>2011-03-07T05:07:13Z</DATETIME>
            <TEXT>Logged in</TEXT>
          </RESPONSE>
        </SIMPLE_RETURN>
        '''

        success = False
        if not self._connected:
            self._username = username

            #. 1/2 Session-Based Auth
            post = {
                'action'   : 'login',
                'username' : username,
                'password' : password,
            }
            response = self._request('session', 'api', post)
            xml = response.read()
            doc = etree.fromstring(xml)

            r = doc.xpath('/SIMPLE_RETURN/RESPONSE/TEXT')
            assert len(r) == 1
            if r[0].text == "Logged in":
                self._connected = True
                success = True
            else:
                print "### TEXT", r[0].text
                r = doc.xpath('/SIMPLE_RETURN/RESPONSE/CODE')
                print "### CODE", r[0].text

            #. 2/2 Basic HTTP Auth
            self._pwmgr.add_password(
                None,
                'https://%s:443/' % self._realm,
                username,
                password
            )

        return success

    def scan(self, **post):
        '''
        Returns all scans launched by the user withing the last 30 days
        '''

        response = self._request('scan', 'api', post)
        xml = response.read()
        doc = etree.fromstring(xml)
        return QAPIData(doc)

    def knowledgebase(self, cvss=1, pcif=1, patchable=1):
        post = {
            'show_cvss_submetrics':cvss,
            'show_pci_flag':pcif,
            'is_patchable':patchable,
        }
        response = self._request('knowledgebase_download', 'msp', post)
        xml = response.read()
        doc = etree.fromstring(xml)
        return QAPIData(doc)

    def templates(self):
        response = self._request('report_template_list', 'msp')
        xml = response.read()
        doc = etree.fromstring(xml)
        return QAPIData(doc)

    def report(self, **post):
        resource = 'report'
        if post.has_key('sub'):
            resource = '%s/%s' % (report, post.pop('sub'))

        response = self._request(resource, 'api', post)
        rawdata = response.read()
        data = rawdata
        try:
            doc = etree.fromstring(rawdata)
            data = doc
        except XMLSyntaxError:
            pass

        return QAPIData(data)

    def asset(self, sub, **post):
        response = self._request('asset/%s' % sub, 'api', post)
        xml = response.read()
        doc = etree.fromstring(xml)
        return QAPIData(doc)

    def appliance(self, **post):
        response = self._request('appliance', 'api', post)
        xml = response.read()
        doc = etree.fromstring(xml)
        return QAPIData(doc)

    def getpass(self):
        passwd = None
        try:
            passwd = self._config.option(self._profile, 'passwd')
        except KeyError, e:
            pass #. password not found

        return passwd
