'.
'.  Retrieving External Files via HTTP
'.
'.  This script utilizes the XMLHTTP COM object, which is present on most
'.  Windows systems, do download files via HTTP.  This can be a useful
'.  tool for retrieving binaries since firewall rules generally allow inbound
'.  HTTP traffic.
'.
'.  This script invokes the XMLHTTP object to download the file specified
'.  in the URL passed on the command line and saves it to the specified
'.  file name.
'.
'.  Usage: cscript XmlHttpGetBinary.vbs http://www.downloadnetcat.com/nc11nt.zip nc11nt.zip
'.

dim XmlHttp, Args, StdOut, URL, FileName, AsynchRequest, OutputStream
const BINARY_STREAM_TYPE = 1
const CREATE_OVERWRITE_SAVE_MODE = 2
set StdOut = WScript.StdOut
set Args = WScript.Arguments
if Args.Count <> 2 then
    StdOut.WriteLine "Usage: XmlHttpGetBinary.vbs <url> <local-file-name>"
    WScript.Quit
end if

URL = Args.Item(0)
FileName = Args.Item(1)
set XmlHttp = WScript.CreateObject("MSXML2.XMLHTTP")
set OutputStream = WScript.CreateObject("ADODB.Stream")
AsynchRequest = false
XmlHttp.Open "GET", URL, AsynchRequest
XmlHttp.Send
OutputStream.Type = BINARY_STREAM_TYPE
OutputStream.Open
OutputStream.Write XmlHttp.responseBody
OutputStream.SaveToFile FileName, CREATE_OVERWRITE_SAVE_MODE
OutputStream.Close
StdOut.Close
set XmlHttp = nothing
set AsynchRequest = nothing
set OutputStream = nothing
