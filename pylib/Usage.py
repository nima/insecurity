import sys

class Usage:
  '''
  I use this for all my __programs, uniform and standard, saves time etc.
  '''

  def __init__(self, programName, usageBlob, version, serial):
    self.__program=programName
    self.__blob=usageBlob
    self.__version=version
    self.__serial=serial
    self.__revision=(self.__program+" v"+self.__version+" ("+self.__serial+")")
    self.version=(self.__program+" v"+self.__version+" ("+self.__serial+")")

  def getRevision():
    return(self.__revision)
  
  def e(self, message, type, code, fatal=False):
    u=Usage("Usage", '''
  fatal - if True, the __program will exit with given code
  
  message - an explanation of what went wrong

  type  - e is for error
        - w is for warning
        - f is for fatal
        - n is for no-type
''',"0.981","20050811")

  
    if(type == "e"):
      m="ERROR: "
    elif(type == "w"):
      m="WARNING: "
    elif(type == "f"):
      m="FATAL: "
      fatal=True
    elif(type == "n"):
      m=""
    else:
      u.e("Unknown: message:"+str(message)+", type:"+str(type)+", code:"+str(code)+", fatal:"+str(fatal), "f", 9, True)
    
    if(message):
      m+=message

    m+=" ("+str(code)+")"
    
    print(m)

    if(fatal):
      sys.exit(code)

  def s(self,message=None):
    '''Usage Syntax'''
    print("Usage: "+self.__program+" <options>")
    if(message is not None):
      print(" o "+message)
    print(self.__blob)
    print(self.__revision)
    exit=0
    if(message is not None):
      exit=1
    sys.exit(exit)

  def v(self,message=None):
    '''Usage Version'''
    print(self.version)
    sys.exit(0)
    
