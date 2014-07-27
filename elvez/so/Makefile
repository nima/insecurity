test: go
	export LD_LIBRARY_PATH=$(shell pwd):${LD_LIBRARY_PATH}; ./go
go: go.c libgo.so
	@#-L will add directory dir to the list of directories to be searched
	@#for -l, -l will search the library named given when linking.
	export LD_LIBRARY_PATH=$(shell pwd):${LD_LIBRARY_PATH}; gcc -L. -lgo -o $@ $<

libgo.so: fun1.o
	@#Produce a shared object which can then be linked with other objects
	@#to form an executable...
	gcc -g -Wall -shared -o $@ $^

fun1.o: fun1.c
	@#If supported for the target machine, emit position-independent
	@#code, suitable for dynamic linking and avoiding any limit on the
	@#size of the global offset table...
	gcc -g -Wall -c -fPIC $<

clean:
	rm -f *.o *.so go


pyglue: go
	./pythonCallC.py
