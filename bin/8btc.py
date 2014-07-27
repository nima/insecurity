#!/usr/bin/env python
import sys

for i in sys.argv[1:]:
    d = ((abs(int(i,16)) ^ 0xffffffff) + 1) & 0xffffffff
    print "%s --> %d [%#010x]" % (i, d, d)
