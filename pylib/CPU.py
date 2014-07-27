"""Utilize Multiple Processors""" 
__revision__ = "0.5"

import sys, subprocess

class CPU:
  """CPU"""

  def __init__(self, iD):
    """Constructor"""
    self.__status      = "idle"
    self.iD            = iD
    
    #. [ FileName, SubProcessObject ]
    self.job           = [None, None]
    
    #. As jobs complete, add them to this list
    self.processedJobs = []

    #. SubProcess cerr and cout
    self.stderr        = subprocess.PIPE
    self.stdout        = subprocess.PIPE
  
  def update(self):
    """Update the CPU State"""

    stateChange = False
    if(self.__status != "retired"):
      if(self.job[1]):
        poll = self.job[1].poll()
        #. None means it is busy...
        if(poll == None):
          #. Has it _just_ become busy?
          if(self.isReady()):
            self.__status = "init"
            stateChange = True
          #. Or has it been busy for a while now?  
          elif(self.__status == "init"):
            self.__status = "busy"
            stateChange = True
          elif(self.__status == "busy"):
            0
          else:
            print("HELP "+self.__status)
        #. 0 means a job was completed successfully
        elif(poll == 0):
          self.__status = "complete"
          self.processedJobs.append(self.job)
          stateChange = True
        #. Anything else and Huston, we have a problem...
        else:
          self.__status = "fail"
          stateChange = True
          print(self.job[1].poll())
      else:
        if(self.__status != "idle"):
          self.__status = "idle"
          stateChange = True
    return(stateChange)    

  def printSummary(self):
    """Display CPU State"""
    summary = None
    cpu = ("cpu-"+str(self.iD))
    job = ("job-"+str(self.job[1].pid))
    if(self.__status=="complete"): 
      summary = (cpu+" completed "+job)
    elif(self.__status=="fail"): 
      summary = (cpu+" failed "+job+" in a failed state")
    elif(self.__status=="init"): 
      summary = (cpu+" initialized "+job)
    elif(self.__status=="busy"): 
      summary = (cpu+" is busy with "+job)
    elif(self.__status=="idle"): 
      summary = (cpu+" is idle.")
    elif(self.__status=="retired"): 
      summary = (cpu+" retired.")
    else:
      summary = (cpu+" is in unkown state `"+self.__status+"'")

    print(self.job[0]+" ("+summary+")")

    if(self.__status=="fail"):  
      sys.exit(1)

  def process(self, jobTitle, jobCommandString):
    """Process Job"""
    self.job = [jobTitle, subprocess.Popen(jobCommandString.split(), stderr=self.stderr)]

  def isReady(self):  
    """Is Processor Ready"""
    return(self.__status == "complete" or self.__status == "idle" or self.__status == "retired")
    
  def isBusy(self):  
    """Is CPU Busy"""
    return(self.__status == "busy" or self.__status == "init")

  def isIdle(self):  
    """Is CPU Idle"""
    return(self.__status == "idle")

  def isComplete(self):  
    """Is CPU Complete (last job)"""
    return(self.__status == "complete")
    
  def isFail(self):  
    """Has CPU Failed (last job)"""
    return(self.__status == "fail")
    
  def isRetired(self):  
    """Has CPU Complete last and final job"""
    return(self.__status == "retired")

  def getStatus(self):
    """Get current state of CPU"""
    return(self.__status)

  def setRetired(self):
    """Set CPU Retired"""
    self.__status = "retired"
