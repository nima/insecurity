ElfElement *$$(ElfObject, sh_append) {
    ElfElement *e = malloc(sizeof(ElfElement));
    e->class = ElfSh;
    e->next = NULL;
    for(e->last=self->sh; e->last->next; e->last = e->last->next);
    e->last->next = e;
    e->index = self->sh_count++;
    e->size = sizeof(Elf32_Shdr);
    self->eh->e_shnum++;

    e->sh = malloc(e->size); {
        Elf32_Shdr sh;
        memcpy(e->sh, &sh, sizeof(Elf32_Shdr));

        e->sh->sh_type = SHT_PROGBITS;
        e->sh->sh_offset = 0x2000;
        e->sh->sh_addr = 0x3000;
        e->sh->sh_link = 0x000;
        e->sh->sh_info = 0x000;
        e->sh->sh_addralign = 0x1;
        e->sh->sh_flags = 0x2;
        e->sh->sh_name = 0x35;
        e->sh->sh_entsize = 0;

        e->sh->sh_size = 0;
        e->raw = NULL;
        e->offset = self->eh->e_shoff + e->index * self->eh->e_shentsize;
    }
    return e;
}
