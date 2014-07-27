/* coding:utf-8
*. vim: fileencoding=utf-8 syntax=c sw=4 ts=4 et
*.
*. © 2009 Felix Domke <tmbinc@elitedvb.net>
*. © 2010 Nima Talebi <nima@autonomy.net.au>
*.
*. Placed in the public domain April 2009 by the author: no copyright is
*. claimed, and you may use it for any purpose you like.
*.
*. No warranty for any purpose is expressed or implied by the author.
*. Report bugs and send enhancements to the author.
*/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <dirent.h>
#include <fcntl.h>
#include <unistd.h>
#include <sys/stat.h>

int ascii2hex(char *c) {
    if(     *c <  '0') return -1;
    else if(*c <= '9') return *c - '0';
    else if(*c <  'A') return -1;
    else if(*c <= 'F') return *c - 'A' + 10;
    else if(*c <  'a') return -1;
    else if(*c <= 'f') return *c - 'a' + 10;
    else return -1;
}

char *errors[3] = {
    "invalid hex string",
    "empty search string",
    "invalid search string",
};

void searchfile(const char *filename, int fd, const unsigned char *value, const unsigned char *mask, int len) {
    off_t offset = 0;
    int r, o, i;

    unsigned char buf[1024];
    do {
        memcpy(buf, buf+len, len);
        r = read(fd, buf+len, 1024-len);

        if(r > 0) {
            for(o=offset?len:0; o<r-len+1; ++o) {
                for(i=0; i<len; ++i)
                    if((buf[o+i] & mask[i]) != value[i])
                        break;

                if(i == len) {
                    printf(
                        "%s:%#010llx",
                        filename,
                        (unsigned long long)(offset+o-len)
                    );
                    if(len < 16) {
                        printf(" ");
                        for(i=-4; i<(len+4); i++) {
                            if(i == 0) printf("\033[1;31m");
                            printf("%02x", buf[o+i]);
                            if(i+1 == len) printf("\033[1;0m");
                        }
                    }
                    printf("\n");
                }
            }
            offset += r;
        } else if(r < 0)
            perror("read");
    } while(r > 0);
}

void recurse(const char *path, const unsigned char *value, const unsigned char *mask, int len) {
    struct stat s;
    if(stat(path, &s)) {
        perror("stat");
        return;
    }
    if(!S_ISDIR(s.st_mode)) {
        int fd = open(path, O_RDONLY);
        if(fd >= 0) {
            searchfile(path, fd, value, mask, len);
            close(fd);
        } else perror(path);
        return;
    }

    DIR *dir = opendir(path);
    if(!dir) {
        perror(path);
        exit(3);
    }

    struct dirent *d;
    while ((d = readdir(dir))) {
        if (!(strcmp(d->d_name, ".") && strcmp(d->d_name, "..")))
            continue;
        char newpath[strlen(path) + strlen(d->d_name) + 1];
        strcpy(newpath, path);
        strcat(newpath, "/");
        strcat(newpath, d->d_name);
        recurse(newpath, value, mask, len);
    }

    closedir(dir);
}

int main(int argc, char **argv) {
    unsigned char value[0x100], mask[0x100];
    short len = 0;
    short e = 0;
    char *h = NULL;

    if(argc < 2) {
        fprintf(stderr, "usage: %s <hex> [<path> [...]]\n", *argv);
        e = 1;
    } else {
        h = argv[1];
        int v0, v1;
        while(e == 0 && *h && len < 0x100) {
            if(*h != '.') {
                while(*h == ' ') h++;
                if((v0 = ascii2hex(h++)) > -1) {
                    while(*h == ' ') h++;
                    if((v1 = ascii2hex(h++)) > -1) {
                        value[len] = (v0 << 4) | v1;
                        mask[len++] = 0xFF;
                    } else e = 2;
                } else e = 2;
            } else {
                value[len] = mask[len] = 0;
                len++;
                h += 1;
            }
        }
    }

    if(e == 0) {
        if (argc < 3)
            searchfile("stdin", 0, value, mask, len);
        else {
            int c = 2;
            while(c < argc)
                recurse(argv[c++], value, mask, len);
        }
    } else {
        if(!len) e = 2;
        else if(len % 2 == 1) e = 3;
        else if(*h) e = 4;
        fprintf(stderr, "ERROR: %s\n", errors[e-1]);
    }

    return e;
}

