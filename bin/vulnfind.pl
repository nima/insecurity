#! /usr/bin/perl
#
# My crappy perl script to find open ports, enumerate services
# and nbtscan them.  Prints a simple report.
#
# Author: Paul Asadoorian (paul@pauldotcom.com)
# Web: http://pauldotcom.com
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR IMPLIED
# WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO
# EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
# SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
# PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
# OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
# WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
# OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
# ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#


use Nmap::Parser;
use Getopt::Std;

#
# Declare some variables
#
my $svc;
my $host;
my $port;
my $ip_addr;

#
# Do the usage stuff and get options
# 
print "vulnfind.pl - ( paul\@pauldotcom.com )\n",('-'x50),"\n\n";
	
getopts('hi:');

die "Usage: $0 [-i <hosts>]\n"
	unless ($opt_i);

#
# Create the parser object
#
my $np = new Nmap::Parser;

#
# Execute Nmap Scan
#
$np->parsescan('/usr/bin/nmap','-n -P0 -T4 -sV -p 2967,2968', $opt_i);

#
# Get the host information
#
for my $host ($np->all_hosts()){

      my $open_port=0;
      for my $ports ($host->tcp_ports('open')){
	$open_port=1;
      };

      if($open_port){

	$ip_addr  = $host->addr;

	print('Address    : '.$ip_addr."\n");

	my $nbtscan = "/usr/bin/nbtscan -s , $ip_addr";
                
        open(NBTSCAN, "exec $nbtscan |");
                
        my $nbtscan_output = "";
                
        #FORMAT: 192.168.1.21,PAULDOTCOM-LAB-M,<server>,PAULDOTCOM-LAB-M,00:00:00:00:00:00
                
        while(<NBTSCAN>) {
        	$nbtscan_output .= $_; 
        	my($nbt_ip, $nbt_name, $nbt_service, $nbt_type, $nbt_mac) = split(/,/, $_);
        	print ('SMB Name   :  ',$nbt_name, "\n", 'MAC Address : ',$nbt_mac);
        }


	for my $port ($host->tcp_open_ports){

   		my $svc = $host->tcp_service($port);
        	print('Service    :  ',$port,' ('.$svc->name.') ',$svc->product,' ',$svc->version,' ',$svc->extrainfo,"\n");

	}

	 print("-------------------------------------\n");

      };
   }
