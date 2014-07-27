#include <stdio.h>
#include <unistd.h>
#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/time.h>
#include <netinet/in.h>
#include <netdb.h>

//. Default google server to send query
#define GOOGLE   "www.google.com"

//. Indentifies links in googles results
#define PATTERN  "<!--m>"

//. Show results
#define RESULTS  "<h3 class=\"r\">"

//. NULL on failure, the encoded query on success
char *encode(char *str);

//. -1 on failure, connected socket on success
int connect_me(char *dest, int port);

int grep_google(
    char *host,
    int port,
    int proxy,
    char *query,
    int mode,
    int start
);

void help(char *usage);
void header(void);

int main(int argc, char **argv) {
    int i,
        port,
        valswap,
        max = 0,
        only_results = 0,
        skip = 0,
        proxl = 0; //. greets at proxy - this variable is dedicated to you ;D

    char *host, *query = NULL;
    if(argc == 1) {
        help(argv[0]);
        return 1;
    } else for(i = 1; i < argc; i++)
        if(argv[i][0] == '-')
            switch(argv[i][1]) {
                case 'V':
                    header();
                    return 0;
                case 'r':
                    only_results = 1;
                    break;
                case 'm':
                    max = atoi(argv[++i]);
                    break;
                case 'p':
                    if((host = strchr(argv[++i], ':')) == NULL) {
                        fprintf(stderr, "illegal proxy syntax   [host:port]\n");
                        return 1;
                    }
                    port = atoi(strtok(host, ":"));
                    host = strtok(argv[i], ":");
                    proxl = 1; //. gib frei ich will rein
                    break;
                case 'h':
                    help(argv[0]);
                    return 0;
            } else query = argv[i];

    if(query == NULL) {
        fprintf(stderr, "no query!\n");
        help(argv[0]);
        return 1;
    }

    if((query = encode(query)) == NULL) {
        fprintf(stderr, "string encoding faild!\n");
        return 2;
    }

    if(!max) {
        if(grep_google(host, port, proxl, query, only_results, skip) > 0) return 0;
        else return 1;
    }

    for(i = 0; i < max;) {
        valswap = grep_google(host, port, proxl, query, only_results, skip);
        skip += 10;
        if(valswap <= 0) return 1;
        else i+=valswap;
    }
    return 0;
}

int grep_google(char *host, int port, int proxl, char *query, int mode, int skip) {
    unsigned int results = 0;
    int sockfd, nbytes, stdlen = 31, prxlen = 38+strlen(GOOGLE), buflen = 100;

    char *sendthis = NULL;
    if(proxl) {
        if((sockfd = connect_me(host, port)) == -1)    // connect to proxy
            results = -2;
        if((sendthis = (char *)malloc(prxlen+strlen(query)+7)) == NULL) {
            perror("malloc");
            results = -1;
        } else sprintf(sendthis, "GET http://%s/search?start=%d&q=%s HTTP/1.0\n\n", GOOGLE, skip, query);
    } else {
        if((sockfd = connect_me(GOOGLE, 80)) == -1)
            results = -2;
        if((sendthis = (char *)malloc(stdlen+strlen(query)+7)) == NULL) {
            perror("malloc");
            results = -1;
        } else sprintf(sendthis, "GET /search?start=%d&q=%s HTTP/1.0\n\n", skip, query);
    }

    char *readbuf = NULL;
    char *buffer = NULL;
    if(results == 0) {
        if(send(sockfd, sendthis, strlen(sendthis), 0) > 0) {
            free(sendthis);
            sendthis = NULL;
            if((readbuf = (char *)malloc(255)) != NULL) {
                if((buffer = (char *)malloc(1)) != NULL) {
                    while((results == 0) && (nbytes = read(sockfd, readbuf, 255)) > 0) {
                        if((buffer = (char *)realloc(buffer, buflen+=nbytes)) == NULL) {
                            perror("realloc");
                            results = -1;
                        } else {
                            strcat(buffer, readbuf); memset(readbuf, 0x00, 255);
                        }
                    }
                    free(readbuf);
                    readbuf = NULL;
                } else {
                    perror("malloc");
                    results = -1;
                }
            } else {
                perror("malloc");
                results = -1;
            }
        } else {
            perror("send");
            results = -3;
        }
    }
    close(sockfd);

    if(results == 0) {
        char *ptr = buffer;
        while(buflen--) {
            if(mode) {
                if(memcmp(ptr++, RESULTS, strlen(RESULTS)) == 0) {
                    ptr += strlen(RESULTS)-1;
                    while(memcmp(ptr, "&", 1) != 0) {
                        if(memcmp(ptr, "<b>", 3) == 0) ptr+=3;
                        else if(memcmp(ptr, "</b>", 4) == 0) ptr+=4;
                        else if(memcmp(ptr, "</a>", 4) == 0) break;
                        else printf("%c", *ptr++);
                    }
                    printf("</a>\n");
                    results++;
                } else continue;
                //return 0;
            } else if(memcmp(ptr++, PATTERN, strlen(PATTERN)) == 0) {
                ptr += strlen(PATTERN)-1;
                results++;
                while(memcmp(ptr, " - ", 3) && buflen--) printf("%c", *ptr++);
                printf("\n");
            }
        }

    }

    return results;
}

char *encode(char *str) {
    static char *query;
    char *ptr;
    int nlen, i;
    nlen = strlen(str)*3;
    if((query = (char *)malloc(nlen)) == NULL) {
        perror("malloc");
        return NULL;
    } else ptr = str;

    for(i = 0; i < nlen; i+=3)
        sprintf(&query[i], "%c%X", '%', *ptr++);

    query[nlen] = '\0';
    return query;
}

int connect_me(char *dest, int port) {
    int sockfd;
    struct sockaddr_in servaddr;
    struct hostent *he;

    if((sockfd = socket(AF_INET, SOCK_STREAM, 0)) == -1) {
        perror("socket");
        return -1;
    }

    if((he = gethostbyname(dest)) == NULL) {
        fprintf(stderr, "cannot resovle hostname\n");
        return -1;
    }

    servaddr.sin_addr   = *((struct in_addr *) he->h_addr);
    servaddr.sin_port = htons(port);
    servaddr.sin_family = AF_INET;

    if(connect(sockfd, (struct sockaddr *)&servaddr, sizeof(struct sockaddr)) == -1) {
        perror("connect");
        return -1;
    } else return sockfd;
}

void help(char *usage) {
    printf("%s help\n", usage);
    printf("Usage: %s <query> [options]\n");
    puts("Options:");
    puts(" -h:   this help menue");
    puts(" -p:   request google with a proxy. next argument must be the proxy");
    puts("       and the port in the following format \"host:port\"");
    puts(" -m:   next argument must be the count of results you want to see");
    puts(" -V:   prints versions info");
    puts(" -r:   prints only the results count and exit");
    puts("Examples:");
    printf("%s \"filetype:pwd inurl:service.pwd\" -r  # show results\n");
    printf("%s \"filetype:pwd inurl:service.pwd\" -m 30  # print about 30 results\n");
}

void header(void) {
    puts("\tlgool  V 0.2");
    puts("written by l0om - WWW.EXCLUDED.ORG - l0om[47]excluded[d07]org\n");
}
