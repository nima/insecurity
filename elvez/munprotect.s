.section .text
.globl main
main:
    call   unprotect

unprotect:
    popl   %ebx
    pushl  %ebx
    subl   $0x5,%ebx      #. Start of .text, which is %edx - 5 bytes for CALL
    mov    $0x1000,%ecx   #. Let's unprotect a page
    mov    $0x7,%edx      #. PROT_READ|PROT_WRITE|PROT_EXEC
    mov    $0x2e,%eax     #. __NR_mprotect
    int    $0x80
    ret
