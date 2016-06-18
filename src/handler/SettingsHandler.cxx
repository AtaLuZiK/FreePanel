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
#include <stdio.h>
#define FREEPANEL_HTTPD_CONF SYSCONFDIR"/httpd-freepanel.conf"


void SettingsHandler::OnRequest(HttpRequest& request, HttpResponse& response)
{
    response.SetHeader("Content-Type", "text/html;charset=utf-8");
    const char *type = request.GetParameter("type");
    if (type == nullptr) {
        response.Write("0");
        return;
    }
    
    if (strcmp(type, "freepanel") == 0) {
        OnSetFreePanel(request, response);
    }
}


void SettingsHandler::OnSetFreePanel(HttpRequest& request, HttpResponse& response)
{
    const char *port = request.GetParameter("port");
    if (port == nullptr || !check_digit(port)) {
        response.Write("0");
        return;
    }
    const char *apacheDir = AppConfig::GetInstance().GetInstalledDir("apache");
    if (!apacheDir) {
        response.Write("0");
        return;
    }

    const int bufferSize = FILENAME_MAX;
    char buffer[bufferSize];
    std::stringstream sbuffer;

    // modify httpd-freepanel.conf
    FILE *freepanelConf = fopen(FREEPANEL_HTTPD_CONF, "rb");
    std::string content;
    while (fread(buffer, 1, bufferSize, freepanelConf)) {
        content.append(buffer);
    }
    if (!feof(freepanelConf)) {
        response.Write("Could not read configure.");
        return;
    }
    // match origin port first, use this port to match Listen configure
    // if not match default port is 80
    std::regex portPattern("<VirtualHost\\s+([^:]+):(\\d+)>");
    std::smatch portMatch;
    std::string oriHost;
    std::string oriPort = "80";
    std::string search; // used by std::string::replace
    if (std::regex_search(content, portMatch, portPattern)) {
        search = portMatch[0].str();
        oriHost = portMatch[1].str();
        oriPort = portMatch[2].str();
        sbuffer.str("");
        sbuffer << "<VirtualHost " << oriHost << ":" << port << ">";
        content.replace(content.find(search), search.size(), sbuffer.str());
        freopen(nullptr, "wb", freepanelConf);
        fwrite(content.c_str(), 1, content.size(), freepanelConf);
        fclose(freepanelConf);
    } else {
        response.Write("Could not parse freepanel configure.");
        fclose(freepanelConf);
        return;
    }

    // modify httpd.conf
    sprintf(buffer, "%s/conf/httpd.conf", apacheDir);
    FILE *httpdConf = fopen(buffer, "rb");
    content = "";
    int read = 0;
    while (read = fread(buffer, 1, bufferSize, httpdConf)) {
        content.append(buffer, read);
    }
    if (!feof(httpdConf)) {
        response.Write("Could not read configure.");
        fclose(httpdConf);
        return;
    }
    // match Listen oriPort and replace it
    sbuffer.str("");
    sbuffer << "Listen\\s+" << oriPort << "\\s*";
    std::string s = sbuffer.str();
    std::regex listenPattern(sbuffer.str());
    std::smatch listenMatch;
    if (std::regex_search(content, listenMatch, listenPattern)) {
        search = listenMatch[0].str();
        sbuffer.str("");
        sbuffer << "Listen " << port << std::endl;
        content.replace(content.find(search), search.size(), sbuffer.str());
    } else {
        // not find original Listen configure, add new
        const char LISTEN_INVOKED[] = "# FREEPANEL_INVOKED_LISTEN";
        size_t pos = content.find(LISTEN_INVOKED);
        if (pos == std::string::npos) {
            response.Write("Could not find listen invoked tag in Apache configure");
            return;
        }
        sbuffer.str("");
        sbuffer << "Listen " << port << std::endl;   // sizeof(constant) included null-terminal character
        content.insert(pos + sizeof(LISTEN_INVOKED), sbuffer.str());
    }
    int written = 0;
    httpdConf = freopen(nullptr, "wb", httpdConf);
    if ((written = fwrite(content.c_str(), 1, content.size(), httpdConf)) != content.size()) {
        response.Write("Could not save httpd configure.");
    }
    fclose(httpdConf);

    // restart httpd
    std::string cmd(apacheDir);
    cmd.append("/bin/apachectl -kgraceful");
    exec_command(cmd.c_str());
    response.Write("success");
}

