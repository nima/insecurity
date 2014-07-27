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

def dispatch(s, c): return getattr(c)(s)
def ascii(s, i): return "\033[%d;1m%s\033[0m"%(30+i, str(s))
def black(s): return "\033[30;1m%s\033[0m"%(str(s))
def red(s): return "\033[31;1m%s\033[0m"%(str(s))
def green(s): return "\033[32;1m%s\033[0m"%(str(s))
def yellow(s): return "\033[33;1m%s\033[0m"%(str(s))
def blue(s): return "\033[34;1m%s\033[0m"%(str(s))
def magenta(s): return "\033[35;1m%s\033[0m"%(str(s))
def cyan(s): return "\033[36;1m%s\033[0m"%(str(s))
def white(s): return "\033[37;1m%s\033[0m"%(str(s))

DISPATCH = {
  1 : red,
  2 : green,
  3 : yellow,
  4 : blue,
  5 : magenta,
  6 : cyan,
  7 : white,
}
