#!/bin/bash
function rr_rape_by_hash() {
    e=1
    local md5=$1
    local cache=/db/${md5}
    if [ -d ${cache} ]; then
        cat ${cache}/stdout > /dev/stdout
        cat ${cache}/stderr > /dev/stderr
        e=$(cat ${cache}/exit)
    else
        printf "[Missing \`$@'!]" > /dev/stderr
    fi
    return $e
}

function rr_hash_to_cmd() {
    e=1
    if [ -d /db/$1 ]; then
        test -f /db/$1/cmd && cat /db/$1/cmd
        e=$?
    fi
    return $e
}

function rr_rape_by_cmd() {
    e=1
    local md5=$(echo "$@"|md5sum|awk '{print$1}')
    rr_rape_by_hash ${md5}
    return $?
}

#. TODO: auditd
#. Watch critical files - /etc/audit/audit.rules, `-w /etc/shadow -p wa -k shadow'
#. Monitor important syscalls, `-a exit,always -S open -S openat -F exit=-EPERM'
#. Use aureport regularly (via cron)

function _check_fs() {
    rr_printf 0 "Retrievind data pertaining to filesystem security" ...
    rr_printf 1 "Filesystem mount segregation and options" ...
    #. TODO: set audit=1 to grub.conf kernel config line
    #. /var/log/audit should be separate at least even if /var/log isn't
    for d in /var /var/log /home /tmp /var/tmp; do
        rr_printf 2 $d
        test "$(rr_rape_by_cmd stat --printf '%m' $d/..)" != "$(rr_rape_by_cmd stat --printf '%m' $d/.)"
        e=$?
        rr_echo_fail $e "Non-separat from parent"
        if [ $e -eq 0 ]; then
            mp="$(rr_rape_by_cmd stat --printf '%m' $d/.)"

            rr_printf 3 'noexec'
            noexec=$(rr_rape_by_cmd mount|grep ${mp}|awk '{if($6~/\<noexec\>/){exit 0}else{exit 1}}')
            rr_echo_fail $? "No noexec"

            rr_printf 3 'nodev'
            nodev=$(rr_rape_by_cmd mount|grep ${mp}|awk '{if($6~/\<nodev\>/){exit 0}else{exit 1}}')
            rr_echo_fail $? "No nodev"
        fi
    done
    echo

    rr_printf 1 "Filesystem mount Non-Segregation" ...
    for d in /etc /lib; do
        rr_printf 2 $d
        test "$(rr_rape_by_cmd stat --printf '%m' $d/..)" == "$(rr_rape_by_cmd stat --printf '%m' $d/.)"
        e=$?
        rr_echo_fail $e "Separated from parent"
    done
    echo

    rr_printf 1 "Checking permissions on directories" ...
    declare -A perm_mask
    perm_mask[/etc/group]=0033
    perm_mask[/etc/passwd]=0033
    perm_mask[/etc/shadow]=0077
    perm_mask[/etc/gshadow]=0077

    declare -A perm_expl
    perm_expl[/]=0755
    perm_expl[/tmp]=1777
    perm_expl[/usr]=0755
    perm_expl[/home]=0755
    perm_expl[/var]=0755
    perm_expl[/var/log]=0755

    for f in ${!perm_mask[@]}; do
        declare -i a=$(rr_rape_by_cmd stat --printf '%a' $f)
        rr_printf 2 "chmod($f) & ${perm_mask[$f]} == 0"
        test $[0$a & 0${perm_mask[$f]}] -eq 0
        rr_echo_fail $? "$a & ${perm_mask[$f]} != 0"
    done

    for f in ${!perm_expl[@]}; do
        declare -i a=$(rr_rape_by_cmd stat --printf '%a' $f)
        rr_printf 2 "chmod($f) == ${perm_expl[$f]}"
        test 0$a -eq 0${perm_expl[$f]}
        rr_echo_fail $? "$a != ${perm_expl[$f]}"
    done

    for f in ${!perm_mask[@]} ${!perm_expl[@]}; do
        declare -i u=$(rr_rape_by_cmd stat --printf '%u' $f)
        rr_printf 2 "chown($f) == root"
        test $u -eq 0
        rr_echo_fail $? "$u != 0"

        declare -i g=$(rr_rape_by_cmd stat --printf '%g' $f)
        rr_printf 2 "chgrp($f) == root"
        test $g -eq 0
        rr_echo_fail $? "$g != 0"
    done
    echo

    rr_printf 2 "Search for other (potentially) vulnerable permissions" ...
    for findcmd in $(index|awk '$2~/^find$/{print "rr_rape_by_hash",$1}'); do
        cmd=$(rr_hash_to_cmd ${findcmd})
        if [ -n "${cmd}" ]; then
            rr_printf 3 "[${cmd}]" ...
            for output in $(rr_rape_by_hash ${findcmd}); do
                rr_printf 4 ${output} .
            done
        fi
    done
}


function _check_os() {
    rr_printf 0 "Retriving reaped system information" ...
    while read line; do
        keyvalue=( $(echo $line|awk -F= '{printf("%s %s",$1,$2)}'|tr -d "'") )
        rr_printf 1 ${keyvalue[0]}
        rr_echo_info 0 ${keyvalue[1]}
    done < sysinfo.sh
    echo

    rr_printf 1 "Checking operating system" ...
    rr_printf 2 "Kernel boot options"
    rr_rape_by_cmd cat /proc/cmdline|grep -q "\<nousb\>"
    test $? -ne 0
    rr_echo_fail $? "No nousb"

    rr_printf 1 "Testing for ASLR"
    aslr=$(rr_rape_by_cmd cat /proc/sys/kernel/randomize_va_space)
    test ${aslr} -eq 2
    rr_echo_fail $? randomize_va_space=${aslr}
    rr_printf 2 "Checking last update"
    oslastupdate=${RR_SYSINFO[oslastupdate]}
    rr_echo_fail $? ${oslastupdate}

    rr_printf 2 "Checking files for smelly keywords" ...
    for ding in ${RR_LOG_BELLS[@]}; do
        rr_printf 3 "Logs of priority ${ding}"
        count=$(rr_rape_by_cmd grep ${RR_OPTS_GREP} ${ding} /var/log/|wc -l)
        if [ ${count} -eq 0 ]; then
            rr_echo_warn 0
        elif [ ${count} -lt 1000 ]; then
            rr_echo_warn 1 "${count}"
        else
            rr_echo_crit 1 "${count}"
        fi
        #rr_rape_by_cmd grep -sriE "(fatal|crit|alert|error|warn)" /var/log/
    done

    for dong in ${RR_LOL_BELLS[@]}; do
        rr_printf 3 "Perculiar configuration files with profanity ${dong}"
        count=$(rr_rape_by_cmd grep ${RR_OPTS_GREP} ${dong} /etc/|wc -l)
        if [ ${count} -eq 0 ]; then
            rr_echo_info 0
        else
            rr_echo_warn 1 "${count}"
        fi
        #rr_rape_by_cmd grep -sriE "(fatal|crit|alert|error|warn)" /var/log/
    done

}

function _check_nw() {
    rr_printf 0 "Retrieving networking data" ...
    while read line; do
        rr_printf 1 "$line" ...
    done << EOF | grep -E '(udp|raw|tcp)' --color
$(
    rr_rape_by_cmd netstat -lnpAinet\
    |sed -e 's/ \{27\}/ NONE /' -e 's/0\.0\.0\.0/*/g'\
    |grep -E '^(udp|raw|tcp)'\
    |column -t
)
EOF

    #. TODO: Check default passwords for databases installed on machine

    #. TODO: Apache - remove unneeded modules, and use mod_security

    #. TODO: Disable interactive boot on RHELL --> /etc/sysconfig/init --> PROMPT=no

    #. TODO: NTP check

    #. TODO: grep libwrap /usr{s,}bin/*|sort --> <progname>:<ip-addr> --> /etc/hosts.allow

    #. TODO: add passwd to single-user mode?

    rr_printf 1 "TCP wrappers" ...
    rr_printf 2 "Deny-All default rule"
    rr_rape_by_cmd cat /etc/hosts.deny | grep -qE '^ *ALL *: *ALL *$'
    rr_echo_fail $?

    rr_printf 1 "System control" ...
    declare -A sysctl_le
    sysctl_le[net.ipv4.tcp_max_syn_backlog]=4096

    declare -A sysctl_eq
    sysctl_eq[kernel.sysrq]=0
    sysctl_eq[kernel.core_uses_pid]=1
    sysctl_eq[kernel.exec-shield]=1
    sysctl_eq[kernel.randomize_va_space]=2
    sysctl_eq[net.ipv4.ip_forward]=0
    sysctl_eq[net.ipv4.conf.default.send_redirects]=0
    sysctl_eq[net.ipv4.conf.all.send_redirects]=0
    sysctl_eq[net.ipv4.conf.default.accept_redirects]=0
    sysctl_eq[net.ipv4.conf.all.accept_redirects]=0
    sysctl_eq[net.ipv4.conf.default.secure_redirects]=0
    sysctl_eq[net.ipv4.conf.all.secure_redirects]=0
    sysctl_eq[net.ipv4.conf.default.rp_filter]=1
    sysctl_eq[net.ipv4.conf.all.rp_filter]=1
    sysctl_eq[net.ipv4.conf.default.accept_source_route]=0
    sysctl_eq[net.ipv4.conf.all.accept_source_route]=0
    sysctl_eq[net.ipv4.conf.default.log_martians]=1
    sysctl_eq[net.ipv4.conf.all.log_martians]=1

    #. IPv6 {{
    #.     Number of Router Solicitations to send until assuming no routers are present.
    #.     This is host and not router
            #net.ipv6.conf.default.router_solicitations = 0
            #.
            # Accept Router Preference in RA?
            #net.ipv6.conf.default.accept_ra_rtr_pref = 0
            #.
            # Learn Prefix Information in Router Advertisement
            #net.ipv6.conf.default.accept_ra_pinfo = 0
            #.
            # Setting controls whether the system will accept Hop Limit settings from a router advertisement
            #net.ipv6.conf.default.accept_ra_defrtr = 0
            #.
            #router advertisements can cause the system to assign a global unicast address to an interface
            #net.ipv6.conf.default.autoconf = 0
            #.
            #how many neighbor solicitations to send out per address?
            #net.ipv6.conf.default.dad_transmits = 0
            #.
            # How many global unicast IPv6 addresses can be assigned to each interface?
            #net.ipv6.conf.default.max_addresses = 1
    #. }}
    sysctl_eq[net.ipv6.conf.default.disable_ipv6]=1
    sysctl_eq[net.ipv6.conf.all.disable_ipv6]=1
    sysctl_eq[net.ipv4.tcp_syncookies]=1
    sysctl_eq[net.ipv4.tcp_synack_retries]=2
    sysctl_eq[net.ipv4.icmp_echo_ignore_all]=0
    sysctl_eq[net.ipv4.icmp_echo_ignore_broadcasts]=1
    sysctl_eq[net.ipv4.icmp_ignore_bogus_error_responses]=1

    local actual
    local designated
    for item in ${!sysctl_le[@]}; do
        actual=$(rr_rape_by_cmd sysctl -a 2>/dev/null|awk -F '[ =]+' '$1~/'${item}'/{print$2}')
        designated=${sysctl_le[${item}]};
        rr_printf 2 "${item} <=${designated}"
        test ${actual} -le ${designated}
        rr_echo_fail $? "${actual} > ${designated}"
    done

    for item in ${!sysctl_eq[@]}; do
        actual=$(rr_rape_by_cmd sysctl -a 2>/dev/null|awk -F '[ =]+' '$1~/'${item}'/{print$2}')
        designated=${sysctl_eq[${item}]};
        rr_printf 2 "${item} ==${designated}"
        if [ -n "${actual}" ]; then
            test ${actual} -eq ${designated}
            rr_echo_fail $? "${actual} != ${designated}"
        else
            rr_echo_warn 1 "Missing"
        fi
    done

    #. Disable unnecessary protocol stacks {{
    #.     alias net-pf-4 off # IPX
    #.     alias net-pf-5 off # Appletalk
    #.     alias net-pf-10 off # IPv6
    #.     alias net-pf-12 off # Decnet
    #. }}
    declare -A rr_proto_stackz_eq
    for proto_stack in 4 5 10 12; do
        rr_printf 2 "Protocol stack id ${proto_stack} disabled"
        disabled=$(cat /etc.snapshot/modprobe.{conf,d/*} 2>/dev/null | awk \
            -F'[ \t]+' '
            BEGIN{s=0};
            $1~/^alias$/&&$2~/^net-pf-'${proto_stack}'/&&$3~/^off$/{s=1};
            END{print s}
            '
        )
        test ${disabled} -eq 1
        rr_echo_fail $? "Protocol Stack Id. ${proto_stack} has not been diabled"
    done
}

function _check_aslr() {
    for segment in '.lib.*.libc-.*so' '.bin.cat' '\[stack\]' '\[heap\]'; do
        for perm in 'r-xp' 'r--p' 'rw-p'; do
            linez=$(awk -F '[ \t]+' '$2~/^'${perm}'$/&&$6~/^'${segment}'$/{print$1}' ${RR_PROC_SELF_MAPS}|sort -u|wc -l)
            if [ ${linez} -gt 0 ]; then
                rr_printf 1 "${perm}/${segment} : ${linez}"
                test ${linez} -eq 101
                rr_echo_fail $? "${linez} < 101"
            fi
        done
    done
}


function _check_ps() {
    rr_printf 0 "Retrieving process snapshot listin" ...
    rr_rape_by_cmd pstree -puUl
}

function _check_sc() {
    rr_printf 0 "Retrieving services data" ...
    case ${RR_SYSINFO[osbreed]} in
        RedHat) rr_rape_by_cmd chkconfig --list;;
    esac
}

function _check_rl() {
    rr_printf 0 "Retrieving X-server data" ...
    rr_printf 1 "Designated runlevel in agreement with actual runlevel"
    test ${RR_SYSINFO[osrlcurrent]} -eq ${RR_SYSINFO[osrldesignated]}
    rr_echo_fail $?
    rr_printf 1 "Runlevel NOX"
    test ${RR_SYSINFO[osrldesignated]} -lt 5
    rr_echo_fail $?
    echo
}

function help() {
    echo "Usage: "
    echo "  index         //. List captured commands"
    echo "  check <item>  //. Run built-in security checks"
    echo "  rc <cmd>      //. Psudo-executed a captured command"
    echo "  rh <hash>     //. Psudo-executed a captured command (by hash)"
    echo
    echo "Captured filesystems are: ${FS}"
}

function index {
    less /index
}

function rc {
    [ $# -eq 0 ] && help || rr_rape_by_cmd $*
    return $?
}

function rh {
    [ $# -eq 1 ] && rr_rape_by_hash $1 || help
    return $?
}


function check() {
    e=0
    for i in $*; do
        case $i in
            os) _check_os;;
            rl) _check_rl;;
            fs) _check_fs;;
            sc) _check_sc;;
            nw) _check_nw;;
            ps) _check_ps;;
            aslr) _check_aslr;;
            *) e=1; echo "Error: Invalid argument $i." > /dev/stderr
        esac
    done
    if [ $e -ne 0 -o $# -eq 0 ]; then
        echo "Usage: check os|rl|fs|sc|nw|ps"
    fi
}

#. TODO: Do not allow root logins
#. TODO: Limit the su command /etc/pam.d/su -> auth required pam_wheel.so use_uid

################################################################################
red="\[\e[0;31m\]"
black="\[\e[1;30m\]"
reset="\e[m"
PS1="${red}root${black}@${red}${FQDN}${black}#${reset} "
