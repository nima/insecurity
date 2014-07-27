//. http://home.att.net/~jackklein/c/inttypes.html

#include <stdio.h>
#include <limits.h>

volatile int char_min = CHAR_MIN;

int main(void)
{
    printf("\n\n\n\n\n        Character Types\n");
    printf("Number of bits in a character: %d\n",
        CHAR_BIT);
    printf("Size of character types is %d byte\n",
        (int)sizeof(char));
    printf("Signed char min: %d max: %d\n",
        SCHAR_MIN, SCHAR_MAX);
    printf("Unsigned char min: 0 max: %u\n",
        (unsigned int)UCHAR_MAX);

    printf("Default char is ");
    if (char_min < 0)
        printf("signed\n");
    else if (char_min == 0)
        printf("unsigned\n");
    else
        printf("non-standard\n");

    printf("\n        Short Int Types\n");
    printf("Size of short int types is %d bytes\n",
        (int)sizeof(short));
    printf("Signed short min: %d max: %d\n",
        SHRT_MIN, SHRT_MAX);
    printf("Unsigned short min: 0 max: %u\n",
        (unsigned int)USHRT_MAX);

    printf("\n           Int Types\n");
    printf("Size of int types is %d bytes\n",
        (int)sizeof(int));
    printf("Signed int min: %d max: %d\n",
        INT_MIN, INT_MAX);
    printf("Unsigned int min: 0 max: %u\n",
        (unsigned int)UINT_MAX);

    printf("\n        Long Int Types\n");
    printf("Size of long int types is %d bytes\n",
        (int)sizeof(long));
    printf("Signed long min: %ld max: %ld\n",
        LONG_MIN, LONG_MAX);
    printf("Unsigned long min: 0 max: %lu\n",
        ULONG_MAX);

    return 0;
}
