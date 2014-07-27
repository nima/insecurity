"""
>>> import const
>>> const.MYCONST = 3
"""

class _CONSTANT(object):
    """The CONSTANT class allows for constants in Python"""

    class ConstError(TypeError):
        """Genetic CONSTANT exception class"""
        pass

    def __setattr__(self, name, value):
        if self.__dict__.has_key(name):
            raise self.ConstError, "Can't rebind CONSTANT %s" % name
        self.__dict__[name] = value

    def __delattr__(self, name):
        if name in self.__dict__:
            raise self.ConstError, "Can't remove CONSTANT %s" % name
        raise NameError, name

import sys
sys.modules[__name__] = _CONSTANT()
