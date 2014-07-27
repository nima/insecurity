#. vim: set noic
ALL     := one one_asm one_asmdbg one_nsl one_nsldbg
CFLAGS  := -O0 -nostdlib -fverbose-asm # -O0 -nostdlib -fverbose-asm -e main
DFLAGS  := -gstabs
ASFLAGS :=
LDFLAGS :=

################################################################################
#. Force 32-bit for 64-bit machines
CFLAGS  += -m32
ASFLAGS += --32
LDFLAGS += -m elf_i386

################################################################################
all: $(ALL)

clean:
	-rm -f core* *.o $(ALL) *.s

.PRECIOUS: stubstart.S
.DEFAULT: all
.PHONY: clean all
################################################################################
one: one.c
	gcc -O0 $< -o $@

################################################################################
one_asm: one.c stubstart.S
	##### Building $@ #####
	gcc $(CFLAGS) $< -S -masm=intel
	mv one.s $@.s
	as $(ASFLAGS) stubstart.S $@.s -o $@.o
	ld $(LDFLAGS) $@.o -e _start -o $@

one_asmdbg: one.c stubstart.S
	##### Building $@ #####
	gcc $(CFLAGS) $< -S -masm=intel $(DFLAGS)
	mv one.s $@.s
	as $(ASFLAGS) stubstart.S $@.s -o $@.o
	ld $(LDFLAGS) $@.o -e _start -o $@


################################################################################
one_nsl: one.c stubstart.S
	##### Building $@ #####
	gcc $(CFLAGS) $^ -o $@

one_nsldbg: one.c stubstart.S
	##### Building $@ #####
	gcc $(CFLAGS) $^ -o $@ $(DFLAGS)

