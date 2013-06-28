#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.


AC_PREREQ([2.61])
AC_INIT([Icinga2Web], [0.0.1], [])
AC_PREFIX_DEFAULT(/usr/local/icinga2-web)

if test "x$prefix" = "xNONE" ; then
    installDir="/usr/local/icinga2-web"
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

#
# Users for webfiles
#

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
    AS_HELP_STRING([--with-http-configuration-path=PATH], [Include folder apache2 (default /etc/apache2/conf.d)]),
    www_conf_path=$withval,
    www_conf_path=AC_PATH_GUESS([/etc/httpd/conf.d /etc/apache2/conf.d /etc/apache/conf.d], [www_conf_path], [/etc/apache2/conf.d])
)

#
#   Users and groups for installation
#

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

#
#   Internal database setup 
#

AC_ARG_WITH([internal_db_type],
    AS_HELP_STRING([--with-internal-db-type=TYPE], [database type to use for internal database (default mysql, supported: pgsql, mysql)]),
    internal_db_type=$withval,
    internal_db_type=mysql
)

AC_ARG_WITH([internal_db_name],
    AS_HELP_STRING([--with-internal-db-name=NAME], [database name to use for internal database (default icinga2web)]),
    internal_db_name=$withval,
    internal_db_name=icinga2web
)
    
AC_ARG_WITH([internal_db_host],
    AS_HELP_STRING([--with-internal-db-host=HOST], [database host to use for internal database (default localhost)]),
    internal_db_host=$withval,
    internal_db_host=localhost
)

AC_ARG_WITH([internal_db_port],
    AS_HELP_STRING([--with-internal-db-port=PORT], [database port to use for internal database (default: 3306 for mysql, 5432 for pgsql)]),
    internal_db_port=$withval,
    internal_db_port=db_default_port
)

AC_ARG_WITH([internal_db_pass],
    AS_HELP_STRING([--with-internal-db-pass=PASS], [database pass to use for internal database (default icinga2web)]),
    internal_db_pass=$withval,
    internal_db_pass=icinga2web
)

AC_ARG_WITH([internal_db_user],
    AS_HELP_STRING([--with-internal-db-user=USER], [database user to use for internal database (default icinga2web)]),
    internal_db_user=$withval,
    internal_db_user=icinga2web
)

#
#   Authorization method
#   
#

AC_ARG_WITH([internal_authentication], 
    AC_HELP_STRING([--with-internal-authentication], [use the internal database for authentication (default: yes)]),
    internal_authentication=yes,
    internal_authentication=def
)

AC_ARG_WITH([ldap_authentication], 
    AC_HELP_STRING([--with-ldap-authentication], [use a ldap server for authentication (default: no)]),
    ldap_authentication=yes,
    ldap_authentication=no
)

#
#   LDAP Authorization
#

AC_ARG_WITH([ldap_host],
    AS_HELP_STRING([--with-ldap-host=HOST], [host to use for authentication via ldap (default localhost)]),
    ldap_host=$withval,
    ldap_host=localhost
)

AC_ARG_WITH([ldap_port],
    AS_HELP_STRING([--with-ldap-port=PORT], [port to use for authentication via ldap (default 389)]),
    ldap_port=$withval,
    ldap_port=389
)

AC_ARG_WITH([ldap_binddn],
    AS_HELP_STRING([--with-ldap-binddn=DN], [dn to use for retrieving user information via ldap (default cn=admin, cn=config)]),
    ldap_binddn=$withval,
    ldap_binddn=["cn=Manager, ou=icinga, ou=org"]
)

AC_ARG_WITH([ldap_bindpass],
    AS_HELP_STRING([--with-ldap-bindpass=PASS], [password to use for retrieving user information via ldap (default admin)]),
    ldap_bindpass=$withval,
    ldap_bindpass=["secret"]
)

AC_ARG_WITH([ldap_rootdn],
    AS_HELP_STRING([--with-ldap-rootdn=LDAP_ATTRIBUTE], [root dn to use for user lookup (default ou=people, dc=icinga, dc=org)]),
    ldap_rootdn=$withval,
    ldap_rootdn=["ou=people, dc=icinga, dc=org"]
)

AC_ARG_WITH([ldap_user_class], 
    AS_HELP_STRING([--with-ldap-attribute-username=LDAP_ATTRIBUTE], [user object type to use for the authentication (defaults inetOrgPerson)]),
    ldap_user_class=$withval,
    ldap_user_class="inetOrgPerson"
)
    
AC_ARG_WITH([ldap_attribute_username],
    AS_HELP_STRING([--with-ldap-attribute-username=LDAP_ATTRIBUTE], [user attribute to use for the username (defaults uid)]),
    ldap_attribute_username=$withval,
    ldap_attribute_username="uid"
)

AC_ARG_WITH([ldap_attribute_firstname],
    AS_HELP_STRING([--with-ldap-attribute-firstname=LDAP_ATTRIBUTE], [user attribute to use for the firstname (defaults firstname)]),
    ldap_attribute_firstname=$withval,
    ldap_attribute_firstname="firstname"
)

AC_ARG_WITH([ldap_attribute_lastname],
    AS_HELP_STRING([--with-ldap-attribute-lastname=LDAP_ATTRIBUTE], [user attribute to use for the lastname (defaults lastname)]),
    ldap_attribute_lastname=$withval,
    ldap_attribute_lastname="lastname"
)

AC_ARG_WITH([ldap_attribute_password],
    AS_HELP_STRING([--with-ldap-attribute-password=LDAP_ATTRIBUTE], [user attribute to use for the password (defaults password)]),
    ldap_attribute_password=$withval,
    ldap_attribute_password="password"
)


# Installation options
INSTALL_OPTS="-o $bin_user -g $bin_group"
INSTALL_OPTS_WEB="-o $web_user -g $web_group"


AS_IF([test "x$internal_db_type" == xmysql],
    [AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Mysql.php],[Zend Framework - MySQL PDO Adapter])])
AS_IF([test "x$internal_db_type" == xpgsql],
    [AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Pgsql.php],[Zend Framework - PostgreSQL PDO Adapter])])
AS_IF([test "x$ldap_authentication" != xno],
    AC_CHECK_PHP_MODULE([ldap]))

# Installation directives
AC_SUBST(app_name)
AC_SUBST(web_user)
AC_SUBST(web_group)
AC_SUBST(web_path)
AC_SUBST(www_conf_path)
AC_SUBST(bin_user)
AC_SUBST(bin_group)

# Internal db setup
AC_SUBST(internal_db_type)
AC_SUBST(internal_db_name)
AC_SUBST(internal_db_host)
AC_SUBST(internal_db_port)
AC_SUBST(internal_db_user)
AC_SUBST(internal_db_pass)

# ldap setup 
AC_SUBST(ldap_host)
AC_SUBST(ldap_port)
AC_SUBST(ldap_rootdn)
AC_SUBST(ldap_binddn)
AC_SUBST(ldap_bindpass)
AC_SUBST(ldap_user_class)
AC_SUBST(ldap_attribute_basedn)
AC_SUBST(ldap_attribute_username)
AC_SUBST(ldap_attribute_firstname)
AC_SUBST(ldap_attribute_lastname)
AC_SUBST(ldap_attribute_password)


AC_SUBST(PHP)
AC_SUBST(INSTALL_OPTS)
AC_SUBST(INSTALL_OPTS_WEB)

AC_CONFIG_FILES([
    Makefile
    etc/config/authentication_db.ini
    etc/config/authentication_ldap.ini
])

AC_OUTPUT
