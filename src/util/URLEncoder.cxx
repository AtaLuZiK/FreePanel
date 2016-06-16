#include <cstddef>
#include <cctype>
#include "URLEncoder.h"
#include <cstring>

size_t URLEncoder::Decode(const char *source, char *pDecodedStr)
{
    size_t sourceLength = strlen(source);
    const char *pSrc = source;
    char *pDest = pDecodedStr;
    while (sourceLength--) {
        if (*pSrc == '+') {
            *pDest = ' ';
        } else if (*pSrc == '%' && sourceLength >= 2 && isxdigit((int)*(pSrc + 1)) && isxdigit((int)*(pSrc + 2))) {
            *pDest = (char)HexToInt(pSrc + 1);
            pSrc += 2;
            sourceLength -= 2;
        } else {
            *pDest = *pSrc;
        }
        ++pSrc;
        ++pDest;
    }
    *pDest = 0;
    size_t decodedLength = strlen(pDecodedStr);
    return decodedLength;
}


size_t URLEncoder::Encode(const char *source, char *pEncodedStr)
{
    static unsigned char HEXCHAR[] = "0123456789abcdef";
    const char *pSrc = source;
    char *pDest = pEncodedStr;
    register unsigned char c;
    while (*pSrc) {
        c = *pSrc++;
        if (c == ' ') {
            *pDest++ = '+';
        } else if ((c < '0' && c != '-' && c!= '.')
                    || (c < 'A' && c > '9')
                    || (c > 'Z' && c < 'a' && c != '_')
                    || (c > 'z')) {
            pDest[0] = '%';
            pDest[1] = HEXCHAR[c >> 4];
            pDest[2] = HEXCHAR[c & 15];
            pDest += 3;
        } else {
            *pDest++ = c;
        }
    }
    *pDest = 0;
    return strlen(pDest);
}


int URLEncoder::HexToInt(const char *s)
{
    int value;
    int c;

    c = ((unsigned char *)s)[0];
    if (isupper(c))
        c = tolower(c);
    value = (c >= '0' && c <= '9' ? c - '0' : c - 'a' + 10) * 16;

    c = ((unsigned char *)s)[1];
    if (isupper(c))
        c = tolower(c);
    value += c >= '0' && c <= '9' ? c - '0' : c - 'a' + 10;
    return value;
}

