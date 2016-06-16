#ifndef FREEPANEL_URLENCODER_H_
#define FREEPANEL_URLENCODER_H_

class URLEncoder
{
public:
    static size_t Decode(const char *source, char *pDecodedStr);
    static size_t Encode(const char *source, char *pEncodedStr);

private:
    static int HexToInt(const char *s);

};

#endif

