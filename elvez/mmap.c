#include <sys/types.h>
#include <sys/stat.h>
#include <sys/mman.h>
#include <fcntl.h>
#include <unistd.h>

#define STDOUT  1

void main(void) {
    char file[]="mmap.s";
    char *mappedptr;
    int fd,filelen;

    fd=fopen(file, O_RDONLY);
    filelen=lseek(fd,0,SEEK_END);
    mappedptr=mmap(NULL,filelen,PROT_READ,MAP_SHARED,fd,0);
    write(STDOUT, mappedptr, filelen);
    munmap(mappedptr, filelen);
    close(fd);
}
