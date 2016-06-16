#include "../Handler.h"
#include "NotFoundHandler.h"

void NotFoundHandler::OnRequest(HttpRequest& request, HttpResponse& response)
{
    response.SetHeader("Content-Type", "text/html;charset=utf-8");
    response.SetHeader("Cache-Control", "max-age=86400");
    response.SetStatus(404);
    response.Write("Not Found");
}

