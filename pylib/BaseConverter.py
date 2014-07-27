BASE = {
  'hex': '0123456789abcdef',
  'dec': '0123456789',
  'oct': '01234567',
  'bin': '01',
}

def h2b(chunk): return baseConvert(chunk, 'hex', 'bin', 4*len(chunk))
def h2d(chunk): return long(baseConvert(chunk, 'hex', 'dec'))
def b2d(chunk): return long(baseConvert(chunk, 'bin', 'dec'))
def baseConvert(number, fromdigits, todigits, width=0):
  res=""

  neg = 0
  if str(number)[0] == '-':
    number = str(number)[1:]
    neg = 1

  x = long(0)
  for digit in str(number):
    x = x*len(BASE[fromdigits]) + BASE[fromdigits].index(digit)

  if x > 0:
    while x > 0:
      digit = x % len(BASE[todigits])
      res = BASE[todigits][digit] + res
      x /= len(BASE[todigits])
    if neg: res = "-"+res
  else:
    res = "0"

  return res.zfill(width)

#. Binary to IP
def b2ip(b):
  return ".".join(
    (
      str(b2d(b[0:8])),
      str(b2d(b[8:16])),
      str(b2d(b[16:24])),
      str(b2d(b[24:32]))
    )
  )
