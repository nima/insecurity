#!/usr/bin/python -u

from BaseConverter import h2b, b2d
#import sys, time, re
#from getopt import getopt
#import subprocess
from ANSI import *

#. Structure: (<byte>, <start-bit>, <bit-length>)
class TCP:
  OCT13 = [
    white('C'),    #. 7
    white('E'),    #. 6
    magenta('U'),  #. 5
    cyan('A'),     #. 4
    blue('P'),     #. 3
    red('R'),      #. 2
    yellow('S'),   #. 1
    red('F'),      #. 0
  ]
  ARCH = {
    "sport"    : (0x00, 0x00, 0x10, b2d),
    "dport"    : (0x02, 0x00, 0x10, b2d),
    "seq_no"   : (0x04, 0x00, 0x20, b2d),
    "ack_no"   : (0x08, 0x00, 0x20, b2d),
    "hsize"    : (0x0C, 0x00, 0x04, b2d), #. The number of 32-bit chunks which equates to the header size
    "reserved" : (0x0C, 0x08, 0x04, str),
      "cwr"    : (0x0D, 0x00, 0x01, b2d),
      "ece"    : (0x0D, 0x01, 0x01, b2d),
      "urg"    : (0x0D, 0x02, 0x01, b2d),
      "ack"    : (0x0D, 0x03, 0x01, b2d),
      "psh"    : (0x0D, 0x04, 0x01, b2d),
      "rst"    : (0x0D, 0x05, 0x01, b2d),
      "syn"    : (0x0D, 0x06, 0x01, b2d),
      "fin"    : (0x0D, 0x07, 0x01, b2d),
    "wsize"    : (0x0E, 0x00, 0x10, b2d),
    "checksum" : (0x10, 0x00, 0x10, str),
    "urgent"   : (0x12, 0x00, 0x10, b2d),
    "options"  : (0x12, 0x00, 0x10, str),
  }
  def __init__(self, packet):
    self._packet = {
      "hex": packet,
      "bin": h2b(packet),
    }
    self._sport = self._get_subpacket("sport")
    self._dport = self._get_subpacket("dport")

  def _get_subpacket(self, name):
    byte, bit, length, fn = TCP.ARCH[name]
    start = byte*8+bit
    end = start+length
    return fn(self._packet["bin"][start:end])

  def __repr__(self):
    i = 7
    ACK = self._get_subpacket("ack")
    pktstr = ['[']
    for _ in "fin", "syn", "rst", "psh", "ack", "urg", "ece", "cwr":
      pktstr.append(self._get_subpacket(_) == 1 and TCP.OCT13[i] or '_')
      i -= 1
    pktstr.append(']')
    pktstr = ["".join(pktstr)]

    urg = red(self._get_subpacket("urgent"))
    hsize = magenta(self._get_subpacket("hsize")*4) #. 32 / 8 = 4
    wsize = blue("%5d"%(self._get_subpacket("wsize")/8))

    fn1 = self._sport < self._dport and green or yellow
    fn2 = self._sport > self._dport and green or yellow

    pktstr.append("%s:%s"%(fn1("%5d"%self._sport), fn2("%5d"%self._dport)))

    _ = ACK and fn1 or white
    ano = _("%10d"%self._get_subpacket("ack_no"))
    sno = fn2("%10d"%self._get_subpacket("seq_no"))

    pktstr.append("[ACK:%s, SEQ:%s]"%(ano, sno))
    pktstr.append("[HSIZE:%sb]"%(hsize))
    pktstr.append("[WSIZE:%sb]"%(wsize))
    pktstr.append("[URG:%s]"%(urg))
    return " ".join(pktstr)

  def get_wsize(self):
    return self._get_subpacket("wsize")

  def get_seq(self):
    return self._get_subpacket("seq_no")

  def get_ack(self):
    return self._get_subpacket("ack_no")

  def get_sport(self):
    return self._sport

  def get_dport(self):
    return self._dport
