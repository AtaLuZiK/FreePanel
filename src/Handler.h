#ifndef FP_HANDLER_H_
#define FP_HANDLER_H_
#include "HttpPacket.h"

class Handler
{
public:
    virtual void OnRequest(HttpRequest& request, HttpResponse& response) = 0;

};

#endif

