#include <map>
#include "AppConfig.h"
#include <cstdio>
#include <cstring>
#include <iostream>
#include <libconfig.h++>
using namespace libconfig;

bool AppConfig::Load(const char *pszFilename)
{
    strcpy(m_szFilename, pszFilename);
    Config cfg;
    try {
        cfg.readFile(m_szFilename);
    } catch(const FileIOException &e) {
        std::cerr << "I/O error while reading configure file(" << m_szFilename << ")." << std::endl;
        return false;
    } catch(const ParseException &e) {
        std::cerr << "Parse error at " << e.getFile() << ":" << e.getLine()
            << " - " << e.getError() << std::endl;
        return false;
    } catch(const SettingNameException& e) {
        std::cerr << "Hello world" << std::endl;
    }

    const Setting& root = cfg.getRoot();
    
    try {
        const Setting& installed = root["installed"];
        int count = installed.getLength();
        for (int i = 0; i < count; ++i) {
            Setting& item = installed[i];
            const char *name = item.getName();
            const char *path = (const char *)item;
            if (name && path) {
                m_Installed[name] = path;
            }
        }
    } catch(const SettingNotFoundException &e) {
    } catch(const SettingNameException& e) {
        std::cerr << "Name exception" << std::endl;
    }
    return true;
}


bool AppConfig::Save(const char *pszFilename)
{
    return false;
}


const char *AppConfig::Get(const char *name, const char *defaultValue) const
{
    return nullptr;
}


const char *AppConfig::GetInstalledDir(const char *name) const
{
    if (!name)
        return nullptr;
    auto found = m_Installed.find(name);
    if (found == m_Installed.end())
        return nullptr;
    return found->second.c_str();
}


void AppConfig::Set(const char *name, const char *value)
{
}

