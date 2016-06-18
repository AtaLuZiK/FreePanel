#include "common.h"

std::string exec_command(const char *command)
{
    std::string result;
    char buffer[1024];
    FILE *fp = popen(command, "r");
    while (0 < fgets(buffer, sizeof(buffer), fp))
      result.append(buffer);
    return result;
}


const char *skip_space(const char *string, const char *pEnd)
{
    const char *p = string;
    if (pEnd)
      while (isspace(*p) && p < pEnd)
        ++p;
    else
      while (isspace(*p) && *p)
        ++p;
    return p;
}


char *rtrim(char *string)
{
    char *p = string + strlen(string) - 1;
    bool found = false;
    while (isspace(*p) && p > string) {
        found = true;
        --p;
    }
    if (found) {
        p += 1;
        *p = 0;
    }
    return string;
}


bool check_digit(const char *s)
{
    while (*s) {
        if (!isdigit(*s))
            return false;
        ++s;
    }
    return true;
}

