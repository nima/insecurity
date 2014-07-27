#. AIM: To do what the following gdb command does, i.e.,
#.      disclose the name of the executed elf binary.

#. br _start
#. r
#. print "%s\n", {char*}($esp+0x04)

.section .text

.globl _start
_start:
  movl 4(%esp), %ecx   #. %ecx now has the address of argv[0]

  xor   %esi,   %esi
gay:
  movl  $4,     %eax   #. __NR_write is 4
  movl  $1,     %ebx   #. stdout
  movl  $1,     %edx   #. 1 char at a time
  int   $0x80          #. interrupt kernel
  addl  $1,     %ecx
  test  %cl, %cl
  jne   gay

  xor  %eax,    %eax   #. __NR_exit is 0
  xor  %ebx,    %ebx   #. exit code 0
  int  $0x80           #. interrupt the kernel
