''' any class inheriting from this class will only be able to change a data
    member when it is equal to None. After that, the change will only succeed
    if the new value is of the same type as the last. In addition, new type may
    not be added'''

def fixset(wsa):
  def __setattr__(self, name, value):
    if hasattr(self, name):
      #. Atribute has been defined prior to __init__()
      if isinstance(getattr(self, name), type): 
        #. Uninitialized (Type == Type)
        fixedType = getattr(self, name)
      else:
        #. Initialized
        fixedType = type(getattr(self, name))
      newType = type(value)
      if isinstance(value, fixedType): 
        #. New value has same type as last value
        wsa(self, name, value)
      else:
        raise TypeError("invalid object type for %r; expected %r, got %r" % (name, fixedType, newType))  
    else:    
      raise AttributeError("can't add attribute %r to %s" % (name, self))
  return __setattr__

class FixAttr(object):
  __setattr__ = fixset(object.__setattr__)

  class __metaclass__(type):
    __setattr__ = fixset(type.__setattr__)
