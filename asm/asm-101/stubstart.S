.globl _start
_start:
    #. Silvio: i think some versions of gdb dont like breakpoint at entry point.
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop
    nop

    #. Silvio: Because main() uses the frame pointer, it expects it to be set.
    #. Either use -fomit-frame-pointer, or do as follows:
    mov %esp, %ebp

    #. Finally, call main():
    call main

    #. exit(main())
    mov %eax, %ebx  #. return value from main()
    mov $1, %eax    #. syscall id for exit()
    int $0x80
