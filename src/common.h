#ifndef COMMON_H_
#define COMMON_H_
#include <ctype.h>
#include <cstring>
#include <string>

#define die_error(message)  \
{    \
    FPLOG_FATAL(message);   \
    exit(EXIT_FAILURE); \
}

std::string exec_command(const char *command);

const char *skip_space(const char *string, const char *pEnd = NULL);

char *rtrim(char *string);

bool check_digit(const char *s);

char *dump_memory(const void *mem, size_t sizeInBytes);

#endif

