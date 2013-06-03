#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.


AC_PREREQ([2.61])
AC_INIT([Cranberry], [0.0.1], [])
AC_PREFIX_DEFAULT(/usr/local/i2)

if test "x$prefix" = "xNONE" ; then
    installDir="/usr/local/i2"
    prefix=$installDir
else
    installDir=$prefix 
fi

# Checks for programs.
AC_PROG_INSTALL
AC_PROG_GREP
AC_PROG_SED

# Check for php
AC_ARG_VAR([PHP],[php cli binary])
AC_CHECK_PHP_INCLUDE([Zend/Application.php],[Zend Framework])
AC_CHECK_PHP_VERSION([5],[3],[0])
AC_CHECK_BIN([PHP], [php])

# Checks for libraries.

AC_CHECK_PHP_MODULE([sockets json])

# Checks for header files.


# Users for webfiles
AC_ARG_WITH([web_user],
    AS_HELP_STRING([--with-web-user=USER], [username for web writable files (default www-data)]),
    web_user=$withval,
    AC_USER_GUESS([www wwwrun www-data apache httpd nobody],[web_user],[www-data])
)

AC_ARG_WITH([web_group],
    AS_HELP_STRING([--with-web-group=GROUP], [group for web writable files (default www-data)]),
    web_group=$withval,
    AC_GROUP_GUESS([www www-data apache httpd nogroup nobody],[web_group], [www-data])
)

AC_ARG_WITH([web_path],
    AS_HELP_STRING([--with-web-path=PATH], [web sub path (default /cranberry)]),
    web_path=$withval,
    web_path=/cranberry
)

AC_ARG_WITH([www_conf_path],
    AS_HELP_STRING([--with-http-configuration-path=PATH], [Include folder apache2 (default /etc/apache2/conf.d]),
    www_conf_path=$withval,
    www_conf_path=AC_PATH_GUESS([/etc/httpd/conf.d /etc/apache2/conf.d /etc/apache/conf.d], [www_conf_path], [/etc/apache2/conf.d])
)

AC_ARG_WITH([bin_user],
    AS_HELP_STRING([--with-bin-user=USER], [user for all other files (default root)]),
    bin_user=$withval,
    bin_user=root
)

AC_ARG_WITH([bin_group],
    AS_HELP_STRING([--with-bin-group=GROUP], [group for all other files (default bin)]),
    bin_group=$withval,
    bin_group=bin
)

# Installation options
INSTALL_OPTS="-o $bin_user -g $bin_group"
INSTALL_OPTS_WEB="-o $web_user -g $web_group"

AC_SUBST(app_name)
AC_SUBST(web_user)
AC_SUBST(web_group)
AC_SUBST(web_path)
AC_SUBST(www_conf_path)
AC_SUBST(bin_user)
AC_SUBST(bin_group)

AC_SUBST(PHP)
AC_SUBST(INSTALL_OPTS)
AC_SUBST(INSTALL_OPTS_WEB)

AC_CONFIG_FILES([
    Makefile
    etc/apache/cranberry.conf
])

AC_OUTPUT
