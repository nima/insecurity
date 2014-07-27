#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <sys/mman.h>

unsigned char *testfun;
unsigned int fun(unsigned int a) { return a+13; }

int main(int argc, char *argv[]) {
    unsigned int pagesize = getpagesize();

    testfun = malloc(1023+pagesize+1);
    if(testfun == NULL) return 1;

    //. Need to align the address on a page boundary
    printf("Unaligned : %p\n", testfun);
    testfun = (unsigned char *)(((long)testfun + pagesize-1) & ~(pagesize-1));
    printf("Aligned   : %p\n", testfun);

    printf("  MProtect: ");
    if(mprotect(testfun, 1024, PROT_READ|PROT_EXEC|PROT_WRITE)) {
        printf("FAIL\n");
        return 1;
    } else printf("PASS\n");

    //400687: b8 0d 00 00 00          mov    $0xd,%eax
    //40068d: c3                      retq
    unsigned int ra;
    {
        testfun[ 0]=0xb8;
        testfun[ 1]=0x0d;
        testfun[ 2]=0x00;
        testfun[ 3]=0x00;
        testfun[ 4]=0x00;
        testfun[ 5]=0xc3;
        ra = ((unsigned int (*)())testfun)();
        printf("  ra      : %#04x\n",ra);
    }

    {
        testfun[ 0]=0xb8;
        testfun[ 1]=0x20;
        testfun[ 2]=0x00;
        testfun[ 3]=0x00;
        testfun[ 4]=0x00;
        testfun[ 5]=0xc3;
        ra=((unsigned int (*)())testfun)();
        printf("  ra      : %#04x\n",ra);
    }

    printf("Fun       : %p\n", fun);
    unsigned char *ptr = (unsigned char *)((long)fun&(~(pagesize-1)));
    printf("  Ptr     : %p\n", ptr);
    unsigned int offset = (unsigned int)(((long)fun)&(pagesize-1));
    printf("  Offset  : %#06x\n", offset);

    printf("  MProtect: ");
    if(mprotect(ptr, pagesize, PROT_READ|PROT_EXEC|PROT_WRITE)) {
        printf("FAIL\n");
        return 1;
    } else printf("PASS\n");

    //for(ra=0;ra<20;ra++) printf("0x%02X,",ptr[offset+ra]); printf("\n");

    {
        ra = 4;
        ra = fun(ra);
        printf("  ra      : %#04x\n",ra);
    }

    {
        ptr[offset+0]=0xb8;
        ptr[offset+1]=0x22;
        ptr[offset+2]=0x00;
        ptr[offset+3]=0x00;
        ptr[offset+4]=0x00;
        ptr[offset+5]=0xc3;

        ra=4;
        ra=fun(ra);
        printf("  ra      : %#04x\n",ra);
    }

    return 0;
}
