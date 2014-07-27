#!/usr/bin/python
import dl
import sys

result="chadFunk"
print >> sys.stdout, result
a=dl.open("libjunk.so")
result=a.call('fun1','1.12','1.22')
a.close()
print >> sys.stdout, result
