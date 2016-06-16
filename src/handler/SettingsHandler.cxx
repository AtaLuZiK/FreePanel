#include "../Handler.h"
#include "SettingsHandler.h"
#include <rapidjson/document.h>
#include <rapidjson/writer.h>
#include <rapidjson/stringbuffer.h>
#include "../common.h"
#include <map>
#include "AppConfig.h"
#include <regex>
#include <iostream>


inline bool CheckDigit(const char *s)
{
    while (*s) {
        if (!isdigit(*s))
            return false;
        ++s;
    }
    return true;
}


void SettingsHandler::OnRequest(HttpRequest& request, HttpResponse& response)
{
    printf("settingsHandler\n");
    response.SetHeader("Content-Type", "text/html;charset=utf-8");
    const char *type = request.GetParameter("type");
    if (type == nullptr) {
        response.Write("invalid0");
        return;
    }
    
    if (strcmp(type, "freepanel") == 0) {
        OnSetFreePanel(request, response);
    }
}


void SettingsHandler::OnSetFreePanel(HttpRequest& request, HttpResponse& response)
{
    const char *port = request.GetParameter("port");
    if (port == nullptr || !CheckDigit(port)) {
        response.Write("not port0");
        return;
    }
    const char *apacheDir = AppConfig::GetInstance().GetInstalledDir("apache");
    if (!apacheDir) {
        response.Write("not install0");
        return;
    }
    char apacheConfigFile[FILENAME_MAX];
    sprintf(apacheConfigFile, "%s/conf/httpd.conf", apacheDir);
    FILE *f = fopen(apacheConfigFile, "r+b");
    fseek(f, 0, SEEK_END);
    int fileSize = ftell(f);
    fseek(f, 0, SEEK_SET);
    char *buffer = (char *)malloc(fileSize + 1);
    fread(buffer, 1, fileSize, f);
    buffer[fileSize] = 0;
    std::string content(buffer);
    free(buffer);

    // ServerName 000.000.000.000:0000
    char newServerName[32];
    sprintf(newServerName, "ServerName 0.0.0.0:%s", port);
    std::regex serverNamePattern("ServerName\\s+[\\S]+");
    content = std::regex_replace(content, serverNamePattern, newServerName);
    int written = 0;
    if ((written = fwrite(content.c_str(), 1, content.size(), f)) != content.size()) {
        std::cerr << "Could not save file(" << apacheConfigFile << "), written " << written << "bytes." << std::endl;
    }
    fclose(f);
    response.Write(content.c_str());
}

