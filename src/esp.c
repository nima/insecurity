/* esp.c */
#include <stdio.h>

unsigned int get_sp(void) {
  __asm__("movl %esp,%eax");
  return -1; //. Should never get here.
}

int main() {
  printf("%%esp: 0x%#010x\n", get_sp());
  return 0;
}
