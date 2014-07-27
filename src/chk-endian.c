#include <stdint.h>
#include <stdio.h>

int main(int argc, char *argv[]) {
    uint32_t d = 0x01234567;
    unsigned char *p = (unsigned char *)&d;

    uint32_t e = (p[3]&0xff);
    e         += (p[2]&0xff) << 8;
    e         += (p[1]&0xff) << 16;
    e         += (p[0]&0xff) << 24;

    printf(
        "%s_ENDIAN: 0x%08x -> 0x%08x\n",
        ((e^d) == 0x66666666) ? "LIL" : "BIG",
        d, e
    );

    return 0;
}
