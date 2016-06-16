#ifndef FP_HANDLERFACTORY_H_
#define FP_HADNLERFACTORY_H_

class Handler;

class HandlerFactory
{
public:
    Handler *CreateHandler(const char *url);

};

#endif

