#include <assert.h>
#include <stdio.h>
#include <string.h>

#define calc_type_size($) bits=0;while($){$<<=1;bits++;}
#define calc_assert(bits, _) assert(bits==sizeof(_)<<3) //. Note: <<3 == *8
int main() {
  int bits;

  signed long long int   slli = 1;
  signed long int         sli = 1;
  signed short int        ssi = 1;
  signed char              sc = 1;
  unsigned long long int ulli = 1;
  unsigned long int       uli = 1;
  unsigned short int      usi = 1;
  unsigned char            uc = 1;

  calc_type_size(sc);
  calc_assert(bits, sc);
  printf("       signed char: %3d bits\n", bits);

  calc_type_size(ssi);
  calc_assert(bits, ssi);
  printf("  signed short int: %3d bits\n", bits);

  calc_type_size(sli);
  calc_assert(bits, sli);
  printf("   signed long int: %3d bits\n", bits);

  calc_type_size(slli);
  calc_assert(bits, slli);
  printf("  signed long long: %3d bits\n", bits);

  calc_type_size(uc);
  calc_assert(bits, uc);
  printf("     unsigned char: %3d bits\n", bits);

  calc_type_size(usi);
  calc_assert(bits, usi);
  printf("unsigned short int: %3d bits\n", bits);

  calc_type_size(uli);
  calc_assert(bits, uli);
  printf(" unsigned long int: %3d bits\n", bits);

  calc_type_size(ulli);
  calc_assert(bits, ulli);
  printf("unsigned long long: %3d bits\n", bits);

  return 0;
}
