#include <pwd.h>
#include <grp.h>
#include <dirent.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <rapidjson/document.h>
#include <rapidjson/writer.h>
#include <rapidjson/stringbuffer.h>
#include "freepaneld.h"
#include "AppConfig.h"
#include "FPPacket.h"
#include "FPProtocolHandler.h"
#include "VirtualHostConfig.h"
#include "common.h"

DECLARE_FP_LOGGER()

static const char VHOST_CONF_DIR[] = SYSCONFDIR"/vhosts";


void FPProtocolHandler::OnDataReceived(IOSession& session, void *data, size_t length)
{
    FPPacket *pPacket = (FPPacket *)data;
    switch (pPacket->GetCommand()) {
    case FPPacket::Command::TEST:
        OnTestCommand(session, pPacket);
        break;
    case FPPacket::Command::DISCONNECT:
        OnDisconnectCommand(session, pPacket);
        break;
    case FPPacket::Command::VHOST:
        OnVHostCommand(session, pPacket);
        break;
    case FPPacket::Command::NONE:
    default:
        FPLOG_INFO("Recviced a invlaid command: " << (int)pPacket->GetCommand() << " and discard it.");
    }
}


void FPProtocolHandler::OnTestCommand(IOSession& session, FPPacket *pPacket)
{
    FPLOG_DEBUG("Recviced a TEST command");
    session.WriteString("success");
}



void FPProtocolHandler::OnDisconnectCommand(IOSession& session, FPPacket *pPacket)
{
    FPLOG_DEBUG("Recviced a DISCONNECT command");
    session.Close();
}


void FPProtocolHandler::OnVHostCommand(IOSession& session, FPPacket *pPacket)
{
    const char *entity = pPacket->GetEntity();
    const unsigned int length = pPacket->GetEntitySize() - 1;
    if (length == 0) {
        FPPacket response;
        response.SetOrder(pPacket->GetOrder());
        response.Append("invalid parameters");
    }
    byte action = *entity;

    rapidjson::Document doc;
    doc.Parse<0>(entity + 1);
    if (doc.HasParseError()) {
        rapidjson::ParseErrorCode code = doc.GetParseError();
        std::stringstream s;
        s << "Failed to parse content: " << code;
        std::string errMsg(s.str());
        FPLOG_ERROR(errMsg << ", " << entity + 1);
        session.WriteString(errMsg);
        return;
    }

    if (action == 0) {
        // create a new virtual host
        FPLOG_DEBUG((const char *)entity + 1);
        const char *domain = doc.HasMember("DOMAIN") ? doc["DOMAIN"].GetString() : nullptr;
        std::string documentRoot(doc.HasMember("DOCUMENT_ROOT") ? doc["DOCUMENT_ROOT"].GetString() : "");
        if (!domain || !strlen(domain)) {
            FPLOG_ERROR("need domain");
            session.WriteString("need domain");
            return;
        }
        if (documentRoot.empty()) {
            documentRoot = "/var/www/";
            documentRoot.append(domain);
        }

        char szFilename[FILENAME_MAX];
        sprintf(szFilename, "%s/%s.conf", VHOST_CONF_DIR, domain);
        char szErrLog[FILENAME_MAX];
        sprintf(szErrLog, LOCALSTATEDIR"/log/%s-error.log", domain);
        char szCustomLog[FILENAME_MAX];
        sprintf(szCustomLog, LOCALSTATEDIR"/log/%s-access.log", domain);
        struct stat fileStat;
        if (stat(szFilename, &fileStat) == 0 || errno != ENOENT) {
            FPLOG_WARN("Try to create an exists domain virtual host");
            session.WriteString("Domain exists");
            return;
        }
        VirtualHostConfig vhost;
        vhost.SetDomain(domain);
        vhost.SetPort(80);
        vhost.SetDocumentRoot(documentRoot.c_str());
        vhost.SetErrorLog(szErrLog);
        vhost.SetCustomLog(szCustomLog);

        std::string content;
        if (!vhost.GenerateApacheConfig(content)) {
            FPLOG_ERROR("Could not generate virtual host configure for apache, " << vhost.GetLastError());
            session.WriteString("Could not generate virtual host configure for apache");
            return;
        }
        FILE *confFile = fopen(szFilename, "wb");
        if (confFile == nullptr) {
            FPLOG_ERROR("Could not open file: " << szFilename << ", " << strerror(errno));
            session.WriteString("freepaneld internal error");
            return;
        }
        size_t written = fwrite(content.c_str(), 1, content.size(), confFile);
        fclose(confFile);
        if (written != content.size()) {
            FPLOG_FATAL("Can not write configure file: " << szFilename);
            session.WriteString("freepaneld internal error");
            return;
        }
        // create directory for virtual host and change owner
        mkdir(documentRoot.c_str(), 0755);

        struct passwd *pwd;
        struct group  *grp;

        pwd = getpwnam("www");
        if (pwd == NULL) {
            FPLOG_FATAL("Failed to get uid");
            session.WriteString("freepaneld internal error");
            return;
        }
        grp = getgrnam("www");
        if (grp == NULL) {
            FPLOG_FATAL("Failed to get gid");
            session.WriteString("freepaneld internal error");
            return;
        }

        if (chown(documentRoot.c_str(), pwd->pw_uid, grp->gr_gid) == -1) {
            FPLOG_FATAL("Failed to chown for " << documentRoot);
            session.WriteString("freepaneld internal error");
            return;
        }
        FPLOG_INFO("Created virtual host: " << domain);
        // restart httpd
        std::string cmd(AppConfig::GetInstance().GetInstalledDir("apache"));
        cmd.append("/bin/apachectl -kgraceful");
        exec_command(cmd.c_str());
    } else if (action == 1) {

    } else if (action == 2) {

    } else if (action == 3) {
        // get vhosts
        const char *domain = doc.HasMember("DOMAIN") ? doc["DOMAIN"].GetString() : nullptr;
        OnGetVHost(session, domain);
    }else {
        std::stringstream s;
        s << "Invalid action code " << action;
        std::string errMsg(s.str());
        FPLOG_ERROR(errMsg);
        session.WriteString(errMsg.c_str());
        return;
    }
    session.WriteString("success");
}


void FPProtocolHandler::OnGetVHost(IOSession& session, const char *domain)
{
    char confFilename[FILENAME_MAX];
    if (domain) {

    } else {
        DIR *dir;
        struct dirent *fd;
        dir = opendir(VHOST_CONF_DIR);
        if (dir) {
            while ((fd = readdir(dir)) != nullptr) {
                if (fd->d_type == DT_REG) {
                    sprintf(confFilename, "%s/%s", VHOST_CONF_DIR, fd->d_name);
                    VirtualHostConfig vhost;
                    vhost.LoadFromFile(confFilename);
                }
            }
            closedir(dir);
        }
    }
}
