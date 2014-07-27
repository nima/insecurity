#include <stdio.h>
#include<fcntl.h>
#include<unistd.h>
#include<sys/stat.h>
#include<sys/types.h>

int main(int argc, char *argv[]) {
    int e = 1;

    char *filename = NULL;
    int f = fileno(stdin);

    char l[80];
    int n;
    if(argc == 2) {
        filename = argv[1];
        if(access(filename, F_OK) == 0) {
            struct stat s;
            if(stat(filename, &s) >= 0) {
                if(S_ISREG(s.st_mode) >= 0) {
                    if(geteuid() == s.st_uid) {
                        if(s.st_mode & S_IRUSR) {
                            e = 0;
                            f = open(filename,O_RDONLY);
                        }
                    } else if(getegid() == s.st_gid) {
                        if(s.st_mode & S_IRGRP) {
                            e = 0;
                            f = open(filename,O_RDONLY);
                        }
                    } else if(s.st_mode & S_IROTH) {
                        e = 0;
                        f = open(filename,O_RDONLY);
                    }
                }
            }
        }
    } else e = 0;

    if(e == 0) {
        while((n=read(f, l, 80)) > 0)
            write(1,l,n);
    }

    return e;
}
