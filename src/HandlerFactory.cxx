#include "Handler.h"
#include "HandlerFactory.h"
#include "handler/NotFoundHandler.h"
#include "handler/SystemHandler.h"
#include "handler/SettingsHandler.h"
#include "string.h"

Handler *HandlerFactory::CreateHandler(const char *url)
{
    if (strcmp(url, "/system") == 0)
        return new SystemHandler;
    else if (strcmp(url, "/settings") == 0)
        return new SettingsHandler;
    return new NotFoundHandler;
}

