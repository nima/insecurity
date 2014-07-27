#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <string>
#include <vector>

#include <iostream>

using namespace std;

const int overhead = 5;

int matchpattern(const char * comstr, int comstrlen, int a, int b) {
    int i;
    if(b + (a - b) > comstrlen) return 0;
    for(i = 0; i < b - a; i++)
        if(comstr[a + i] != comstr[b + i])
            return 0;
    return 1;
}

void printsubstr(const char * comstr, int a, int b) {
    if(a > b) return;
    int i;
    for(i = a; i <= b; ++i)
        putchar(comstr[i]);
}

//The only C++ function because I couldn't be bothered doing memory management
typedef struct pattern {
    int fchar;
    int patlen;
    int reps;
} pattern;

vector<pattern> getnewstring(const char * comstr, int currep, int lchar, vector<pattern> repsarray, int * score) {
    // If currep is less than 0 then we finish
    if(currep < 0) {
        *score = 0;
        vector<pattern> ret;
        return ret;
    }

    pattern curpat = repsarray[currep];
    // Make the current rep fit in the last char
//    printf("#####getting pattern %i: %i, %i, %i %i ", currep, curpat.fchar, curpat.reps, curpat.patlen, lchar);
    if(lchar != 0 && curpat.fchar + curpat.reps * curpat.patlen - 1 >= lchar)
        curpat.reps = (lchar - curpat.fchar) / curpat.patlen;

    int olscore = curpat.patlen * (curpat.reps-1) - overhead;
//    printf("getting pattern %i: %i, %i, %i: %i ", currep, curpat.fchar, curpat.reps, curpat.patlen, olscore);
//    printsubstr(comstr, curpat.fchar, curpat.fchar + curpat.patlen*curpat.reps-1);
//    printf("\n");


    int bestscore = 0;
    vector<pattern> bestpats;

    int curscore;
    vector<pattern> curpats;


    if(olscore <= 0 || curpat.reps < 2)
        return getnewstring(comstr, currep - 1, lchar, repsarray, score);

    // Test for overlaps
    int i;
    for(i = currep - 1; i >= 0; --i) {
        pattern ipat = repsarray[i];
//        printf("overlap %i with %i, lchar: %i, curpat: %i %i %i, ipat: %i %i %i ", currep, i, lchar, curpat.fchar, curpat.reps, curpat.patlen, ipat.fchar, ipat.reps, ipat.patlen);
//        printsubstr(comstr, ipat.fchar, ipat.fchar + ipat.patlen*ipat.reps-1);
//        printf("\n");
        if(lchar != 0 && ipat.fchar + ipat.reps * ipat.patlen - 1 >= lchar)
            ipat.reps = (lchar - ipat.fchar) / ipat.patlen;
        if(ipat.reps < 2)
            continue;
//        printf("overlap %i with %i, ipat.reps now %i\n", currep, i, ipat.reps);
        int patlchar = ipat.fchar + ipat.reps * ipat.patlen;
        // Here we're only testing overlap.  We can test no overlap later.
        if(patlchar > curpat.fchar) {
            // Cut off the reps
            int olreps = (patlchar - curpat.fchar - 1) / curpat.patlen + 1;
            pattern newpat;
            newpat.fchar = curpat.fchar + olreps * curpat.patlen;
            newpat.patlen = curpat.patlen;
            newpat.reps = curpat.reps-olreps;
            int npscore = newpat.patlen * (newpat.reps-1) - overhead;
            if(npscore > 0)
                newpat.fchar = patlchar;
            else
                npscore = 0;
//            printf("curpat: %i %i %i, newpat: %i %i %i, score now %i, olreps %i, patlchar: %i\n", curpat.fchar,curpat.reps,curpat.patlen,newpat.fchar,newpat.reps,newpat.patlen, npscore, olreps, patlchar);
            curpats = getnewstring(comstr, i, newpat.fchar, repsarray, &curscore);
//            printf("new score %i and total %i with bestscore currently %i\n", curscore, bestscore);
            if(curscore + npscore > bestscore) {
//                printf("New overlap best score with prev best score %i, cs: %i, npscore: %i\n", bestscore, curscore, npscore);
                bestpats = curpats;
                if(npscore > 0) {
                    bestpats.push_back(newpat);
                    bestscore = curscore + npscore;
//                    printf("Adding new pat\n");
                } else {
                    bestscore = curscore;
//                    printf("Best score now is %i\n", bestscore);
                }
            }
        }
    }

    // Testing no overlap
//    printf("Testing no overlap: olscore %i bestscore %i\n", olscore, bestscore);
    curpats = getnewstring(comstr,currep - 1, curpat.fchar, repsarray, &curscore);
//    printf("curscore: %i\n", curscore);
    if(curscore + olscore > bestscore) {
        bestpats = curpats;
        bestpats.push_back(curpat);
//        printf("adding pattern %i, %i, %i: %i\n", curpat.fchar, curpat.reps, curpat.patlen, olscore);
        bestscore = curscore + olscore;
    }

    *score = bestscore;
    return bestpats;

}

int * makeNCArray(const char * comstr, int comstrlen) {
    int i;

    int * ncarray = (int *)malloc(comstrlen * sizeof(int));
    if(ncarray == NULL) exit(1);

    int lastchar[256] = {0};

    for(i = comstrlen - 1; i >= 0; --i) {
        ncarray[i] = lastchar[comstr[i]];
        lastchar[comstr[i]] = i;
    }

    return ncarray;
}

vector<pattern> compress(const char * comstr) {
    int comstrlen = strlen(comstr);
    int *ncarray = makeNCArray(comstr, comstrlen);

#ifdef DEBUG
    printf("length: %i\n", comstrlen);

    int _i;
    for(_i = 0; _i < comstrlen; ++_i)
        printf("%c: %i\n", comstr[_i], ncarray[_i]);
#endif

    int i, j;
    vector<pattern> repsarray;
    for(i = 0; i < comstrlen; ++i) {
        j = ncarray[i];
        while(j != 0) {
            if(matchpattern(comstr, comstrlen, i, j)) {
                int np = j;
                int nnp = j + (j-i);
                while(matchpattern(comstr, comstrlen, np, nnp)) {
                    np+=j-i;
                    nnp += j - i;
                }
                int reps = (nnp - i) / (j - i);
                int remreps;
                for(remreps = i; remreps < nnp; remreps += (j-i))
                    ncarray[remreps] = ncarray[np];

                pattern curpat;
                curpat.fchar = i;
                curpat.patlen = j-i;
                curpat.reps = reps;

                // Only put it in if it's worth it
                int olscore = curpat.patlen * (curpat.reps-1) - overhead;
                if(olscore > 0)
                    repsarray.push_back(curpat);
            }
            j = ncarray[j];
        }
    }

    int nreps = repsarray.size();
    for(int i = 0; i < repsarray.size(); ++i) {
        printf("Matched %i patterns at char %i:", repsarray[i].reps, repsarray[i].fchar);
        printsubstr(comstr, repsarray[i].fchar, repsarray[i].fchar + repsarray[i].patlen*repsarray[i].reps-1);
        printf("\n");
    }

    int curscore;
    vector<pattern> bestpats = getnewstring(comstr, nreps-1, 0, repsarray, &curscore);

//    printf("Best patterns with score %i\n", curscore);
//    for(int i = 0; i < bestpats.size(); ++i) {
//        printf("Matched %i patterns at char %i:", bestpats[i].reps, bestpats[i].fchar);
//        printsubstr(comstr, bestpats[i].fchar, bestpats[i].fchar + bestpats[i].patlen*bestpats[i].reps-1);
//        printf("\n");
//    }

    return bestpats;
}

void printpats(const char * comstr, vector<pattern> bestpats) {
    if(bestpats.size() == 0) {
        printf("'%s'", comstr);
        return;
    }
    pattern curpat = bestpats[0];
    pattern nextpat;
    if(curpat.fchar != 0) {
        putchar('\'');
        printsubstr(comstr, 0, curpat.fchar - 1);
        printf("'+");
    }

    int i;
    int cplchar;
    int len = strlen(comstr);
    for(i = 0; i < bestpats.size(); ++i) {
        curpat = bestpats[i];
        cplchar = curpat.fchar + curpat.patlen * curpat.reps;
        putchar('\'');
        printsubstr(comstr, curpat.fchar, curpat.fchar + curpat.patlen - 1);
        printf("'*%i", curpat.reps);
        if(i == bestpats.size() - 1) {
            if(cplchar != len) {
                printf("+'");
                printsubstr(comstr, cplchar, len - 1);
                putchar('\'');
            }
            break;
        }
        nextpat = bestpats[i+1];
        if(cplchar == nextpat.fchar)
            putchar('+');
        else {
            printf("+'");
            printsubstr(comstr, cplchar, nextpat.fchar - 1);
            printf("'+");
        }
    }
}


int main(int argc, char ** argv) {
    int e = 0;
    vector<pattern> bestpats;
    if(argc == 2) {
        bestpats = compress(argv[1]);
        printpats(argv[1], bestpats);
        printf("\n");
    } else {
        fprintf(stderr, "Usage: %s <str>\n", argv[0]);
        e=1;
    }

    /* DEBUG
      string in;
      string curline;
      while(cin) {
          getline(cin, curline);
          in += curline;
      }
      compress(in.c_str());
    */

    return e;
}
