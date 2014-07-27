from configobj import ConfigObj, Section
from os import path

from sys import stderr
class BaseError(Exception):
    '''Base class for local exceptions.'''

class InternalError(BaseError):
    '''
    Raised only if the code is broken

    Should never ever be the case in a production environment.
    '''

class InternalConfigError(BaseError):
    '''
    Raised only if the sarface config file is missing or otherwise
    unreadable.

    Should never ever be the case in a production environment.
    '''

def decrapify(data):
    if type(data) is list:
        if False not in [item.count(':') == 1 for item in data]:
            decrap = dict(
                zip(
                    [decrapify(i.split(':')[0]) for i in data],
                    [decrapify(i.split(':')[1]) for i in data]
                )
            )
            return decrap
        else:
            return [decrapify(item) for item in data]
    elif type(data) is Section:
        return data
    elif type(data) is str:
        if data[0] != '$':
            return data
        else:
            data = data[1:]
            if data not in ("None", "False", "True"):
                try:
                    return int(data)
                except(TypeError, ValueError):
                    try:
                        return float(data)
                    except(TypeError, ValueError):
                        return data
            else:
                if data == "None":
                    return None
                elif data == "False":
                    return False
                elif data == "True":
                    return True

class ConfigFileManager:
    '''
    The abstraction class between the static data, and the code.  While at the
    moment, it merely passes calls through to the underlying configobj
    instance, in future it may in future do so via other means.
    '''

    def __init__(self, base, name=None):
        if name is not None:
            self._name = name
            self._path = path.join(base, "%s.db" % name)
        else:
            self._name = path.basename(base)
            self._path = base

        try:
            assert path.exists(self._path)
        except AssertionError:
            raise InternalConfigError(
                "User configuration file `%s' not found." % self._path
            )

        self._confspec = path.join(base, "%s.vf" % name)
        if path.exists(self._confspec):
            self._table = ConfigObj(
                self._path,
                configspec=self._confspec
            )
        else:
            self._table = ConfigObj(self._path)

    def __repr__(self):
        return repr(self._table)

    def __str__(self):
        return repr(self._table)

    def __getitem__(self, section):
        return self._table[section]

    def merge(self, table):
        '''
        Update this table with another, merging the differences from the latter
        over the first.
        '''
        self._table.merge(table._table)

    def sections(self):
        '''Return the sections in this ConfigFileManager (excluding scalars)'''
        return self._table.sections

    def section(self, section):
        '''Return the entire section content as a dict'''
        return self._table[section]

    def options(self, section):
        '''Return all sections (excludes scalars)'''
        return self._table[section].keys()

    def scalars(self, section):
        '''Return all scalars (excludes sections)'''
        return self._table[section].scalars

    def option(self, section, option, default='!'):
        '''Return a particular option from the given section'''

        '''TODO: This fn is damn messy, and generally crap logic'''

        r = decrapify(self._table[section].get(option, default))
        if type(r) is str:
            if r[0] == '@':
                return decrapify(self._table[section][r])
            else:
                return r
        elif r:
            return r
        elif default != '!':
            return default
        else:
            raise Exception
