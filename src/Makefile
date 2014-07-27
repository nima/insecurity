
ALL := shelldisasm
all: $(ALL)
clean:
	rm -f $(ALL)

%: %.c %.h Makefile
	gcc $(CFLAGS) $< -o $@
	@#./$@ $@ || printf ""
	@#elf-check --file $@
	@#
	@paxctl -c -PEMRXS $@
	@#./$@ $@ || printf ""
	@#elf-check --file $@

.PHONY: all clean
