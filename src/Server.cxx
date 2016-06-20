#include <errno.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <pthread.h>
#include <string>
#include <sstream>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <map>
#include "freepaneld.h"
#include "Server.h"
#include "common.h"
#include "Handler.h"
#include "HttpPacket.h"
#include "util/URLEncoder.h"

DECLARE_FP_LOGGER()

///////////////////////////////////////////////////////////////////////////////
// HttpPacketImpl class

typedef struct std::map<std::string, std::string> SSMAP;

class HttpPacketImpl : virtual public HttpPacket
{
public:
    const char *GetHeader(const char *name) const override
    {
        auto found = m_Headers.find(name);
        if (found == m_Headers.end())
            return nullptr;
        return found->second.c_str();
    }

    void SetHeader(const char *name, const char *value) override
    {
        m_Headers[name] = value;
    }

protected:
    SSMAP m_Headers;

};

///////////////////////////////////////////////////////////////////////////////
// HttpRequestImpl class

class HttpRequestImpl : public HttpPacketImpl, virtual public HttpRequest
{
public:
    HttpRequestImpl(const char *method, const char *requestUrl)
    {
        SetMethod(method);
        const char *tmp = strchr(requestUrl, '?');
        if (tmp) {
            SetQueryString(tmp + 1);
            m_RequestURI.assign(requestUrl, tmp - requestUrl);
        } else {
            m_RequestURI.assign(requestUrl);
        }
    }

    void SetHeader(const char *name, const char *value) override
    {
        HttpPacketImpl::SetHeader(name, value);
    }

    const char *GetMethod() const override
    {
        return m_Method.c_str();
    }

    void SetMethod(const char *method)
    {
        m_Method = method;
    }

    const char *GetQueryString() const override
    {
        return m_QueryString.c_str();
    }

    void SetQueryString(const char *queryString)
    {
        if (queryString) {
            m_QueryString = queryString;
            ParseQueryString(queryString);
        }
    }

    const char *GetParameter(const char *name, const char *defaultValue) const override
    {
        auto found = m_Parameters.find(name);
        if (found == m_Parameters.end())
            return defaultValue;
        return found->second.c_str();
    }

    const char *GetRequestURI() const override
    {
        return m_RequestURI.c_str();
    }

    void SetRequestURI(const char *requestURI)
    {
        m_RequestURI = requestURI;
    }

private:
    void ParseQueryString(const char *queryString)
    {
        char *pszQueryString = strdup(queryString);
        
        size_t decodedLen = 1024;
        char *pDecodedStr = (char *)malloc(decodedLen);

        for (char *savedPtr, *p = strtok_r(pszQueryString, "&", &savedPtr); p; p = strtok_r(nullptr, "&", &savedPtr))
        {
            char *value = strchr(p, '=');
            *value = 0;
            ++value;

            int maxNeed = strlen(value);
            if (maxNeed > decodedLen) {
                decodedLen = maxNeed;
                pDecodedStr = (char *)realloc(pDecodedStr, decodedLen + 1);
            }
            URLEncoder::Decode(value, pDecodedStr);
            m_Parameters[p] = pDecodedStr;
        }
        free(pDecodedStr);
        free(pszQueryString);
    }

    std::string m_Method;
    std::string m_QueryString;
    std::string m_RequestURI;
    SSMAP m_Parameters;

};

///////////////////////////////////////////////////////////////////////////////
// HttpResponseImpl class

class HttpResponseImpl : public HttpPacketImpl, virtual public HttpResponse
{
public:
    HttpResponseImpl()
        : m_StatusCode(200)
    {
    }

    void SetIntHeader(const char *name, int value) override
    {
        std::stringstream buffer;
        buffer << value;
        HttpPacketImpl::SetHeader(name, buffer.str().c_str());
    }

    void SetContentLength(int length) override
    {
        SetIntHeader("Content-Length", length);
    }

    void SetContentType(const char *type) override
    {
        auto found = m_Headers.find("Content-Type");
        if (found != m_Headers.end()) {
            std::string& value = found->second;
            size_t pos  = value.find("charset=");
            if (pos != std::string::npos) {
                // set charset already
                std::string charset(value.substr(pos));
                value = type;
                value.append(";charset=");
                value.append(charset);
            } else {
                value = type;
            }
        } else {
            SetHeader("Content-Type", type);
        }
    }

    void SetCharacterEncoding(const char *charset) override
    {
        auto found = m_Headers.find("Content-Type");
        if (found != m_Headers.end()) {
            std::string& value = found->second;
            if (!value.empty()) {
                size_t pos = value.find("charset=");
                if (pos != std::string::npos) {
                    // set MIME already
                    std::string mime(value.substr(0, pos - 1));
                    value = mime;
                }
            } else {
                value = "text/plain";
            }
            value.append(";charset=");
            value.append(charset);
        } else {
            std::string value("text/plain;charset=");
            value.append(charset);
            SetHeader("Content-Type", value.c_str());
        }
    }

    void Write(const char *data, int length = -1) override
    {
        if (length == -1)
           length = strlen(data);
        m_EntityBuffer.write(data, length);
    }

    void SetStatus(int statusCode) override
    {
        m_StatusCode = statusCode;
    }

    void SendTo(int clientSocket)
    {
        SetHeader("Connection", "close");
        std::string content(m_EntityBuffer.str());
        SetContentLength(content.size());
        std::stringstream buffer;
        buffer << "HTTP/1.1 ";
        buffer << m_StatusCode;
        if (m_StatusCode >= 200 && m_StatusCode < 300)
            buffer << " OK";
        else if (m_StatusCode >= 400 && m_StatusCode < 500)
            buffer << " BAD REQUEST";
        else
            buffer << "Internal Server Error";
        buffer << "\r\n";
        for (auto& item : m_Headers) {
            buffer << item.first << ": " << item.second << "\r\n";
        }
        buffer << "\r\n";
        buffer << content;
        std::string response(buffer.str());
        send(clientSocket, response.c_str(), response.size(), 0);
    }

private:
    std::stringstream m_EntityBuffer;
    int m_StatusCode;

};

////////////////////////////////////////////////////////////////////////////////
// Server class

int get_line(int sock, char *buf, int size)
{
    int i = 0;
    char c = '\0';
    int n;

    while ((i < size - 1) && (c != '\n'))
    {
        n = recv(sock, &c, 1, 0);
        /* DEBUG printf("%02X\n", c); */
        if (n > 0)
        {
            if (c == '\r')
            {
                n = recv(sock, &c, 1, MSG_PEEK);
                /* DEBUG printf("%02X\n", c); */
                if ((n > 0) && (c == '\n'))
                  recv(sock, &c, 1, 0);
                else
                  c = '\n';
            }
            buf[i] = c;
            i++;
        }
        else
          c = '\n';
    }
    buf[i] = '\0';
    return(i);
}

Server::Server()
{
}


Server::~Server()
{
}


int Server::Run(u_short port)
{
    u_short listenPort = port;
    int listenSocket = Start(listenPort);
    FPLOG_INFO("freepaneld running on port port " << listenPort)
    while (true) {
        struct sockaddr_in clientName;
        unsigned int clientNameSize = sizeof(clientName);
        intptr_t clientSocket = accept(listenSocket, (struct sockaddr *)&clientName, &clientNameSize);
        if (clientSocket == -1)
            die_error("accept error");
        pthread_t t;
        Connection *pConnection = (Connection *)malloc(sizeof(Connection));
        pConnection->server = this;
        pConnection->clientSocket = clientSocket;
        if (pthread_create(&t, NULL, Server::StartHandleConnection, pConnection) != 0)
            die_error("pthread_create error");
    }
    close(listenSocket);
    return 0;
}


void *Server::StartHandleConnection(void *connection)
{
    void *result = NULL;
    if (connection) {
        Connection *p = (Connection *)connection;
        result = p->server->HandleConnection(p->clientSocket);
        free(connection);
    }
    return result;
}


void *Server::HandleConnection(int client)
{
    char buffer[2048];
    size_t length;
    char method[8];
    char url[1024];
    char *queryString = NULL;

    length = get_line(client, buffer, sizeof(buffer));

    const char *pCur = buffer;
    const char *pEnd = pCur + length;
    memset(method, 0, sizeof(method));
    for (int i = 0; !isspace(*pCur) && i < sizeof(method) - 1; ++i, ++pCur) {
        method[i] = *pCur;
    }
    
    if (strcasecmp(method, "GET") && strcasecmp(method, "POST")) {
        // TODO: unimplemented(client);
        FPLOG_ERROR("Error method: " << method);
        return NULL;
    }

    pCur = skip_space(pCur, pEnd);
    memset(url, 0, sizeof(url));
    for (int i = 0; i < sizeof(url) - 1 && !isspace(*pCur) && pCur < pEnd; ++pCur, ++i) {
        url[i] = *pCur;
    }

    if (strcasecmp(method, "GET") == 0) {
        queryString = strchr(url, '?') + 1;
    }
    printf("%s - %s\n", method, url);
    
    HttpRequestImpl request(method, url);
    // parse header
    std::map<std::string, std::string> headers;
    while ((length = get_line(client, buffer, sizeof(buffer))) > 0 && strcmp("\n", buffer)) {
        char *value = strchr(buffer, ':');
        *value = 0;
        value = const_cast<char *>(skip_space(++value));
        value = rtrim(value);
        request.SetHeader(buffer, value);
    }
    if (strcasecmp(method, "GET") == 0) {
    } else if (strcasecmp(method, "POST") == 0) {
        const char *contentLengthStr = request.GetHeader("Content-Length");
        if (contentLengthStr) {
            int contentLength = atoi(contentLengthStr);
            int read = 0;
            if (contentLength >= 0) {
                while (read < contentLength && (length = get_line(client, buffer, contentLength + 1))) {
                    request.SetQueryString(buffer);
                    read += length;
                }
            }
        }
    }
    Handler *pHandler = m_HandlerFactory.CreateHandler(request.GetRequestURI());
    HttpResponseImpl response;
    pHandler->OnRequest(request, response);
    response.SendTo(client);
    delete pHandler;
    close(client);
}


void Server::DispatchRequest(int clientSocket, const char *method, const char *url, const char *queryString)
{
}


int Server::Start(u_short& port)
{
    int httpd = 0;
    struct sockaddr_in name;
    
    httpd = socket(PF_INET, SOCK_STREAM, 0);
    if (httpd == -1) {
        die_error("socket error");
    }
    memset(&name, 0, sizeof(name));
    name.sin_family = AF_INET;
    name.sin_port = htons(port);
    name.sin_addr.s_addr = htonl(INADDR_ANY);
    if (bind(httpd, (struct sockaddr *)&name, sizeof(name)) < 0) {
        char *msg = strerror(errno);
        die_error("bind error, " << msg);
    }
    if (port == 0) {
        unsigned int namelen = sizeof(name);
        if (getsockname(httpd, (struct sockaddr *)&name, &namelen) == -1)
            die_error("getsockname error");
        port = ntohs(name.sin_port);
    }
    if (listen(httpd, 5) < 0) {
        die_error("listen error, " << strerror(errno));
    }
    return httpd;
}

