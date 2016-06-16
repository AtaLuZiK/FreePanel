#ifndef FP_NOTFOUNDHANDLER_H_
#define FP_NOTFOUNDHANDLER_H_

class NotFoundHandler : public Handler
{
public:
    void OnRequest(HttpRequest& request, HttpResponse& response) override;

};

#endif

