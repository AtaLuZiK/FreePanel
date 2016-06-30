#include <string>
#include <sstream>
#include <regex>
#include "VirtualHostConfig.h"

VirtualHostConfig::VirtualHostConfig()
    : m_IPv4("*")
    , m_Port(80)
{

}


VirtualHostConfig::VirtualHostConfig(VirtualHostConfig&& vhc)
    : m_Port(vhc.m_Port)
    , m_CustomLog(std::move(vhc.m_CustomLog))
    , m_ErrorLog(std::move(vhc.m_ErrorLog))
    , m_LastError(std::move(vhc.m_LastError))
    , m_DocumentRoot(std::move(vhc.m_DocumentRoot))
    , m_Domain(std::move(vhc.m_Domain))
    , m_IPv4(std::move(vhc.m_IPv4))
{

}


VirtualHostConfig::~VirtualHostConfig()
{
}


VirtualHostConfig& VirtualHostConfig::operator=(VirtualHostConfig&& vhc)
{
    if (this != &vhc) {
        m_Port = vhc.m_Port;
        m_CustomLog = std::move(vhc.m_CustomLog);
        m_ErrorLog = std::move(vhc.m_ErrorLog);
        m_LastError = std::move(vhc.m_LastError);
        m_DocumentRoot = std::move(vhc.m_DocumentRoot);
        m_Domain = std::move(vhc.m_Domain);
        m_IPv4 = std::move(vhc.m_IPv4);
    }
    return *this;
}


bool VirtualHostConfig::LoadFromFile(const char *filename)
{
    const int BUFFER_SIZE = 2048;
    char buffer[BUFFER_SIZE];
    FILE *f = fopen(filename, "rb");
    if (f) {
        unsigned long read = 0;
        while (read = fread(buffer, 1, BUFFER_SIZE, f)) {

        }
        fclose(f);
    }
    return false;
}


bool VirtualHostConfig::GenerateApacheConfig(std::string& output)
{
    if (m_DocumentRoot.empty()) {
        m_LastError = "DocumentRoot can not be empty";
        return false;
    }
    if (m_Domain.empty()) {
        m_LastError = "Domain can not be empty";
        return false;
    }
    std::stringstream buffer;
    buffer << "<VirtualHost " << m_IPv4 << ":" << m_Port << ">" << std::endl;
    buffer << "DocumentRoot \"" << m_DocumentRoot << "\"" << std::endl;
    buffer << "ServerName " << m_Domain << std::endl;
    if (!m_ErrorLog.empty())
        buffer << "ErrorLog " << m_ErrorLog << std::endl;
    if (!m_CustomLog.empty())
        buffer << "CustomLog " << m_CustomLog << " common" << std::endl;
    buffer << "<Directory \"" << m_DocumentRoot << "\">" << std::endl;
    buffer << "SetOutputFilter DEFLATE" << std::endl;
    buffer << "Options FollowSymLinks" << std::endl;
    buffer << "AllowOverride All" << std::endl;
    buffer << "Order allow,deny" << std::endl;
    buffer << "Allow from all" << std::endl;
    buffer << "DirectoryIndex default.html index.html default.htm index.htm default.php index.php" << std::endl;
    buffer << "</Directory>" << std::endl;
    buffer << "</VirtualHost>" << std::endl;
    output = buffer.str();
    return true;
}


std::string VirtualHostConfig::GetLastError() const
{
    return m_LastError;
}


void VirtualHostConfig::SetDomain(const char *domain)
{
    if (!domain)
        return;
    m_Domain = domain;
}


void VirtualHostConfig::SetPort(unsigned short port)
{
    m_Port = port;
}


void VirtualHostConfig::SetIPv4(const char *ip)
{
    if (!ip)
        return;
    std::regex ipv4Pattern("((?:(?:25[0-5]|2[0-4]\\d|((1\\d{2})|([1-9]?\\d)))\\.){3}(?:25[0-5]|2[0-4]\\d|((1\\d{2})|([1-9]?\\d))))");
    if (std::regex_match(ip, ipv4Pattern)) {
        m_IPv4 = ip;
    }   // TODO: check ipv6
}


void VirtualHostConfig::SetDocumentRoot(const char *documentRoot)
{
    if (!documentRoot)
        return;
    m_DocumentRoot = documentRoot;
}


void VirtualHostConfig::SetErrorLog(const char *logfile)
{
    if (!logfile)
        return;
    m_ErrorLog = logfile;
}


void VirtualHostConfig::SetCustomLog(const char *logfile)
{
    if (!logfile)
        return;
    m_CustomLog = logfile;
}
