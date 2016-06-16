#ifndef FP_SYSTEMHANDLER_H_
#define FP_SYSTEMHANDLER_H_

class SystemHandler : public Handler
{
public:
    void OnRequest(HttpRequest& request, HttpResponse& response) override;

};

#endif

