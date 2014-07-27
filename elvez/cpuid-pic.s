.section .text
.globl _start

_start:
    call unprotect
    jmp str

unprotect:
    popl   %ebx
    pushl  %ebx
    andl   $0xfffff000,%ebx #. Alignment to page (0x1000)
    mov    $0x100,%ecx      #. Let's unprotect a page
    mov    $0x7,%edx        #. PROT_READ|PROT_WRITE|PROT_EXEC
    mov    $0x7d,%eax       #. __NR_mprotect
    int    $0x80
    ret

cpuid:
    popl %edi             #. Move out output (defined in .data) to register %edi
    movl $0, %eax         #. Set %eax to 0 (an option for cpuid to get the vendor id)
    cpuid                 #. Make the system call to cpuid, which will output analogeous to: sprintf("%s%s%s", %ebx, %edx, %ecx)

    movl %ebx, 28(%edi)   #. Write registers to $output, at the given offsets (relative to start of output) 28, 32, 36
    movl %edx, 32(%edi)   #. Note the odd order that cpuid outputs, and that each is 4 bytes in size.
    movl %ecx, 36(%edi)   #. Also note that the 28th character is the first `x', and 39 is the last

    movl $4, %eax         #. __NR_write is 4, write this value to register %eax as usual
    movl $1, %ebx         #. for the write() system call, %ebx will be the output file descriptor (1 is stdout)
    movl %edi, %ecx       #. %ecx is the start of the output string
    movl $42, %edx        #. %edx is the strlen() of the string
    int  $0x80            #. Finally, once all registers are primed, make the system call
                            #. int generates a soft interrupt with a value of 0x80 - i.e. ask kernel to access console

    movl $1, %eax         #. __NR_exit is 1
    movl $0, %ebx         #. we want to exit with a exit status of 0 (success)
    int $0x80             #. again, interrupt the kernel

str:
    call cpuid
    .ascii "The processor vendor ID is 'xxxxxxxxxxxx'\n"
