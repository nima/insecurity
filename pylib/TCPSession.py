from ANSI import *
class TCPSession:
  def __init__(self):
    self._valid = False

    self._server = {
      "addr" : None,
      "port" : None,
      "seq"  : None,
      "ack"  : None,
      "win"  : None,
    }

    self._client = {
      "addr" : None,
      "port" : None,
      "seq"  : None,
      "ack"  : None,
      "win"  : None,
    }

  def __repr__(self):
    return " * Server: %s:%s <seq:%s, ack:%s, wsize:%s>\n * Client: %s:%s <seq:%s, ack:%s, wsize:%s>"%(
      green(self._server["addr"]),
      yellow(self._server["port"]),
      white(self._server["seq"]),
      white(self._server["ack"]),
      white(self._server["win"]),
      green(self._client["addr"]),
      yellow(self._client["port"]),
      white(self._client["seq"]),
      white(self._client["ack"]),
      white(self._client["win"]),
    )

  def feedPacket(self, ip_packet):
    tcp_segment = ip_packet.get_encapsulated()
    if tcp_segment:
      sport = tcp_segment.get_sport()
      dport = tcp_segment.get_dport()
      saddr = ip_packet.get_src()
      daddr = ip_packet.get_dst()
      seq = tcp_segment.get_seq()
      ack = tcp_segment.get_ack()
      win = tcp_segment.get_wsize()
      if dport < 1024 and sport >= 1024: #. CLT -> SVR
        self.setServer("port", dport)
        self.setClient("port", sport)
        self.setServer("addr", daddr)
        self.setClient("addr", saddr)
        self.setClient("seq", seq)
        self.setClient("ack", ack)
        self.setClient("win", win)
      elif sport < 1024 and dport >= 1024: #. SRV -> CLT
        self.setServer("port", sport)
        self.setClient("port", dport)
        self.setServer("addr", saddr)
        self.setClient("addr", daddr)
        self.setServer("seq", seq)
        self.setServer("ack", ack)
        self.setServer("win", win)
    return self._valid

  def setClient(self, key, value):
    assert key in self._client.keys()
    self._client[key] = value
    return self._revalidate()

  def setServer(self, key, value):
    assert key in self._server.keys()
    self._server[key] = value
    return self._revalidate()

  def getClient(self, key):
    return self._client[key]

  def getServer(self, key):
    return self._server[key]

  def _revalidate(self):
    self._valid = None not in self._server.values()+self._client.values()
    return self._valid

  def valid(self):
    return self._valid

  def is_quiet(self):
    r = None
    if self._valid:
      r = self._server["seq"] == self._client["ack"] and self._client["seq"] == self._server["ack"]

      print "Assertion 1:",
      print self._client["ack"] <= self._server["seq"] <= self._client["ack"] + self._client["win"] and green(True) or red(False),
      print self._client["ack"], "<=", self._server["seq"], "<=", self._client["ack"], "+", self._client["win"]

      print "Assertion 2:",
      print self._server["ack"] <= self._client["seq"] <= self._server["ack"] + self._server["win"] and green(True) or red(False),
      print self._server["ack"], "<=", self._client["seq"], "<=", self._server["ack"], "+", self._server["win"]

    return r
