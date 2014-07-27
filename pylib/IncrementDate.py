import datetime

from DateUtils import DateUtils

class IncrementDate:
  def __init__(self, date, no, by):
    self.date=date
    self.by=by
    self.no=no
    self.dayOfMonth=date.day

  def increment(self):
    if(self.by == "d"):
      self.byDays(1*self.no)
    elif(self.by == "w"):
      self.byWeeks(1*self.no)
    elif(self.by == "f"):
      self.byWeeks(2*self.no)
    elif(self.by == "m"):
      self.byMonths(1*self.no)
    elif(self.by == "q"):
      self.byMonths(3*self.no)
    elif(self.by == "h"):
      self.byMonths(6*self.no)
    elif(self.by == "y"):
      self.byYears(1*self.no)
    else:
      u.e("Invalid transaction frequency identifier: "+str(self.by),"f",9)
      
  def getDate(self):
    if(isinstance(self.date,datetime.date)):
      r=self.date
    else:
      r="DATE NOT SET" 
    return(r)

  def byDays(self, n):
    self.date += datetime.timedelta(n)

  def byWeeks(self, n):
    self.date += datetime.timedelta(n*7)

  def byMonths(self, n):
    if(n!=0):
      for d in range(min(0, n), max(0, n)):
        happy=0
        while(not happy):
          try:
            if(self.date.month == 12 and n > 0):
              self.date=self.date.replace(year=self.date.year+1,month=1)
            elif(self.date.month == 1  and n < 0):
              self.date=self.date.replace(year=self.date.year-1,month=12)
            else:  
              self.date=self.date.replace(month=self.date.month+(n/abs(n)))
            #. Compare to original days in month, and increment back up... 
            stop=0
            while((self.date.day < self.dayOfMonth) and (stop==0)):
              if((self.date + datetime.timedelta(1)).day > self.date.day):
                self.date += datetime.timedelta(1)
              else:
                stop=1
            happy=1
          except ValueError:
            #. Reduce days in month until it is correct for that month
            i=1
            try:
              self.date=self.date.replace(day=self.date.day-i)
            except:
              i += 1
            #. Incrementing a 31-day month to a 30-day month issue...
            #. Problem is that if month 1 is 31-day, month 2 is 28 day,
            #. eg Feb-->Mar, then the following months will be calced at 28 days
  
  def byYears(self, n):
    self.date=self.date.replace(year=self.date.year+n)
