#!/usr/bin/python -u

from BaseConverter import h2b, b2d, b2ip
#import sys, time, re
#from getopt import getopt
#import subprocess
from ANSI import *

class Host:
  def __init__(self, ip_addr, fn):
    self._ip_addr = ip_addr
    self._str_fn = fn

  def __str__(self):
    return self._str_fn(self._ip_addr)

  def __repr__(self):
    return self._str_fn(self._ip_addr)

  def getL3Addr(self):
    return self._ip_addr

class Network:
  def __init__(self, hosts):
    self._hosts = hosts

#. Structure: (<byte>, <start-bit>, <bit-length>)
class IP:
  PRECEDENCE = {
    "000" : "Routine",
    "001" : "Priority",
    "010" : "Immediate",
    "011" : "Flash",
    "100" : "Flash Override",
    "101" : "CRITIC/ECP",
    "110" : "Internetwork Control",
    "111" : "Network Control",
  }

  ARCH = {
    "ver"      : (0x00, 0x00, 0x04, b2d),
    "ihl"      : (0x00, 0x04, 0x04, b2d),
    "tos"      : (0x01, 0x00, 0x08, b2d),
    "len"      : (0x02, 0x00, 0x10, b2d),
    "id"       : (0x04, 0x00, 0x10, b2d),
    "flags"    : (0x06, 0x00, 0x04, b2d),
    "fragos"   : (0x06, 0x04, 0x0c, str),
    "ttl"      : (0x08, 0x00, 0x08, b2d),
    "proto"    : (0x09, 0x00, 0x08, b2d),
    "checksum" : (0x0a, 0x00, 0x10, str),
    "src"      : (0x0c, 0x00, 0x20, b2ip),
    "dst"      : (0x10, 0x00, 0x20, b2ip),
  }

  def __init__(self, packet, encapsulate=None):
    self._packet = {
      "hex": packet,
      "bin": h2b(packet),
    }
    self._encapsulated = encapsulate

  def _get_subpacket(self, name):
    byte, bit, length, fn = IP.ARCH[name]
    start = byte*8+bit
    end = start+length
    return fn(self._packet["bin"][start:end])

  def __str__(self):
    i = 7
    pktstr = []
    src = self._get_subpacket("src")
    dst = self._get_subpacket("dst")
    hosts = [src, dst]
    #hosts.sort()
    fn1 = src < dst and green or yellow
    fn2 = src > dst and green or yellow
    pktstr.append("<%s %s %s>"%(fn1("%s"%src), "=>", fn2("%s"%dst)))
    if self._encapsulated:
      pktstr.append("{{{ %s }}}"%repr(self._encapsulated))
    return " ".join(pktstr)

  def get_src(self):
    return self._get_subpacket("src")

  def get_dst(self):
    return self._get_subpacket("dst")

  def get_encapsulated(self):
    return self._encapsulated
