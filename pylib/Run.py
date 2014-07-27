#!/usr/bin/env python

import os, select
from fcntl import fcntl, F_GETFL, F_SETFL
from popen2 import Popen3
from time import sleep

DEFAULT_TO = 0.005 #. For read() (and write() - write timeout not implemented yet)
BUFFER = 8192

VERSION = "0.9.0"

class Run:
  def __init__(self, cmd, strict=True):
    '''Default constructor'''
    #. Strict mode means that even if the exit code is 0, but stderr is not empty,
    self.__me = "run"
    self.__cmd = cmd
    self.__strict = strict
    self.__stdout = list()
    self.__stderr = list()
    self.__exit = None
    self.__name = os.path.basename(self.__cmd[0])
    self.__pid = os.getpid()
    self.__sig = None
    self.__closed = False
    self.__child = Popen3(cmd, True)
    self.__pid = self.__child.pid

    self.__io = { "w":dict(), "o":dict(), "e":dict() }

    self.__io["w"]["fD"] = self.__child.tochild
    self.__io["o"]["fD"] = self.__child.fromchild
    self.__io["e"]["fD"] = self.__child.childerr

    self.__io["o"]["spool"] = ""
    self.__io["e"]["spool"] = ""

    #. set this for interactive shells, such as `hpasmcli>'...
    self.__wait = DEFAULT_TO

    #. Used for read() and get_std*() functions, so if the last function called was
    #. read(), then get_stdout and get_stderr should not call read() again, but if it
    #. was write(), and read() was not called after this write, then call read().
    self.__last_call = None

    #. Non-interactive cmds, and interactive cmds that are completed will mean that
    #. hungup is set to True...
    self.__hungup = False
    self.__set_hup(1000) #. Wait upto a second for child process to crash (or not)

  def __del__(self):
    '''Default destructor'''
    if self.__closed is False:
      print "You did not close you Run() instance fool."

  def __repr__(self):
    return "<RunObject:[%s]>"%self.__cmd[0]

  def __set_blocking(self, fD, sw=True):
    '''Set a given file-descriptor to either block, or not block'''
    flags = fcntl(fD, F_GETFL)
    if sw:
      flags &= ~os.O_NDELAY
      flags &= ~os.O_NONBLOCK
    else:
      flags |= os.O_NDELAY
      flags |= os.O_NONBLOCK
    fcntl(fD, F_SETFL, flags)

  def __set_hup(self, timeout):
    '''Determine if the child process of this Run instance has hungup, or in a bad state, '''
    '''and then set the self.__hungup appropriately, (or raise an exception (to be later revised))'''
    poll = select.poll()
    poll.register(self.__child.childerr, select.POLLIN)
    ready = poll.poll(timeout)
    if len(ready):
      assert len(ready) == 1
      if ready[0][1] == select.POLLHUP:
        self.__hungup = True
      elif ready[0][1] in (select.POLLERR, select.POLLNVAL):
        raise ready

  def set_timeout(self, timeout):
    '''If the default timeout is not suitable, use this method to set it differently'''
    #. None or -ve means block.
    self.__wait = timeout

  def expect(self, xstring, wait="default"):
    '''Helper function to be used with interactive shells that are Run proxies to and from.'''
    '''Loop-call read() until expected line (prompt for example) is found as the last line of the data chunk.'''
    '''If no data is returned however from read, assume program has exited, and return []'''
    data = ""
    eof = False
    while not eof:
      self.read(wait)
      _ = self.get_stdout()
      if _:
        data += _
        if data.split("\n")[-1].strip() == xstring:
          eof = True
      else:
        data = None
        eof = True
    return data.split("\n")

  def read(self, wait="default"):
    ''' returns: nothing, instead fills stdout and stderr spools for later retrieval. '''
    ''' referenece: some ideas from http://codespeak.net/py/dist/apigen/source/process/cmdexec.py.html. '''

    #. NOTE: This wait does not reset the default wait timeout of this instance (self.__wait)

    fH = None
    event = 0
    read = 0;

    eofs = { "o":False, "e":False }

    if wait=="default":
      wait = self.__wait

    if wait is None or wait < 0:
      #. Would not make sense otherwise...

      #. Blocking is better used for when a non-interactive command is called.
      self.__set_blocking(self.__io["o"]["fD"], True)
      self.__set_blocking(self.__io["e"]["fD"], True)
      block = True

      open_fD = filter(None, [ (not eofs[_]) and self.__io[_]["fD"].fileno() for _ in eofs.keys() ])
      ready = select.select(open_fD, [], [], None) #. Block (timout set to `None')
      for io in eofs.keys():
        self.__io[io]["fD"].flush()
        if self.__io[io]["fD"].fileno() in ready[0]:
          chunk = True
          while chunk:
            try:
              chunk = self.__io[io]["fD"].read(BUFFER)
              if chunk:
                self.__io[io]["spool"] += chunk
            except IOError, e:
              if e.errno == 11:
                eofs[io] = True
              else:
                raise

    else:
      #. Non-Blocking is better used for when an interactive command is called.
      block = False
      self.__set_blocking(self.__io["o"]["fD"], block)
      self.__set_blocking(self.__io["e"]["fD"], block)
      i = 0

      #. Wait for input...
      open_fD = filter(None, [ (not eofs[_]) and self.__io[_]["fD"].fileno() for _ in eofs.keys() ])
      while False in eofs.values():
        i += 1

        ready = select.select(open_fD, [], [], wait)
        for io in eofs.keys():
          self.__io[io]["fD"].flush()
          if self.__io[io]["fD"].fileno() in ready[0]:
            while not eofs[io]:
              try:
                chunk = self.__io[io]["fD"].read(BUFFER)
                if chunk:
                  self.__io[io]["spool"] += chunk
                  read += len(chunk)
                  if len(chunk) < BUFFER:
                    ready = select.select(open_fD, [], [], DEFAULT_TO)
                    if self.__io[io]["fD"].fileno() not in ready[0]:
                      eofs[io] = True
                else:
                  eofs[io] = True

              except IOError, e:
                #. 11: Resource Temporarily Unavailable.... so we keep trying.
                if e.errno != 11:
                  raise e
          else:
            eofs[io] = True

    self.__last_call = "read()"
    return read

  def write(self, input, wait="default"):

    #. NOTE: This wait does not reset the default wait timeout of this instance (self.__wait)
    assert self.__closed is False
    assert self.__hungup is False
    assert type(input) in (list, str)

    if wait=="default":
      wait = self.__wait

    if wait is None or wait < 0:
      self.__set_blocking(self.__io["w"]["fD"], True)
    else:
      self.__set_blocking(self.__io["w"]["fD"], False)

    eofs = { "w":False }
    open_fD = filter(None, [ (not eofs[_]) and self.__io[_]["fD"].fileno() for _ in eofs.keys() ])
    #. TODO: Implement timeouts on write()...
    #ready = select.select(open_fD, [], [], TIMEOUT)

    wrote = 0
    www = None

    if type(input) is list:
      for line in input:
        os.write(self.__io["w"]["fD"].fileno(), "%s\n"%line.strip("\n"));
        wrote += len(line)+1
    elif type(input) is str:
      os.write(self.__io["w"]["fD"].fileno(), "%s\n"%input.strip("\n"));
      wrote += len(input)+1

    self.__io["w"]["fD"].flush()

    self.__last_call = "write()"

    return wrote


  def has_hungup(self):
    self.__set_hup(0)
    return self.__hungup

  def get_exit(self):
    return self.__exit;

  ###########################################################################
  #. FIXME: Run class in AIX on small-data generating commands doesn't return
  #.        a string, even on a blocking read, so this while loop hack has been
  #.        put in place as a temporary fix until the root cause has been
  #.        diagnosed...
  def get_stdout(self, AIX_HACK_PERSIST=False, placeholder=None):
    data = placeholder
    if(self.__last_call != "read()"):
      self.read(self.__wait)
    if AIX_HACK_PERSIST:
      while not self.__io["o"]["spool"]:
        sleep(0.1)
        self.read(None)
    if self.__io["o"]["spool"]:
      data = self.__io["o"]["spool"]
      self.__io["o"]["spool"] = ""
    self.__last_call = "get_stdout()"
    return data

  def get_stderr(self, AIX_HACK_PERSIST=False, placeholder=None):
    data = placeholder
    if(self.__last_call != "read()"):
      self.read(self.__wait)
    if AIX_HACK_PERSIST:
      while not self.__io["e"]["spool"]:
        sleep(0.1)
        self.read(None)
    if self.__io["e"]["spool"]:
      data = self.__io["e"]["spool"]
      self.__io["e"]["spool"] = ""
    self.__last_call = "get_stderr()"
    return data
  ###########################################################################

  def close(self):
    success = False
    assert self.__closed is False
    self.__closed = True

    self.read(self.__wait)
    for io in self.__io.keys():
      self.__io[io]["fD"].close()

    #. FIXME Python2.5 crashes here about 50% of the time.
    __ = self.__child.wait();
    self.__exit = __>>8
    self.__sig = __&0xff
    #. TODO raise RuntimeError, "failed with exit code %d\n%s"%e

    if self.__exit != 0: success = False
    elif self.__strict and len(self.__io["e"]["spool"]): success = False
    else: success = True

    return success
