#define hex2int(c) (unsigned short)(\
    (c>='0' && c<='9') ? c-'0' : (\
        (c>='A' && c<='F') ? c-'A'+10 : (\
            (c>='a' && c<='f') ? c-'a'+10 : 0\
        )\
    )\
)
