#. AIM: Don't want to rely on a .data segment, or any other absolute address.

.section .text

.globl _start
_start:
  jmp save_output_str

back_to_code:
  popl %ecx             #. %ecx now has the address of the string

  xor  %eax, %eax
  mov  $4, %al          #. __NR_write is 4, write this value to register %eax as usual
  xor  %ebx, %ebx
  mov  $1, %bl          #. for the write() system call, %ebx will be the output file descriptor (1 is stdout)
  xor  %edx, %edx
  mov  $42, %dl         #. %edx is the strlen() of the string
  int  $0x80            #. Finally, once all registers are primed, make the system call
                        #. int generates a soft interrupt with a value of 0x80 - i.e. ask kernel to access console

  xor  %eax, %eax
  mov  $1, %al          #. __NR_exit is 1
  xor  %ebx, %ebx       #. we want to exit with a exit status of 0 (success)
  int  $0x80            #. again, interrupt the kernel

save_output_str:
  call back_to_code
  .string "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\n"
