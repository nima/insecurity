#!/usr/bin/awk
BEGIN { FS=":" }
{
    if($0 ~ block) {
        gsub(/ /, "", block);
        catch=1
        if(!inc)
            next
    } else if($0 ~ /^$/) {
        if(inc) print $0
        if(catch && !showall)
            exit
        catch=0
    } else if($0 && $2 && catch) {
        gsub(/[\t ]+/, "", $0);
        gsub(/[^a-zA-Z0-9]/, "", $1);
        printf("dmi_%s[%s]=\"%s\"\n", block, $1, $2);
    }
}
