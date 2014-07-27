"""Python utility to print MD5 checksums of argument files"""
import sys, md5

class MD5Sum:

  def __init__(self):
    self.__BLOCKSIZE = 1024*1024
  
  def getMD5Sum4(self,file):
    sum = md5.new()
    f = open(file, "rb")
    while 1:
      block = f.read(self.__BLOCKSIZE)
      if not block:
        break
      sum.update(block)
    f.close()
    mD5Sum=self.__hexify(sum.digest())
    return(mD5Sum)

  def __hexify(self, s):
    return ("%02x"*len(s)) % tuple(map(ord, s))

