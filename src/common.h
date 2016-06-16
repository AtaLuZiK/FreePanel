#ifndef COMMON_H_
#define COMMON_H_
#include <ctype.h>
#include <cstring>
#include <string>

#define die_error(message, arg...)  \
{    \
    syslog(LOG_ERR, message, ##arg);   \
    closelog(); \
    exit(EXIT_FAILURE); \
}

std::string exec_command(const char *command);

const char *skip_space(const char *string, const char *pEnd = NULL);

char *rtrim(char *string);

#endif

