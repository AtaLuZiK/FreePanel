#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.69])
AC_INIT([freepanel], [1.0], [])
AM_INIT_AUTOMAKE([-Wall foreign subdir-objects])

# Checks for programs.
AC_PROG_CXX
AC_PROG_CC

# Checks for libraries.
PKG_CHECK_MODULES([LIBCONFIGXX], [libconfig++ >= 1.4], , AC_MSG_ERROR([libconfig++ 1.4 or newer not found.]))
AX_PTHREAD()
AC_LOG4CXX_CHECK()

# Checks for header files.
AC_CHECK_HEADERS([netinet/in.h stdlib.h sys/socket.h syslog.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_CHECK_HEADER_STDBOOL
AC_C_INLINE
AC_TYPE_SIZE_T
AX_CXX_COMPILE_STDCXX([11], [noext], [mandatory])

# Checks for library functions.
AC_FUNC_MALLOC
AC_FUNC_REALLOC
AC_CHECK_FUNCS([memset socket strcasecmp strchr strdup strerror])

AC_PREFIX_DEFAULT(/usr/local/freepanel)
AC_ARG_WITH([apache-dir], AS_HELP_STRING([--with-apache-dir], [Set the path where apache was installed]), 
    AC_CHECK_FILE([$with_php_dir/bin/php], 
        AC_SUBST([APACHE_INSTALL_DIR], [$with_apache_dir]), 
        AC_MSG_FAILURE($with_apache_dir not apache install directory)
    ), 
    AC_SUBST([APACHE_INSTALL_DIR])
    )
AC_ARG_WITH([mysql-dir], AS_HELP_STRING([--with-mysql-dir], [Set the path where MYSQL was installed]), 
    AC_CHECK_FILE([$with_mysql_dir/bin/mysqld], 
        AC_SUBST([MYSQL_INSTALL_DIR], [$with_mysql_dir]), 
        AC_MSG_FAILURE($with_mysql_dir not mysql install directory)
        ), 
    AC_SUBST([MYSQL_INSTALL_DIR])
    )
AC_ARG_WITH([php-dir], AS_HELP_STRING([--with-php-dir], [Set the path where PHP was installed]), 
    AC_CHECK_FILE([$with_php_dir/bin/php], 
        AC_SUBST([PHP_INSTALL_DIR], [$with_php_dir]), 
        AC_MSG_FAILURE($with_php_dir not php install directory)
        ), 
    AC_SUBST([PHP_INSTALL_DIR])
    )
AC_CONFIG_FILES([Makefile src/Makefile tests/Makefile])
AC_OUTPUT
