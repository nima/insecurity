#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <ctype.h>

/* Converts a hex character to its integer value */
char from_hex(char ch) {
    return isdigit(ch) ? ch - '0' : tolower(ch) - 'a' + 10;
}

/* Converts an integer value to its hex character*/
char to_hex(char code) {
    static char hex[] = "0123456789abcdef";
    return hex[code & 15];
}

/* Returns a url-encoded version of str */

/* IMPORTANT: be sure to free() the returned string after use */
char *url_encode(char *str) {
    char *pstr = str, *buf = malloc(strlen(str) * 3 + 1), *pbuf = buf;

    while(*pstr) {
        if(isalnum(*pstr) || *pstr == '-' || *pstr == '_' || *pstr == '.' ||
            *pstr == '~')
            *pbuf++ = *pstr;
        else if(*pstr == ' ')
            *pbuf++ = '+';
        else
            *pbuf++ = '%', *pbuf++ = to_hex(*pstr >> 4), *pbuf++ =
                to_hex(*pstr & 15);
        pstr++;
    }
    *pbuf = '\0';
    return buf;
}

/* Returns a url-decoded version of str */

/* IMPORTANT: be sure to free() the returned string after use */
char *url_decode(char *str) {
    char *pstr = str, *buf = malloc(strlen(str) + 1), *pbuf = buf;

    while(*pstr) {
        if(*pstr == '%') {
            if(pstr[1] && pstr[2]) {
                *pbuf++ = from_hex(pstr[1]) << 4 | from_hex(pstr[2]);
                pstr += 2;
            }
        } else if(*pstr == '+') {
            *pbuf++ = ' ';
        } else {
            *pbuf++ = *pstr;
        }
        pstr++;
    }
    *pbuf = '\0';
    return buf;
}

int main(int argc, char *argv[]) {
    const char *DECODE = "decode";
    const char *ENCODE = "encode";

    int e = -1;
    char *(* fn)(char *) = NULL;
    if(argc > 1) {
        e = 0;
        if(strstr(DECODE, argv[1]) == DECODE)
            fn = url_decode;
        else if(strstr(ENCODE, argv[1]) == ENCODE)
            fn = url_encode;
        else e = 1;
    }

    if(fn != NULL) {
        char *buf = NULL;
        if(argc > 2) {
            int i;
            for(i=1; i<argc-1; i++) {
                buf = fn(argv[i + 1]);
                printf("%s\n", buf);
                free(buf);
            }
        } else if(argc > 1) {
            FILE *f = fdopen(fileno(stdin), "r");

            char l[1024];
            while(fgets(l, sizeof l, f) != NULL) {
                l[strlen(l) - 1] = '\0';
                buf = fn(l);
                printf("%s\n", buf);
                free(buf);
            }
        }
        buf = NULL;
    }

    if(e != 0) {
        printf("Usage: %s encode|decode <str1> [<str2> [...]]\n", argv[0]);
    }

    return e;
}
