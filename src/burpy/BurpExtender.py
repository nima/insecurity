#from burp import IBurpExtender
#from burp import IBurpExtenderCallbacks
#from burp import IMenuItemHandler

#from cgi import parse_qs
#import traceback, sys

#exitSuite = None
#class BurpExtender(IBurpExtender):
#    def registerExtenderCallbacks(self, callbacks):
#        global exitSuite
#        self.mCallBacks = callbacks
#        try:
#            self.mCallBacks.registerMenuItem("Phuck off quietly", FOMenuItem())
#            exitSuite = self.mCallBacks.exitSuite
#        except:
#            print "This only works with version 1.3.07 of the suite"
#            traceback.print_exc(file=sys.stderr)
#
#class FOMenuItem(IMenuItemHandler):
#    def menuItemClicked(self, menuItemCaption, messageInfo):
#        exitSuite(False)
