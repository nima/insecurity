#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>

#include <libdasm.h>

#include "shell.h"

int print_instruction(INSTRUCTION *inst, BYTE *buffer, unsigned long offset, int l) {
    int e = 1;
    int i;
	char string[256];

    printf("%#010x:%02d ", offset, l);
    for(i=0; i<l; i++) printf(" %02x", buffer[i]);
    for(i=0; i<12-l; i++) printf("   ");

    get_mnemonic_string(inst, FORMAT_INTEL, string, sizeof(string));
	snprintf(string + strlen(string), sizeof(string) - strlen(string), " ");
	e = get_operands_string(
        inst, FORMAT_INTEL, offset, string + strlen(string), sizeof(string) - strlen(string)
    );
	printf("%-30s", string);

    get_mnemonic_string(inst, FORMAT_ATT, string, sizeof(string));
	snprintf(string + strlen(string), sizeof(string) - strlen(string), " ");
	e = get_operands_string(
        inst, FORMAT_ATT, offset, string + strlen(string), sizeof(string) - strlen(string)
    );
	printf("%-30s\n", string);


    return e;
}

static char eof = 0;
BYTE get_next_byte(int argc, char *argv[]) {
    static int i = 1;
    static int j = 0;
    static char c[3];

    BYTE b = hex2int(argv[i][j]);
    b <<= 4;
    if(j++ == strlen(argv[i])-1) { i+=1; j^=j; }

    b |= hex2int(argv[i][j]);
    if(j++ == strlen(argv[i])-1) { i+=1; j^=j; }

    eof = (i == argc);

    return b;
}

#define INSTR_BUF 32
int main(int argc, char *argv[]) {
	INSTRUCTION inst;
    int i, l = INSTR_BUF;
    int offset = 0;
    int codes = 0;
    for(i=1; i<argc; i++)
        codes += strlen(argv[i]);

    if(codes % 2 == 0) {
        BYTE op;
        BYTE buffer[INSTR_BUF];
        do {
            for(i=0; i<INSTR_BUF; i++)
                if(l+i < INSTR_BUF)
                    buffer[i] = buffer[l+i];
                else if(!eof)
                    buffer[i] = get_next_byte(argc, argv);
                else
                    buffer[i] = '\0';

            if((l = get_instruction(&inst, buffer, MODE_32))) {
                print_instruction(&inst, buffer, offset, l);
                offset += l;
            } else {
                printf("INVALID: %02x\n", buffer[0]);
                l = 1;
            }
        } while(offset < codes/2);

        if(offset < codes/2)
            fprintf(stderr, "Error: Only %d of %d bytes offset\n", offset, codes/2);
        else if(offset > codes/2)
            fprintf(stderr, "Error: More bytes offset (%d) than provided (%d)\n", offset, codes/2);

    } else
        fprintf(stderr, "Invalid number of characters in the input stream.\n");

    return 0;
}
