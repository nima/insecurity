SMOKED :=
BAKE   :=
BREWED :=
LIBS   :=
HEADS  :=
DATA   := GeoLiteCity.dat pc-au.csv

#-------------------------------------------------------------------------------
#. System Monitor/Alterer/Tracer
SMOKED += locate strace ltrace lsof which whereis netstat iptables
SMOKED += macchanger multitail
BREWED += manson
BAKE   += atomicles
#. Network Scanners/Resolver/Tracer
SMOKED += hping hping2 hping3 traceroute dnstracer dig geoiplookup
SMOKED += tcpdump nmap nping ngrep wireshark ettercap dsniff whois
SMOKED += netwatch arpwatch snmpwalk tshark ldapsearch firewalk
SMOKED += ncftp smbclient wget
BAKE   += tcptraceroute dmitry
BREWED += qcli google-dork fierce
BREWED += geoip postal myip
#. Network Intruders
BAKE   += sock hydra isic scnc
SMOKED += nc scapy socat arping
BREWED += connect
#. Web Scanner/Proxy/Utilities
BAKE   += belch #. Non-Free Software
BAKE   += dirbuster nikto
BREWED += lbd urlencode
#. Disassemblers
SMOKED += idal idaq #. Non-Free
SMOKED += objdump
BREWED += elf-entrypoint
BAKE   += eresi
#. Decompilers
BAKE   += jd-gui
#. Binary/Hex Manipulation
SMOKED += hexedit hexdump xxd cscope
BAKE   += vbindiff dhex
BREWED += elf-check bgrep
#. Compililation
SMOKED += flex bison yacc ld ldd nm
SMOKED += gcc g++ javac automake autoconf autoheader
#. Debuggers
SMOKED += gdb
#. Packers
SMOKED += upx
#. Assemblers
SMOKED += nasm yasm as
#. Interpreters
SMOKED += python python2 perl mono java rhino php
BAKE   += spidermonkey
#. Coding
SMOKED += diff patch indent
SMOKED += wing #. Non-Free
#. Compression
SMOKED += rar unrar 7z zip unzip tar bzip2 gzip uncompress gunzip bunzip2
#. Exploit/Malware Development
SMOKED += gnulib-tool
BREWED += canvas #. Non-Free Software
BREWED += allinone
BREWED += shellcoder shellwriter shelldisasm
BREWED += shell-asciihex2bin shell-elf2asciihex shell-elf2cstr
BREWED += syscall-resolve
BREWED += esp hexection chk-endian
BREWED += hexquash
BAKE   += libdasm libptrace
#. Crackers
SMOKED += fcrackzip john aircrack-ng
SMOKED += ncrack ophcrack cmospwd cowpatty
SMOKED += medusa xlcrack
BAKE   += ike pkcrack
BREWED += sshcrack
BREWED += cisco-decrypt ciscocrack
#. Crypto
SMOKED += gpg openssl
BAKE   += ent aescrypt
#. Fuzzing
BAKE   += jmeter zzuf
#. SCM
SMOKED += git svn cvs
#. GRSecurity, PaX
BAKE   += gradm paxctl pax-utils paxtest
BREWED += paxit paxtest
#. Recon
BAKE   += dnsmap
BREWED += scrape

#. Document Readers/Manipulators
SMOKED += zathura
BREWED += phraxtract
#. Media Readers/Manipulators
SMOKED += feh ppmquant pnmnoraw convert img2txt #. via feh, netpbm, and imagemagick
BREWED += doc2pdf
#. Utilities
SMOKED += awk sed vim fold
SMOKED += rsync ssh scp telnet openvpn rdesktop
SMOKED += vmware #. Non-Free Software
SMOKED += w3m screen tcsh bc sqlite3
SMOKED += pwgen ipcalc
SMOKED += xdg-open xset beep
SMOKED += dos2unix pcregrep
SMOKED += srm wipe top iftop finger scrot
SMOKED += colordiff transmission-cli nice ionice cowsay ctags
SMOKED += mkfs.msdos mkfs.vfat mkfs.ntfs
BREWED += resync supergenpass
BREWED += beeping
BAKE   += tty-clock darkhttpd
#. Search Engine Utilities (for recoll)
SMOKED += recoll unrtf id3info catdoc antiword pstotext pstopdf
SMOKED += djvudigital djvudump djvuextract djvumake djvups djvused djvuserve
SMOKED += djvutoxml djvutxt djvuxmlparser
BREWED += deathrow

#-------------------------------------------------------------------------------
LIBS   += libnet.so libpcap.so libcrack.so libcrypt.so libsnmp.so libpcre.so
LIBS   += libwiretap.so libssl.so libssh.so libpng.so libtiff.so libgif.so
LIBS   += libncurses.so libxml2.so libcucul.so libcaca.so libsvn_client-1.so
LIBS   += libapr-1.so libaprutil-1.so libfbclient.so libmysqlclient.so
LIBS   += libgdk-x11-2.0.so libdasm32.so libdasm64.so
#LIBS   += lib32-pcre-8.10-3  lib32-dbus-core-1.4.0-2  lib32-glib2-2.26.1-2  lib32-glib-1.2.10-11
#LIBS   += libafpclient libncp libpg

HEADS  += libdasm.h

#. FIXME: header requirements: openssl/rsa.h

################################################################################
#. TODO: Add a check for perl modules
PERL := net-ssleay #. For nikto ssl support

################################################################################
include make.d/Makefile.header

help:
	@echo "################################################################################"
	@echo "### Autonomy Insecurity Pentest Toolkit ###"
	@echo
	@echo "Generally you would do this:"
	@echo ""
	@echo "    <editor> insecurity.conf"
	@echo "    make build"
	@echo "    sudo make install"
	@echo "    make status"
	@echo
	@echo "However, if you'd like to step through the phases yourself, here are"
	@echo "all the supported targets, along with a brief description"
	@echo ""
	@echo "    make extract        (to extract source without building)"
	@echo "    make patch          (to patch the source if need-be)"
	@echo "    make build          (compile the extracted and patched source)"
	@echo "    sudo make install   (install into $(PREFIX), symlink from /opt/bin)"
	@echo "    sudo make uninstall (uninstall from $(PREFIX))"
	@echo "    make clean          (extracted source, pre-build state)"
	@echo "    make purge          (like clean, but also remove extracted source)"
	@echo "    make status         (state map of your machine)"
	@echo
	@echo "Missing sources? Get them from http://src.autonomy.net.au/insecurity/"
	@echo ""
	@echo "    Note that all sources attained from this uri are not mine - I merely"
	@echo "    keep a collection of them here for your convenience."
	@echo "    distributed by the upstream."
	@echo
	@echo "    Currently, you have elected the following directory as your source space:"
	@echo ""
	@printf "        SOURCEZ : ${TARBALLZ} ... ";
	@test -d ${TARBALLZ} && printf "$(AIS_PASS)\n" 'PASS'|| printf "$(AIS_FAIL)\n" 'FAIL'
	@printf "        DATAZ : ${DATAZ} ... ";
	@test -d ${DATAZ} && printf "$(AIS_PASS)\n" 'PASS'|| printf "$(AIS_FAIL)\n" 'FAIL'
	@echo
	@echo "################################################################################"

status: clear
	@echo "Binariez expected to be smoked"
	@( $(foreach r,$(SMOKED),$(call smoke,$r);) )|sort -k2
	@echo
	@echo "Shared librariez:"
	@( $(foreach s,$(LIBS),$(call libz,$s);) )|sort -k2
	@echo
	@echo "Data filez:"
	@( $(foreach d,$(DATA),$(call data,$d);) )|sort -k2
	@echo
	@echo "Home-brewed executablez:"
	@( $(foreach c,$(BREWED),$(call cook,$c);) )|sort -k2
	@echo
	@echo "Packages to bake (if ingredients are at hand):"
	@( $(foreach b,$(BAKE),$(call bake,$b);) )|sort -k2
	@echo
	@echo "Summary:"
	@printf "$(AIS_FAIL)${AIS_SEP}%s\n" `cat ${AIS_ERRORZ}|wc -l` errorz
	@printf "$(AIS_WARN)${AIS_SEP}%s\n" `cat ${AIS_WARNINGZ}|wc -l` warningz
	@#m=`cat ${AIS_ERRORZ}`; $(foreach c,$$m,printf " \+++$(AIS_FAIL)\n" $c)
clear:
	@printf '' > ${AIS_ERRORZ}
	@printf '' > ${AIS_WARNINGZ}
source:
	@mkdir -p $@ && cd $@ && wget -q -w3 -c -A '*.tar.gz' -nd -r 'http://src.autonomy.net.au/insecurity/'

count: COUNT=$(shell $(MAKE) status|awk '$$1~/^\[/{print$$2}'|sort -u|wc -l)
count:
	@echo $(COUNT) executables

$(shell mkdir -p build)
include make.d/Makefile.direct

build: $(foreach c,$(BREWED),build/$c)
	@$(call proxy)

clean:
	@$(MAKE) -C elvez $@
	@rm -f $(foreach c,$(BREWED),build/$c)
	@rm -f ${AIS_ERRORZ}
	@rm -f ${AIS_WARNINGZ}
	@find . -type l -exec rm -f {} \;
	@$(call proxy)

purge: clean
	@$(call proxy)
	@rm -f $(foreach d,$(DATA),data/$d)
	@rmdir build && mkdir build

install: ${PREFIX}/bin ${PREFIX}/share $(foreach c,$(BREWED),${PREFIX}/bin/$c)
	@$(call proxy)
	@$(foreach b,$(BAKE),$(MAKE) --no-print-directory -f make.d/Makefile.$b $@;)
	mkdir -p ${PREFIX}/bin
	mkdir -p ${PREFIX}/share
	rsync -qa share/ ${PREFIX}/share
	rsync -qa pylib/ ${PREFIX}/pylib
	sudo /opt/bin/paxit
${PREFIX}/bin:; mkdir -p $@
${PREFIX}/share:; mkdir -p $@

${PREFIX}/bin/%: build/%
	install $< $@
	ln -sf $@ /opt/bin/$(@F)

uninstall:
	@$(call proxy)
	rm -rf ${PREFIX}

reinstall: clean build
	sudo $(MAKE) uninstall
	sudo $(MAKE) install

.PHONY: uninstall install clean purge build sanity

################################################################################
#. FIXME:
qualys: build/qualys.tar.gz
build/qualys.tar.gz: build/qualys/__init__
	cd $(@D) && tar --owner=root --group=root -czvf $(@F) qualys
pylib/ntlm:
	svn co http://python-ntlm.googlecode.com/svn/trunk/python26/ntlm $@
build/qualys/__init__: pylib/ntlm pylib/qapi.py pylib/ANSI.py pylib/ConfigFileManager.py bin/qcli share/qapi.ini share/qcli.conf share/qscript.py
	rm -rf $(@D)
	mkdir $(@D)
	cp -a $^ $(@D)
	touch $@
