#include <stdio.h>
#include <dlfcn.h>

/*
extern void *dlopen(const char *filename, int flag);
extern char *dlerror(void);
extern void *dlsym(void *handle, const char *symbol);
extern int dlclose(void *handle);
*/

int main(int ac, char **av) {
    void *h = dlopen("/usr/lib32/libc-2.13.so", RTLD_LAZY);
    void *p;

    p = dlsym(h, "printf");
    printf("%p\n", p);

    p = dlsym(h, "sprintf");
    printf("%p\n", p);

    dlclose(h);

    return 0;
}
