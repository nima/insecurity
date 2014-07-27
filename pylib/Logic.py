class Logic:
  def __init__(self, node, parent=None):
    self.__level = 1
    self.__oF = {
      '(' : '(',
      ')' : ')',
      '&' : ' and ',
      '|' : ' or ',
    }
    self.__default_op = None

    if node in ('&', '|'):
      self.__type ="opranch"
      self.__name = node
      self.__children = [];
      self.__level = parent.getLevel() + 1
    elif type(node) == str:
      self.__name = node
      self.__type = "stub" #. Stub node
      self.__children = [] #None;
      self.__level = parent.getLevel() + 1
    elif type(node) == list:
      self.__name = "ROOT"
      self.__type = "root" #. Stub node
      self.__children = [] #None;
      nextLogic = self
      stack = []
      for c in node:
        if c == '(':
          stack.append(nextLogic.getName())
        elif c == ')':
          stack.pop()
          if nextLogic:
            nextLogic = nextLogic.getParent()
        elif c in ('&', '|'):
          if not self.__default_op:
            _c = ['&', '|']
            _c.remove(c)
            self.__default_op = _c[0]
          n = Logic(c, nextLogic)
          nextLogic.addChild(n)
          nextLogic = n
        else:
          nextLogic.addChild(Logic(c, nextLogic))

    if self.__default_op is None:
      self.__default_op = '&'

    self.__parent = parent

  def REPR(self):
    parentName = self.__parent and self.__parent.getName() or "R"
    s = " %s %s(%s) son of %s, %d children ]]\n"%(
      self.__level*"--",
      self.__name,
      self.__type,
      parentName,
      len(self.__children)
    )
    for c in self.__children:
      s += repr(c)
    return s

  def __repr__(self):
    if self.__type == "root":
      return (self.__oF[self.__default_op].join([repr(_) for _ in self.__children]))
    elif self.__type == "stub":
      return self.__name
    elif self.__type == "opranch":
      return "%s %s %s"%(self.__oF['('], self.__oF[self.__name].join([repr(_) for _ in self.__children]), self.__oF[')'])

  def addChild(self, n):
    self.__children.append(n)
    n.setParent(self)

  def setParent(self, n):
    assert self.__parent is None or self.__parent == n
    self.__parent = n

  def getParent(self):
    return self.__parent

  def getName(self):
    return self.__name

  def getLevel(self):
    return self.__level

