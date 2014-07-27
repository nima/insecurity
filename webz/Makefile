ALL := xss.swf
all: $(ALL)
	@echo "Now visit file://$(shell pwd)/index.html"

xss.swf: flash/xss.as
	mtasc -cp /usr/share/mtasc/std -swf $@ -main -header 800:600:20 $<
	@#mtasc -cp /usr/share/mtasc/std -v $< -main -out $@

clean:
	rm -f $(ALL)
