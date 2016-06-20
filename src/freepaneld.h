#ifndef FREEPANEL_H_
#define FREEPANEL_H_

#define FREEPANELD_VERSION VERSION
#define DAEMON_NAME PACKAGE_NAME"d"
#define FREEPANELD_CONF SYSCONFDIR"/freepaneld.cfg"

#include <log4cxx/logger.h>
#include <log4cxx/propertyconfigurator.h>
#include <log4cxx/helpers/exception.h>

#define DECLARE_LOGGER(name) static log4cxx::LoggerPtr __logger(log4cxx::Logger::getLogger(name));
#define DECLARE_FP_LOGGER() DECLARE_LOGGER(DAEMON_NAME)
#define FPLOG_DEBUG(message)    LOG4CXX_DEBUG(__logger, message)
#define FPLOG_TRACE(message)    LOG4CXX_TRACE(__logger, message)
#define FPLOG_INFO(message)     LOG4CXX_INFO(__logger, message)
#define FPLOG_WARN(message)     LOG4CXX_WARN(__logger, message)
#define FPLOG_ERROR(message)    LOG4CXX_ERROR(__logger, message)
#define FPLOG_FATAL(message)    LOG4CXX_FATAL(__logger, message)

#endif

