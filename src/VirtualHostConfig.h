#ifndef VIRTUALHOSTCONFIG_H_
#define VIRTUALHOSTCONFIG_H_
/**
 * TODO: Add a ConfigParser to parse configures for apache
 */

/**
 *
 */
enum class NodeType {
    Root,       //!< Root
    VirtualHost,//!< VirtualHost
};

/**
 * The command record structure.  Modules can define a table of these
 * to define the directives it will implement.
 */
typedef struct tagCommandRecord {
    NodeType type;
    /** Extra data, for functions which implement multiple commands... */
    void *extraData;
    /** 'usage' message, in case of syntax errors */
    const char *errMsg;
} CommandRecord;

/**
 * @brief A structure to store information for each virtual server
 */
typedef struct tagServerRecord {
    /** The next server in the list */
    tagServerRecord *next;

    /* Log files --- note that transfer log is now in the modules... */

    /** The name of the error log */
    char *errorLog;
    /** true if this is the virtual server */
    char isVirtual;


    /* Information for redirects */

    /** for redirects, etc. */
    unsigned short port;
    /** The server request scheme for redirect responses */
    const char *server_scheme;

    /* Contact information */

    /** The admin's contact information */
    const char *serverAdmin;
    /** The server hostname */
    const char *serverHostname;

    /** Normal names for ServerAlias servers */
    const char *names;
    /** Wildcarded names for ServerAlias servers */
    const char *wildNames;

    /** Pathname for ServerPath */
    const char *path;
} ServerRecord;

/**
 * This structure is passed to a command which is being invoked,
 * to carry a large variety of miscellaneous data which is all of
 * use to *somebody*...
 */
typedef struct tagCommandParams {
    /** Argument to command from cmd_table */
    void *info;
    /** Server_rec being configured for */
    ServerRecord *server;
    /** If configuring for a directory, pathname of that directory.
     *  NOPE!  That's what it meant previous to the existence of &lt;Files&gt;,
     * &lt;Location&gt; and regex matching.  Now the only usefulness that can be
     * derived from this field is whether a command is being called in a
     * server context (path == NULL) or being called in a dir context
     * (path != NULL).  */
    char *path;
    /** configuration command */
    CommandRecord *cmd;
} CommandParams;

class VirtualHostConfig
{
public:
    VirtualHostConfig();
    virtual ~VirtualHostConfig();
    VirtualHostConfig(const VirtualHostConfig &) = delete;
    VirtualHostConfig(VirtualHostConfig&& vhc);
    VirtualHostConfig& operator=(const VirtualHostConfig &) = delete;
    VirtualHostConfig& operator=(VirtualHostConfig&& vhc);

    bool LoadFromFile(const char *filename);

    bool GenerateApacheConfig(std::string& output);
    bool GenerateNginxConfig(std::string& output);
    std::string GetLastError() const;

    void SetDomain(const char *domain);
    void SetPort(unsigned short port);
    void SetIPv4(const char *ip);
    void SetDocumentRoot(const char *documentRoot);
    void SetErrorLog(const char *logfile);
    void SetCustomLog(const char *logfile);

private:
    std::string m_LastError;
    int m_Port;
    std::string m_IPv4;
    std::string m_DocumentRoot;
    std::string m_Domain;
    std::string m_ErrorLog;
    std::string m_CustomLog;

};

#endif /* VIRTUALHOSTCONFIG_H_ */
