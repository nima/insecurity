import sys

class Progress:
  def __init__(self, total, weight, sTitle="Loading", eTitle="Done", width=80):
    self.char    = "."
    self.item    = 0.0
    self.total   = total
    self.percent = 0
    self.sTitle  = sTitle
    self.eTitle  = eTitle
    self.width   = width
    
    #. Continuation of another progress (dont print title again)
    if(self.sTitle==None):
      self.barWidth = (weight/100.0) * (self.width - len(self.eTitle))
    else:
      self.barWidth = (weight/100.0) * (self.width - len(self.sTitle) - len(self.eTitle))
      sys.stdout.write(self.sTitle)

    #. We know the weight, but not the total, so just fill in the dots
    if(self.total==0):
      for i in range(0,int(self.barWidth)):
        sys.stdout.write(self.char)
        sys.stdout.flush()
      self.percent += self.barWidth

  def progress(self):
    self.item += 1.0
    if(self.total!=0):
      newPercent = int(100*self.item/self.total)
      if(newPercent > self.percent):
        for points in range(int(self.percent*self.barWidth/100), int(newPercent*self.barWidth/100)):
          sys.stdout.write(self.char)
        self.percent = newPercent
      if(self.percent == 100 and self.eTitle):
        print("done")
    else:
      if(self.percent == 100 and self.eTitle):
        print("failed")
    sys.stdout.flush()
    return(0)
