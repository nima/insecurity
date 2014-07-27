#include <stdio.h>
#include <stdlib.h>

#include <assert.h>

#include <limits.h> /* CHAR_MIN, CHAR_MAX */
#include <stddef.h> /* wchar_t */

#define SIZE_OF(type) printf("%12s: %lu\n", #type,  sizeof(type))

void chk_char(void) {
    printf("%12s: %d..%d\n", "char", CHAR_MIN, CHAR_MAX);
}

int main(int argc, char *argv[], char *envp[]) {
    chk_char();
    SIZE_OF(char);
    SIZE_OF(wchar_t);
    SIZE_OF(short);
    SIZE_OF(int);
    SIZE_OF(long);
    SIZE_OF(long long);

    { int i=0; i += ++i; assert(i == 2); }
    { int i=0; i += i++; assert(i == 1); }
    { char c = 'free'; assert(c == 101); }
    {
        unsigned long  l = 0x12345678;
        unsigned short s = 0x1234;
        printf("s:%x, l:%lx\n", s, l);

        s = ((struct { unsigned short $; }){(l)}.$);
        printf("s:%x, l:%lx\n", s, l);

        s = (unsigned short)l;
        printf("s:%x, l:%lx\n", s, l);

        s = l;
        printf("s:%x, l:%lx\n", s, l);
    }

    return EXIT_SUCCESS;
}
