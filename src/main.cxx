/**
 * FreePanel daemon
 *
 */
#include <sys/stat.h>
#include <stdio.h>
#include <stdlib.h>
#include <getopt.h>
#include <iostream>
#include <unistd.h>
#include <log4cxx/logger.h>
#include <log4cxx/propertyconfigurator.h>
#include <log4cxx/helpers/exception.h>
#include "freepaneld.h"
#include "Server.h"
#include <map>
#include "AppConfig.h"

#define LOGGER_CONF SYSCONFDIR"/freepaneld-log.properties"

log4cxx::LoggerPtr _logger(log4cxx::Logger::getLogger(DAEMON_NAME));
DECLARE_FP_LOGGER()

static struct option daemonOptions[] = {
    { "port", optional_argument, 0, 'p' },
    { "version", no_argument, 0, 'v' },
    { "help", no_argument, 0, 'h' },
    { 0, 0, 0, 0 }
};


void usage()
{
    const char *processName = getenv("_");
    std::cout << "Usage: " << processName << " [-v] [-h]" << std::endl;
    exit(EXIT_FAILURE);
}


int main(int argc, char *argv[])
{
    // initialize logger
    log4cxx::PropertyConfigurator::configure(LOGGER_CONF);
    FPLOG_INFO(DAEMON_NAME" started")
    if (getuid()) {
        FPLOG_ERROR("!!!freepaneld not run as root!!!")
    }
    u_short serverPort = 0;
    while (true) {
        int index = 0;
        char c = getopt_long(argc, argv, "p:vh?", daemonOptions, &index);

        if (c == -1)
            break;
        switch (c) {
        case 0:
            // if this option set a flag, do nothing else now.
            if (daemonOptions[index].flag != 0)
                break;
            std::cout << "option " << daemonOptions[index].name;
            if (optarg)
                std::cout << " with arg " << optarg << std::endl;
            break;
        case 'p':
            serverPort = atol(optarg);
            break;
        case 'v':
            std::cout << "Version: " << FREEPANELD_VERSION << std::endl;
            return EXIT_SUCCESS;
        case 'h':
        case '?':
            usage();
            break;
        }
    }
    // close(STDIN_FILENO);
    // close(STDOUT_FILENO);
    // close(STDERR_FILENO);

    // load configure
    char configFile[FILENAME_MAX];
    sprintf(configFile, "%s/freepaneld.cfg", SYSCONFDIR);
    AppConfig& appConfig = AppConfig::GetInstance();
    appConfig.Load(FREEPANELD_CONF);
    appConfig.Save();
    Server server;
    server.Run(serverPort);
    return EXIT_SUCCESS;
}

