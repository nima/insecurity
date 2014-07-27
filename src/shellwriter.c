#include <stdio.h>
#include <assert.h>
#include <string.h>
#include <sys/mman.h>
#include "shell.h"

int main(int argc, char *argv[]) {
    int e = -1;
    if(argc > 1) {
        e = 0;

        int i, j, k;
        for(i=1,k=0; i<argc; i++) {
            k += strlen(argv[i]);
            assert(k%2 == 0);
        };

        k = 0;
        for(i=1; i<argc; i++)
            for(j=0; j<strlen(argv[i]); j+=2, k++)
                printf(
                    "%c",
                    (char)(
                        (hex2int(argv[i][0+j])<<4)
                        + hex2int(argv[i][1+j])
                    )
                );
    } else
        printf("Usage: shellwriter <shellcode>\n");

    return e;
}
