#include <stdio.h>

int main(int ac, char **av) {
    FILE *fH = fopen("/proc/self/maps", "r" );
    char line[128];
    while(fgets(line, sizeof line, fH) != NULL)
        fputs(line, stdout );
    fclose(fH);

    return 0;
}
