#ifndef FREEPANEL_APPCONFIG_H_
#define FREEPANEL_APPCONFIG_H_
#include "util/Singleton.h"

class AppConfig : public Singleton<AppConfig>
{
public:
    bool Load(const char *pszFilename);
    bool Save(const char *pszFilename = nullptr);
    const char *Get(const char *name, const char *defaultValue) const;
    const char *GetInstalledDir(const char *name) const;
    void Set(const char *name, const char *value);

private:
    bool Parse(const char *content);
    char m_szFilename[FILENAME_MAX];
    std::map<std::string, std::string> m_Installed;

};

#endif

