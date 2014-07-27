################################################################################
.section .data
    output:
        .ascii "The processor vendor ID is 'xxxxxxxxxxxx'\n"

################################################################################
#. Here we can only define one of (main, _start), so we choose main for gcc
#. as it reuires it, without _start gas will make a best guess and write a
#. warning... alternatively, give `ld' `-e main' to tell it to use `main' and
#. not the default `_start'.  This is only usefule in our example as we want
#. the same code to be compiled by both `gcc' and `gas'.
#.
#. Another oddity is that although the program is referred to as `gas', the
#. binary is `as' - get used to it.

.section .text
#.  .globl _start
    .globl main
#.      _start:
        main:
            movl $0, %eax         #. Set %eax to 0 (an option for cpuid to get the vendor id)
            cpuid                 #. Make the system call to cpuid, which will output analogeous to: sprintf("%s%s%s", %ebx, %edx, %ecx)
            movl $output, %edi    #. Move out output (defined in .data) to register %edi
            movl %ebx, 28(%edi)   #. Write registers to $output, at the given offsets (relative to start of output) 28, 32, 36
            movl %edx, 32(%edi)   #. Note the odd order that cpuid outputs, and that each is 4 bytes in size.
            movl %ecx, 36(%edi)   #. Also note that the 28th character is the first `x', and 39 is the last

            #. Now time to make some system calls, namely write() and exit()...
            #. See /usr/include/asm-i386/unistd.h

            movl $4, %eax         #. __NR_write is 4, write this value to register %eax as usual
            movl $1, %ebx         #. for the write() system call, %ebx will be the output file descriptor (1 is stdout)
            movl $output, %ecx    #. %ecx is the start of the output string
            movl $42, %edx        #. %edx is the strlen() of the string
            int  $0x80            #. Finally, once all registers are primed, make the system call
                                    #. int generates a soft interrupt with a value of 0x80 - i.e. ask kernel to access console

            movl $1, %eax         #. __NR_exit is 1
            movl $0, %ebx         #. we want to exit with a exit status of 0 (success)
            int $0x80             #. again, interrupt the kernel
