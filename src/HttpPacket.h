#ifndef HTTP_PACKET_H_
#define HTTP_PACKET_H_

class HttpPacket
{
public:
    virtual const char *GetHeader(const char *name) const = 0;
    virtual void SetHeader(const char *name, const char *value) = 0;

};


class HttpRequest : virtual public HttpPacket
{
public:
    virtual const char *GetMethod() const = 0;
    virtual const char *GetQueryString() const = 0;
    virtual const char *GetParameter(const char *name, const char *defaultValue = nullptr) const = 0;

    /**
     * Returns the part of this request's URL from the protocol name up to the
     * first line of the HTTP request. For example:
     * POST /some/path.html HTTP/1.1    /some/path.html
     * GET http://foo.bar/a.html        /a.html
     * HTTP/1.0                         
     * HEAD /xyz?a=b HTTP/1.1           /xyz
     * @return A pointer to a string containing the part of the URL from thhhe protocol name up to query string.
     */
    virtual const char *GetRequestURI() const = 0;
    
protected:
    virtual void SetHeader(const char *name, const char *value) = 0;

};


class HttpResponse : virtual public HttpPacket
{
public:
    virtual void SetIntHeader(const char *name, int value) = 0;

    /**
     * Sets the character encoding(MIME charset) of the response being sent to the client, for example, to UTF-8.
     */
    virtual void SetCharacterEncoding(const char *charset) = 0;
    virtual void SetContentLength(int length) = 0;

    /**
     * Sets the content type of the response being sent to the client.
     */
    virtual void SetContentType(const char *type) = 0;
    virtual void Write(const char *data, int length = -1) = 0;
    virtual void SetStatus(int statusCode) = 0;

};

#endif

