import sys

SERIAL="20050904"

class Colourise:
  def __init__(self, format=None, style=None):
    #. If no stle is defined, here are the defaults... 
    if(not style):
      if(format=="ansi"):
        style=["+b"]
      else:
        style=[]

    if(format == "ansi"):
      self.format = "ansi"
      self.style=style
    elif(format == "html"):
      self.format = "html"
      self.style=style
    elif(format == "text"):
      self.format = "text"
      self.style=style
    else:
      raise ValueError("Unknown format: `%s'" % str(format))

  def getHead(self):
    if(self.format == "html"):
      h=(
'''
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
  <head>
  </head>
  <body bgcolor="#111">
    <pre>
''')
    else:
      h=""
    return(h)  
  
  def getTail(self):
    if(self.format == "html"):
      t=(
'''
    </pre>
  </body>
</html>
''')
    else:
      t=""
    return(t)
    
  def getLnStr(self, s, fg="default", bg="default", style=[]):
    return(self.getStr(s, fg, bg, style)+"\n")

  def getStr(self, s, fg="default", bg="default", style=[]):
    if(self.format == "ansi"):
      #. Initialize...
      sTA=["\033["]
      
      #. Bold, Italics etc... 
      if(len(style) == 0):
        style=self.style

      for i in style:
        if(i == "+b"):
          sTA.append(self.setBold())
        elif(i == "+i"):
          sTA.append(self.setItalic())
        elif(i == "-b"):
          sTA.append(self.setBold(switch=0))
        elif(i == "-i"):
          sTA.append(self.setItalic(switch=0))
        else:
          print("Only support +-[b]old and +-[i]talic, not `"+i+"'")

      #. Colour
      sTA.extend(self.setColour(fg, bg))
      
      #. Compose String...
      sT = sTA[0];
      for i in range(1,len(sTA)):
        if(i != (len(sTA)-1)):
          sT += sTA[i]+";"
        else:
          sT += sTA[i]+"m"
      string = sT+str(s)+self.reset()
    
    elif(self.format == "html"):
      if(fg != "default"):
        string='<font style="color:'+fg+'">'+s+"</font>"
      else:  
        string=s

    elif(self.format == "text"):
      string=s

    return(string)
  
  def reset(self):
    if(self.format == "ansi"):
      sT = "\033[0m"
    return sT

  def setBold(self, switch=1): 
    if(self.format == "ansi"):
      if(switch==0):
        sT = "22"
      elif(switch==1):
        sT = "1"
    elif(self.format == "html"):
      pass
    return sT

  def setItalic(self, switch=1):
    if(self.format == "ansi"):
      if(switch==0):
        sT = "23"
      elif(switch==1):
        sT = "3"
    return sT

  def setUnderlne(self, switch=1):
    if(self.format == "ansi"):
      if(switch==0):
        sT = "24"
      elif(switch==1):
        sT = "4"
    return sT
  
  def setInverse(self, switch=1):
    if(self.format == "ansi"):
      if(switch==0):
        sT = "27"
      elif(switch==1):
        sT = "7"
    return sT

  def setStrikethrough(self, switch=1):
    if(self.format == "ansi"):
      if(switch==0):
        sT = "29"
      elif(switch==1):
        sT = "9"
    return sT
  
  def setColour(self, fg, bg):
    if(self.format == "ansi"):
      sT=[]
      if(fg == "black"):
        sT.append("30")
      elif(fg == "red"):
        sT.append("31")
      elif(fg == "green"):
        sT.append("32")
      elif(fg == "yellow"):
        sT.append("33")
      elif(fg == "blue"):
        sT.append("34")
      elif(fg == "magenta"):
        sT.append("35")
      elif(fg == "cyan"):
        sT.append("36")
      elif(fg == "white"):
        sT.append("37")
      elif(fg == "default"):
        sT.append("39")

      if(bg == "black"):
        sT.append("40")
      elif(bg == "red"):
        sT.append("41")
      elif(bg == "green"):
        sT.append("42")
      elif(bg == "yellow"):
        sT.append("43")
      elif(bg == "blue"):
        sT.append("44")
      elif(bg == "magenta"):
        sT.append("45")
      elif(bg == "cyan"):
        sT.append("46")
      elif(bg == "white"):
        sT.append("47")
      elif(bg == "default"):
        sT.append("49")

    return(sT)  
