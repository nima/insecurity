@echo off
rem gpresult - gets group policy settings
echo '##gpresult' >> securus_output.txt
gpresult >> securus_output.txt
rem get account details
echo '##net_accounts' >> securus_output.txt
net accounts >> securus_output.txt
rem check config
echo '##net_config workstation '>> securus_output.txt
net config workstation >> securus_output.txt
echo '##net_config server' >> securus_output.txt
net config server >> securus_output.txt
rem on domain controllers, get groups. 666
echo '##net_group' >> securus_output.txt
net group >> securus_output.txt
rem get localgroups
echo '##net_localgroup' >> securus_output.txt
net localgroup >> securus_output.txt
rem get a list of open sessions
echo '##net_session' >> securus_output.txt
net session >> securus_output.txt
rem ipconfig and netstat, basic tools for checking connections/ip address
echo '##netconfigs' >> securus_output.txt
ipconfig /all >> securus_output.txt
netstat -anb >> securus_output.txt
echo '##regresults' >> securus_output.txt
rem gets registry settings related to windows update
reg query "HKLM\SYSTEM\CurrentControlSet\Services\wuauserv" /s >> securus_output.txt
reg query "HKLM\Software\Microsoft\Windows\CurrentVersion\Policies" /s >> securus_output.txt
echo '##tasklist' >> securus_output.txt
tasklist /SVC >> securus_output.txt
echo '##wmic_proclist' >> securus_output.txt
wmic process list >> securus_output.txt
rem this checks various dep settings
echo '##wmic_stuff' >> securus_output.txt
wmic OS Get DataExecutionPrevention_Available >> securus_output.txt
wmic OS Get DataExecutionPrevention_SupportPolicy >> securus_output.txt
wmic OS Get DataExecutionPrevention_32BitApplications >> securus_output.txt
wmic OS Get DataExecutionPrevention_Drivers >> securus_output.txt
rem systeminfo for info related to patching
echo '##systeminfo' >> securus_output.txt
systeminfo >> securus_output.txt
echo '##NETSH' >> securus_output.txt
rem  Displays the excepted programs.
netsh firewall show allowedprogram >> securus_output.txt
rem  local configuration information
netsh firewall show config >> securus_output.txt
rem  current profile
netsh firewall show currentprofile >> securus_output.txt
rem  icmp settings
netsh firewall show icmpsettings >> securus_output.txt
rem  logging settings
netsh firewall show logging >> securus_output.txt
rem  multicast/broadcast response settings
netsh firewall show multicastbroadcastresponse >> securus_output.txt
rem  current settings for notifications
netsh firewall show notifications >> securus_output.txt
rem  operational mode
netsh firewall show opmode >> securus_output.txt
rem  excepted ports
netsh firewall show portopening >> securus_output.txt
rem  services
netsh firewall show service >> securus_output.txt
rem  current state information
netsh firewall show state >> securus_output.txt
rem Window uses the ShellBags to store information the display settings and Most
rem Recently Used (MRU) information about individual folders that have been open
rem or closed at least once in Windows Explorer. When recording information
rem about remote folders the information will be stored under the "Shell" key
rem while local folders will be record in the “ShellNoRoam”. The structure of
rem both remote and local keys are identical.
rem In a penetration test and/or a capture the flag scenario the analysis of
rem Shellbags may provide you clues as to additional places to look for sensitive
rem or required information. This could be especially useful in the remote
rem Shellbags for identifying remote folders that are accessed from non-mapped
rem network drives. This could potentially reduce the time required to identify
rem sensitive documents.
reg query "HKEY_USERS\\Software\Microsoft\Windows\Shell" /s >> securus_output.txt
reg query "HKEY_USERS\\Software\Microsoft\Windows\ShellNoRoam" /s >> securus_output.txt
reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\ComDlg32\OpenSaveMRU" /s >> securus_output.txt
