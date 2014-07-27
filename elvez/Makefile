CFLAGS := -Wall -fPIC -pie -fstack-protector-all -Wl,-z,relro,-z,now -m32

ALL := elves maps munprotected-sex

ALL += pl_cpuid pl_cpuid-pic pl_pic pl_external
ALL += pl_munprotect pl_execve pl_argv0
ALL += pl_hadouken pl_shuriken

################################################################################
demo: all maps.cln
	@echo "Demo of germ infection..."
	-@./maps.cln; test $$? -eq 33 && echo "PASS" || echo "FAIL"

maps.cln: elves maps
	./elves maps
	chmod +x $@

################################################################################
all: $(ALL) /tmp/shellcode

pl_shuriken.h: pl_shuriken
	shell-elf2cstr $< > $@

/tmp/shellcode: pl_external
	shell-elf2asciihex $< | shell-asciihex2bin > $@

clean:
	rm -f $(ALL) *.o *.cln /tmp/shellcode maps.cln pl_shuriken.h

pl_%: %.o
	ld -e _start -melf_i386 -o $@ $<
	@chpax -m $@
%.o: %.s
	as --32 -o $@ $<

elves: elves.c pl_shuriken.h
	gcc $(CFLAGS) $< -o $@

%: %.c %.h
	gcc $(CFLAGS) $< -o $@
	@#paxctl -c -pemrxs $@

.PHONY: all clean
#.gcc  -S -masm=intel
