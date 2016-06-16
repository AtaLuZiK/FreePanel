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
#include "freepaneld.h"
#include "Server.h"
#include <map>
#include "AppConfig.h"


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

    setlogmask(LOG_UPTO(LOG_NOTICE));
    openlog(DAEMON_NAME, LOG_CONS | LOG_NDELAY | LOG_PERROR | LOG_PID, LOG_USER);

    // close(STDIN_FILENO);
    // close(STDOUT_FILENO);
    // close(STDERR_FILENO);

    // load configure
    char configFile[FILENAME_MAX];
    sprintf(configFile, "%s/freepaneld.cfg", SYSCONFDIR);
    AppConfig& appConfig = AppConfig::GetInstance();
    appConfig.Load(configFile);
    appConfig.Save();
    Server server;
    server.Run(serverPort);

    closelog();
    return EXIT_SUCCESS;
}

