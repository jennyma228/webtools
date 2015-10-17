////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2006-2009 MStar Semiconductor, Inc.
// All rights reserved.
//
// Unless otherwise stipulated in writing, any and all information contained
// herein regardless in any format shall remain the sole proprietary of
// MStar Semiconductor Inc. and be kept in strict confidence
// ("MStar Confidential Information") by the recipient.
// Any unauthorized act including without limitation unauthorized disclosure,
// copying, use, reproduction, sale, distribution, modification, disassembling,
// reverse engineering and compiling of the contents of MStar Confidential
// Information is unlawful and strictly prohibited. MStar hereby reserves the
// rights to any and all damages, losses, costs and expenses resulting therefrom.
//
////////////////////////////////////////////////////////////////////////////////
#define MAIN_C
///////////////////////////////////////////////////////////////////////////////
/// @file   main.c
/// @author MStar Semiconductor Inc.
/// @brief  main file
///////////////////////////////////////////////////////////////////////////////
//------------------------------------------------------------------------------
// Include Files
//------------------------------------------------------------------------------
//#include "MTypes.h"
#include <stdio.h>
#include <unistd.h>
#include <sys/time.h>

#include <pthread.h>
#include <semaphore.h>

//#include <sys/mman.h>// for file mmap()
#include <fcntl.h> // for file open() ...
#include <dirent.h> // for readdir()
//#include <sys/stat.h> 

//#include <dlfcn.h>

#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <sys/select.h> 
#include <arpa/inet.h>  
#include <netinet/in.h> /*for struct sockaddr_in*/
#define BACKLOG 20
#define RECV_PORT 8080
#define MAXLINE 1024*10
#define ITEM 3

#include <fcntl.h>
#include <errno.h>
#include <error.h>

#define MPOOL_NONCACHE 1
#define MPOOL_CACHE    0
#define MPOOL_MIUSEL0  0
#define MPOOL_MIUSEL1  1

#define METHOD_UNKNOWN 0
#define METHOD_GET 1
#define METHOD_HEAD 2
#define METHOD_POST 3

#ifndef UNUSED
#define UNUSED(x) x=x
#endif

#define MSG(fmt, args...)     {printf("server:");printf(fmt, ## args);}

typedef struct
{
    int tx_len;
    int tx_index;
    char tx_buf[MAXLINE];
    int rx_len;
    int rx_index;
    char rx_buf[MAXLINE];
    int distant_size;
    struct sockaddr_in distant_addr;
    int local_size;
    struct sockaddr_in local_addr;
} stTransceiverData;


typedef struct
{
    int method;
    char* path;
    char* filename;
    char* filetype;
    char realname[128];
    char* query;
    char* protocol;
    
    char* authorization;
    size_t content_length;
    char* content_type;
    char* content_type2;
    char* boundary;
    char* content_disposition;
    char* cookie;
    char* host;
    time_t if_modified_since;
    char* referer;
    char* useragent;
    char* lastmodify;
    char* server;
    char* connection;
} stParserResult;

struct timeval tv1,tv2;
//struct timezone tz1,tz2;

//printf("server %ld.%lds\n",tv1.tv_sec,tv1.tv_usec);
//printf("server %ld.%lds\n",tv2.tv_sec,tv2.tv_usec);

#define _NULL_ ((int)0)
#define _EXIT_ ((int)0xffffffff)
int  socket_flag;
pthread_cond_t pCond;
pthread_mutex_t mutex;
#define RECV_THREAD_NUM 10
struct timeval time_out;

pthread_t id_lisent,id_recv[RECV_THREAD_NUM];

stTransceiverData *TransceiverBuf = NULL;

//------------------------------------------------------------------------------
// Public Function Prototypes
//------------------------------------------------------------------------------

void _check_return(int ret,const char *s)
{
    if(ret<0)
    {
        char buf[128];
        MSG("%s NG\n",s);
        sprintf(buf,"%s NG",s);
        perror(buf);
        exit(1);
    }
    else
    {
        MSG("%s OK\n",s);
    }
}

int _read(int fd,stTransceiverData *ptr)// obtain protocol head
{
    ssize_t n = 0;
    ssize_t r = 0;

    while(1)
    {
        do{n = read(fd,&ptr->rx_buf[ptr->rx_len],sizeof(ptr->rx_buf) - 1 - ptr->rx_len - r);}
        while((errno == EINTR || errno == EAGAIN )&&(n < 0));

        if(n <= 0)
        {
            MSG("the client has been closed\n");
            break;
        }

        r += n;

        if(strstr(&ptr->rx_buf[ptr->rx_len],"\r\n\r\n") != NULL || strstr(&ptr->rx_buf[ptr->rx_len],"\n\n") != NULL)
        {
            break;
        }
    }
    
    ptr->rx_len += r;
    ptr->rx_buf[ptr->rx_len] = '\0';
    return(r);
}

char* _get_one_line(stTransceiverData *ptr)
{
    int i;
    char c;

    for(i = ptr->rx_index;ptr->rx_index < ptr->rx_len;++ptr->rx_index)
    {
        c = ptr->rx_buf[ptr->rx_index];
        if(c == '\n' || c == '\r')
        {
            ptr->rx_buf[ptr->rx_index] = '\0';
            ++ptr->rx_index;
            if(c == '\r' && ptr->rx_index < ptr->rx_len && ptr->rx_buf[ptr->rx_index] == '\n')
            {
                ptr->rx_buf[ptr->rx_index] = '\0';
                ++ptr->rx_index;
            }
            return &(ptr->rx_buf[i]);
        }
    }
    return NULL;
}

void _tx_error(int s,const char* title,const char* extra_header,const char* text)
{
    MSG("error =%s\n",title);
}

int _head_parser(stTransceiverData *ptr,stParserResult *res)
{
    char* line;
    char* point;

    // Parse the first line of the request.

    res->method = METHOD_UNKNOWN;
    res->path = (char *)&"";
    res->filename = (char *)&"";
    res->filetype = (char *)&"";
    res->realname[0] = '\0';
    res->query = (char *)&"";
    res->protocol = (char *)&"";
    
    res->authorization = (char *)&"";
    res->content_length = -1;
    res->content_type = (char *)&"";
    res->content_type2 = (char *)&"";
    res->boundary = (char *)&"";
    res->content_disposition = (char *)&"";
    res->cookie = (char *)&"";
    res->host = (char *)&"";
    res->if_modified_since = (time_t) -1;
    res->referer = (char *)&"";
    res->useragent = (char *)&"";
    res->connection = (char *)&"";

    line = _get_one_line(ptr);
    if(line == NULL)
    {
        MSG("parser error =%d\n",__LINE__);
        _tx_error(400,"Bad Request","","Can't parse request.");
        return 0;
    }

    if(strncasecmp(line,"GET",3) == 0)
    {
        res->method = METHOD_GET;
    }
    else if(strncasecmp(line,"HEAD",4) == 0)
    {
        res->method = METHOD_HEAD;
    }
    else if(strncasecmp(line,"POST",4) == 0)
    {
        res->method = METHOD_POST;
    }
    else
    {
        res->method = METHOD_UNKNOWN;
        _tx_error(501,"Not Implemented","","That method is not implemented.");
        return 0;
    }

    point = strpbrk(line," \t\n\r");
    if(point == NULL)
    {
        MSG("parser error =%d\n",__LINE__);
        _tx_error(400,"Bad Request","","Can't parse request.");
        return 0;
    }
    *point++ = '\0';

    point += strspn(point," \t\n\r" );
    res->path = point;

    if(res->path[0] != '/')
    {
        MSG("parser error =%d\n",__LINE__);
        _tx_error(400,"Bad Request","","Bad filename.");
        return 0;
    }
    
    point = strpbrk(point," \t\n\r" );
    if(point == NULL)
    {
        MSG("parser error =%d\n",__LINE__);
        _tx_error(400,"Bad Request","","Can't parse request.");
        return 0;
    }
    *point++ = '\0';
    
    point += strspn(point," \t\n\r");
    res->protocol = point;
    
//----------------------------------------------------------
    if(res->method == METHOD_GET)
    {
        point = strchr(res->path,'?');
        if(point != NULL)
        {
            res->query = point;
            *res->query++ = '\0';
        }
    
        for(point = &res->path[strlen(res->path)];*point != '/';point--) ;
        res->filename  = point + 1;
    
        point = strrchr(res->filename, '.');
        if(point != NULL)
        {
            res->filetype = ++point;
        }
    
        sprintf(res->realname,"%s","../www");
        if((strlen(res->path) + 6) > (sizeof(res->realname)-1))
        {
            MSG("parser error =%d\n",__LINE__);
            _tx_error(400,"Bad Request","","Filename too long.");
            return 0;
        }
        if(strcmp(res->path,"/") == 0)
            strcat(res->realname,"/index.html");
        else
            strcat(res->realname,res->path);
    }
    else if(res->method == METHOD_POST)
    {
        // Parse the the request headers.
        while((line = _get_one_line(ptr)) != NULL)
        {
            if(line[0] == '\0')
                break;
            #if 0
            else if(strncasecmp(line,"Authorization:",14) == 0)
            {
                point = &line[14];
                point += strspn(point, " \t");
                res->authorization = point;
            }
            #endif
            else if(strncasecmp(line,"Content-Length:",15) == 0)
            {
                point = &line[15];
                point += strspn(point, " \t");
                res->content_length = atol(point);
            }
            else if(strncasecmp(line,"Content-Type:",13) == 0)
            {
                point = &line[13];
                point += strspn(point, " \t");
                res->content_type = point;
            }
            #if 0
            else if(strncasecmp(line,"Cookie:",7) == 0)
            {
                point = &line[7];
                point += strspn(point, " \t");
                res->cookie = point;
            }
            else if(strncasecmp(line,"Host:",5) == 0)
            {
                point = &line[5];
                point += strspn(point, " \t");
                res->host = point;
                if(strchr(res->host, '/') != (char*) 0 || res->host[0] == '.')
                {
                    MSG("parser error =%d\n",__LINE__);
                    _tx_error(400,"Bad Request","","Can't parse request.");
                    return 0;
                }
            }
            else if(strncasecmp(line,"If-Modified-Since:",18) == 0)
            {
                point = &line[18];
                point += strspn(point, " \t");
                //res->if_modified_since = tdate_parse(point);
            }
            else if(strncasecmp(line,"Referer:", 8) == 0)
            {
                point = &line[8];
                point += strspn(point, " \t");
                res->referer = point;
            }
            else if(strncasecmp(line,"User-Agent:", 11) == 0)
            {
                point = &line[11];
                point += strspn(point, " \t");
                res->useragent = point;
            }
            else if(strncasecmp(line,"Last-Modified:", 14) == 0)
            {
                point = &line[14];
                point += strspn(point, " \t");
                res->lastmodify = point;
            }
            else if(strncasecmp(line,"Connection:", 11) == 0)
            {
                point = &line[11];
                point += strspn(point, " \t");
                res->connection = point;
            }
            #endif
        }
        
        if((point = strstr(res->content_type,"boundary")) != NULL)
        {   
            res->boundary = point+9;
        }
    }
    
    MSG("method =%d\n",res->method);
    if(res->method == METHOD_GET)
    {
        MSG("path =%s\n",res->path);
        MSG("file =%s\n",res->filename);
        MSG("type =%s\n",res->filetype);
        MSG("realname =%s\n",res->realname);
        MSG("query =%s\n",res->query);
        MSG("protocol =%s\n",res->protocol);
    }
    else if(res->method == METHOD_POST)
    {
        MSG("content_length =%d\n",(int)res->content_length);
        MSG("content_type =%s\n",res->content_type);
        MSG("boundary =%s\n",res->boundary);
        MSG("host =%s\n",res->host);
        MSG("useragent =%s\n",res->useragent);
        MSG("connection =%s\n",res->connection);
    }

    return 1;
}

int _post_parser(stTransceiverData *ptr,stParserResult *res)
{
    char* line;
    char* point;

    // Parse the first line of the request.

    if(res->method != METHOD_POST)
        return 0;

    while((line = _get_one_line(ptr)) != NULL)
    {
        if(line[0] == '\0')
            break;
        else if(strncasecmp(line,"Content-Disposition:",20) == 0)
        {
            point = &line[20];
            point += strspn(point, " \t");
            res->content_disposition = point;
        }
        else if(strncasecmp(line,"Content-Type:",13) == 0)
        {
            point = &line[13];
            point += strspn(point, " \t");
            res->content_type2 = point;
        }
    }

    if((point = strcasestr(res->content_disposition,"filename")) != NULL)
    {   
        res->filename = point+9;
        if((point = strchr(res->filename,'"')) != NULL)
        {
            char *point2;
            point++;
            if((point2 = strchr(point,'"')) != NULL)
            {
                for(point = point2;((*point != '\\') && (point != res->filename));point--) ;
                point++;
                if((point2-point) < (int)sizeof(res->realname))
                {
                    memcpy((void*)res->realname,(void*)point,point2-point);  
                    res->realname[point2-point] = '\0';
                }
            }
        }
    }

    MSG("path =%s\n",res->path);
    MSG("file =%s\n",res->filename);
    MSG("type =%s\n",res->filetype);
    MSG("realname =%s\n",res->realname);
    MSG("content_type2 =%s\n",res->content_type2);
    MSG("content_disposition =%s\n",res->content_disposition);
    //MSG("post data =%s\n",&ptr->rx_buf[ptr->rx_index]);

    return 1;
}

int _is_picture(char *type)
{
    if(type == NULL)
        return 0;
    
    if (strcmp(type,"jpg") == 0 ||
        strcmp(type,"png") == 0 ||
        strcmp(type,"gif") == 0)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}
int _is_video(char *type)
{
    if(type == NULL)
        return 0;
    
    if (strcmp(type,"wmv") == 0 ||
        strcmp(type,"avi") == 0 ||
        strcmp(type,"rmvb") == 0 ||
        strcmp(type,"rm") == 0 ||
        strcmp(type,"mp4") == 0)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

int _is_audio(char *type)
{
    if(type == NULL)
        return 0;
    
    if (strcmp(type,"mp3") == 0 ||
        strcmp(type,"wav") == 0)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

int _is_js(char *type)
{
    if(type == NULL)
        return 0;
    
    if (strcmp(type,"js")==0)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}


void* pthread_recv_socket(void *args)
{
    int connect_sock;
    stTransceiverData *pTRbuf = (stTransceiverData *)args;

    pthread_detach(pthread_self());
    MSG("pthread_recv_socket %d is start!\n",(int)pthread_self());
    
    while (1)
    {
        pthread_mutex_lock (&mutex);
        {
            while (socket_flag == _NULL_)
                pthread_cond_wait (&pCond, &mutex);
            connect_sock = socket_flag;
            
            if(socket_flag != _EXIT_)
                socket_flag = _NULL_;
        }
        pthread_mutex_unlock (&mutex);

        if(connect_sock == _EXIT_)
            break;
        //MSG("pthread_recv_socket %d is running!\n",(int)pthread_self());

        fcntl(connect_sock,F_SETFL,O_NONBLOCK);
        
        pTRbuf->rx_index = 0;
        pTRbuf->rx_len = 0;
        int res = _read(connect_sock,pTRbuf);
        if(res >0)
        {
            MSG("***************************************************************************1\n\n\n");
            MSG("recv head=%s\n",pTRbuf->rx_buf);
            stParserResult result;
            
            _head_parser(pTRbuf,&result);
            
            if(result.method == METHOD_GET)
            {   
                int fd;
                if(result.query[0] == '\0')
                {
                    MSG("open file %s\n",result.realname);
                    
                    if ((fd = open(result.realname,O_RDONLY)) < 0)     // file not exist
                    {
                        MSG("file not exist\n");
                        char temp[]="HTTP/1.1 501 Not Found\r\nContent-Type:text/html\r\n\r\n<html><body>Request file not found!</body></html>";
                        write(connect_sock,temp,strlen(temp));
                    }
                    else            // file exist
                    {
                        int n;
                        if (_is_picture(result.filetype))        // file is a pitcure file
                        {
                            char head[1024];
                            MSG("file is a pitcure file\n");
                            sprintf(head,"HTTP/1.1 200 OK\r\nContent-Type:image/%s\r\n\r\n",result.filetype);
                            write(connect_sock,head,strlen(head));
                            while ((n = read(fd,pTRbuf->tx_buf,MAXLINE)) > 0)
                                write(connect_sock,pTRbuf->tx_buf,n);
                        }else if (_is_video(result.filetype))        // file is a video file 
                        {
                            char head[1024];int totle=0; struct stat fileStat;
                            MSG("file is a video file\n");
                            sprintf(head,"HTTP/1.1 200 OK\r\nContent-Type:video/%s\r\n\r\n",result.filetype);
                            write(connect_sock,head,strlen(head));
                            fileStat=stat(result.realname, &);
                            MSG("filessize=%d\n",(int)fileStat.st_size);
                            while ((n = read(fd,pTRbuf->tx_buf,MAXLINE)) > 0){
                                int w;
                                w=write(connect_sock,pTRbuf->tx_buf,n);
                                if(w<0){
                                  lseek(fd,-n, SEEK_CUR);
                                  continue;
                                }if (w!=n){
                                  lseek(fd,-(n-w), SEEK_CUR);
                                }
                                totle+=w;
                                printf("[%d]*********************************\n",totle);
                            }
                        }else if (_is_audio(result.filetype))        // file is a audio file 
                        {
                            char head[1024];
                            MSG("file is a audio file\n");
                            sprintf(head,"HTTP/1.1 200 OK\r\nContent-Type:audio/%s\r\n\r\n",result.filetype);
                            write(connect_sock,head,strlen(head));
                            while ((n = read(fd,pTRbuf->tx_buf,MAXLINE)) > 0)
                                write(connect_sock,pTRbuf->tx_buf,n);
#if 1
                        }else if (_is_js(result.filetype))        // file is a js file 
                        {
                            char head[1024];
                            MSG("file is a js file\n");
                            sprintf(head,"HTTP/1.1 200 OK\r\nContent-Type:%s\r\n\r\n","application/x-javascript");
                            write(connect_sock,head,strlen(head));
                            while ((n = read(fd,pTRbuf->tx_buf,MAXLINE)) > 0)
                                write(connect_sock,pTRbuf->tx_buf,n);
#endif
                        }
                        else        // file is not a picture,deal it as html file type  
                        {
                            MSG("file is not a picture,deal it as html file type\n");
                            char head[]="HTTP/1.1 200 OK\r\nContent-Type:text/html\r\n\r\n";
                            write(connect_sock,head,strlen(head));
                            while ((n = read(fd,pTRbuf->tx_buf,MAXLINE)) > 0)
                                write(connect_sock,pTRbuf->tx_buf,n);
                        }// end read file
                        close(fd);
                    }// end open file
                }
                else
                {
                    //TV control
                    MSG("TV control\n");
                }
            }
            else if(result.method == METHOD_POST)
            {
                MSG("post\n");
                if(pTRbuf->rx_index == pTRbuf->rx_len)
                    res = _read(connect_sock,pTRbuf);
                _post_parser(pTRbuf,&result);
        
                FILE* fp = fopen(result.realname, "w");
                if(fp == NULL)
                {
                    perror("open uploadfile:");
                }
        
                int len = pTRbuf->rx_len - pTRbuf->rx_index;
                char *point;
        
                if(len == 0)
                {
                    len = read(connect_sock,&pTRbuf->rx_buf[pTRbuf->rx_len],sizeof(pTRbuf->rx_buf) - 1 - pTRbuf->rx_len);
                    MSG("read = %d\n",len);
                    pTRbuf->rx_len += len;
                    pTRbuf->rx_buf[pTRbuf->rx_len] = '\0';
                }
                
                len -= strlen(result.boundary);
                
                do
                {
                    if((point = strstr(&pTRbuf->rx_buf[pTRbuf->rx_index],result.boundary)) != NULL)
                    {
                        if((*(point-3) == '\n')||(*(point-3) == '\r'))
                        {
                            point -= 3;
                            *point = '\0';
                        }
                        if((*(point-1) == '\n')||(*(point-1) == '\r'))
                        {
                            point--;
                            *point = '\0';
                        }
                        
                        len = point - &pTRbuf->rx_buf[pTRbuf->rx_index];
                        fwrite(&pTRbuf->rx_buf[pTRbuf->rx_index],sizeof(char),len,fp);
                        MSG("len = fileend\n");
                        break;
                    }
                    else
                    {
                        len = fwrite(&pTRbuf->rx_buf[pTRbuf->rx_index],sizeof(char),len,fp);
                        memmove(&pTRbuf->rx_buf[pTRbuf->rx_index],&pTRbuf->rx_buf[pTRbuf->rx_index+len],pTRbuf->rx_len - pTRbuf->rx_index - len+1);
                        pTRbuf->rx_len -= len;
                    }
        
                    int i;
                    for(len = 0,i = 0;(len == 0)&&(i < 3);i++)
                    {
                        len = read(connect_sock,&pTRbuf->rx_buf[pTRbuf->rx_len],sizeof(pTRbuf->rx_buf) - 1 - pTRbuf->rx_len);
                        if(len <= 0)
                            MSG("len = [%d][%d]\n",len,i);
                    }
                    pTRbuf->rx_len += len;
                    pTRbuf->rx_buf[pTRbuf->rx_len] = '\0';
                } while(len > 0);
                
                fclose(fp);
                write(connect_sock,"HTTP/1.1 200 OK\r\n",17);
                write(connect_sock,"Content-Type:text/html\r\n",24);
                write(connect_sock,"\r\n",2);
                write(connect_sock,"<html><body>UPLOAD OK!</body></html>",37);
            }
            else
            {
                char temp[]="HTTP/1.1 501 Not Implemented\r\nContent-Type:text/html\r\n\r\n<html><body>That method is not implemented.</body></html>";
                write(connect_sock,temp,strlen(temp));
            }
        }
        res = close(connect_sock);
        _check_return(res,"close connect socket");
        MSG("***************************************************************************2\n\n");
    }
    
    return NULL;
}

void* pthread_lisent_socket(void *args)
{
    stTransceiverData *pTRbuf = (stTransceiverData *)args;
    
    pthread_detach(pthread_self());
    MSG("pthread_lisent_socket[%d] is running\n",(int)pthread_self());

    int listen_sock = socket(pTRbuf->local_addr.sin_family,SOCK_STREAM,0);//creat socket
    _check_return(listen_sock,"creat socket");

    // Init sockaddr_in 
    int opt = 1;
    setsockopt(listen_sock,SOL_SOCKET,SO_REUSEADDR,&opt,sizeof(&opt));

    int res = bind(listen_sock,(struct sockaddr*)&(pTRbuf->local_addr),(socklen_t)pTRbuf->local_size);// bind the socket to this port
    _check_return(res,"bind socket");

    res = listen(listen_sock,BACKLOG);  //listening, waiting for connect. BACKLOG     
    _check_return(res,"listen client");

    fd_set rset;
    while(1)
    {
        if(socket_flag == _EXIT_)
            break;
        
        //accept connection
        FD_ZERO(&rset);
        FD_SET(listen_sock,&rset);
        time_out.tv_sec = 1;time_out.tv_usec = 0;
        res = select(listen_sock + 1,&rset,NULL,NULL,&time_out);

        if(res == 0)//time out
        {
            //MSG("time out,try again!\n");
            sleep(1);
            continue;
        }
        else if(FD_ISSET(listen_sock,&rset))//accept
        {
            int connect_sock = accept(listen_sock,(struct sockaddr*)&(pTRbuf->distant_addr),(socklen_t *)&(pTRbuf->distant_size));
            MSG("distant IP : %s\n",inet_ntoa(pTRbuf->distant_addr.sin_addr));
            MSG("distant port : %d\n",pTRbuf->distant_addr.sin_port);
            
            pthread_mutex_lock(&mutex);
            if( socket_flag == _NULL_)
            {
                socket_flag = connect_sock;
                pthread_cond_signal(&pCond);
            }
            else
            {
                close(connect_sock);
                printf(".\n");
            }
            pthread_mutex_unlock(&mutex);
            MSG("***************************************************************************0\n\n");
        }
        else if ((errno == ECONNABORTED) || (errno == EINTR))
        {
            MSG("lisent error,try again!\n");
            continue;
        }
        else
        { 
            MSG("lisent error,exit!\n");
            break;; 
        }
    }
    res = close(listen_sock);
    _check_return(res,"close listen socket");

    MSG("recv data:%s\n",pTRbuf->rx_buf);
    
    return NULL;
}

void httpd_start(void)
{
    pthread_mutex_init(&mutex, NULL);
    pthread_cond_init(&pCond, NULL);
    socket_flag = _NULL_;
    TransceiverBuf = (stTransceiverData *)malloc(sizeof(stTransceiverData));

    bzero(&TransceiverBuf->local_addr,sizeof(struct sockaddr));       // fill in sockaddr_in
    TransceiverBuf->local_addr.sin_family = AF_INET;                  //creat socket AF_INET AF_UNIX
    TransceiverBuf->local_addr.sin_addr.s_addr = htonl(INADDR_ANY);  // dest IP address
    TransceiverBuf->local_addr.sin_port = htons(RECV_PORT);           // dest port
    TransceiverBuf->local_size = sizeof(struct sockaddr);
    TransceiverBuf->distant_size = 0;

    #if 0
    sigset_t signal_mask;
    sigemptyset (&signal_mask);
    sigaddset (&signal_mask, SIGPIPE);
    int rc = pthread_sigmask (SIG_BLOCK,&signal_mask,NULL);
    if(rc != 0)
    {
        printf("block sigpipe error/n");
    }
    #endif
    
    pthread_create(&id_lisent,NULL,pthread_lisent_socket,(void *)TransceiverBuf);
    
    int i;
    for(i = 0; i < RECV_THREAD_NUM;i++)
    {
        pthread_create(&id_recv[i],NULL,pthread_recv_socket,(void *)TransceiverBuf);
    }
}

void httpd_stop(void)
{
    pthread_mutex_lock(&mutex);
    socket_flag = _EXIT_;
    pthread_cond_broadcast(&pCond);
    pthread_mutex_unlock(&mutex);
    int i;void *tret;
    
    for(i = 0; i < RECV_THREAD_NUM;i++)
    {
        pthread_join(id_recv[i],&tret);
    }
    
    pthread_join(id_lisent,&tret);
    
    if(TransceiverBuf != NULL)
        free(TransceiverBuf);
}

int main(int argc, char** argv)
{
    UNUSED(argc);
    UNUSED(argv);

    MSG("Initialize finished\n");
    
    httpd_start();
    //sleep(60*5);
    while(1){;}
    httpd_stop();
    //-----------------------------------------------------
    MSG("Program Terminated Normally.\n");
    return 0;
}

#undef MAIN_C

