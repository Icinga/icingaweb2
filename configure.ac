#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.61])
AC_INIT([IcingaWeb2], [1.0.0], [info@icinga.org])
AC_PREFIX_DEFAULT(/usr/local/icingaweb)

if test "x$prefix" = "xNONE" ; then
    installDir="/usr/local/icingaweb"
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

#
#   Configuration files
#
AC_ARG_WITH([icingaweb_config_path],
    AS_HELP_STRING([--with-icingaweb-config-path=PATH], [Configuration path for icinga web (default $prefix/config)]),
    icingaweb_config_path=$withval,
    icingaweb_config_path=$prefix/config
)

#
#   Log files
#
AC_ARG_WITH([icingaweb_log_path],
    AS_HELP_STRING([--with-icingaweb-log-path=PATH], [Log path for icinga web (default $prefix/var/log)]),
    icingaweb_log_path=$withval,
    icingaweb_log_path=$prefix/var/log
)

#
#   Users for webfiles
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
    AS_HELP_STRING([--with-web-path=PATH], [web sub path (default /icingaweb)]),
    web_path=$withval,
    web_path=/icingaweb
)

AC_ARG_WITH([httpd_config_path],
    AS_HELP_STRING([--with-httpd-config-path=PATH], [Include folder apache2 (default /etc/apache2/conf.d)]),
    httpd_config_path=$withval,
    httpd_config_path=AC_PATH_GUESS([/etc/httpd/conf.d /etc/apache2/conf-available /etc/apache2/conf.d /etc/apache/conf.d], [httpd_config_path], [/etc/apache2/conf.d])
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

ICINGA_CHECK_DBTYPE($internal_db_type, [--with-internal-db-type])

AC_ARG_WITH([internal_db_name],
    AS_HELP_STRING([--with-internal-db-name=NAME], [database name to use for internal database (default icingaweb)]),
    internal_db_name=$withval,
    internal_db_name=icingaweb
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

AC_ARG_WITH([internal_db_password],
    AS_HELP_STRING([--with-internal-db-password=PASS], [database pass to use for internal database (default icingaweb)]),
    internal_db_password=$withval,
    internal_db_password=icingaweb
)

AC_ARG_WITH([internal_db_user],
    AS_HELP_STRING([--with-internal-db-user=USER], [database user to use for internal database (default icingaweb)]),
    internal_db_user=$withval,
    internal_db_user=icingaweb
)

#
#   Authorization method
#

AC_ARG_WITH([internal_authentication], 
    AC_HELP_STRING([--with-internal-authentication], [use the internal database for authentication (default: yes)]),
    internal_authentication=$withval,
    internal_authentication=yes
)

AC_ARG_WITH([ldap_authentication], 
    AC_HELP_STRING([--with-ldap-authentication], [use a ldap server for authentication (default: no)]),
    ldap_authentication=$withval,
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
    ldap_binddn=["cn=admin,cn=config"]
)

AC_ARG_WITH([ldap_bindpass],
    AS_HELP_STRING([--with-ldap-bindpass=PASS], [password to use for retrieving user information via ldap (default admin)]),
    ldap_bindpass=$withval,
    ldap_bindpass=["admin"]
)

AC_ARG_WITH([ldap_rootdn],
    AS_HELP_STRING([--with-ldap-rootdn=LDAP_ATTRIBUTE], [root dn to use for user lookup (default ou=people, dc=icinga, dc=org)]),
    ldap_rootdn=$withval,
    ldap_rootdn=["ou=people, dc=icinga, dc=org"]
)

AC_ARG_WITH([ldap_user_objectclass],
    AS_HELP_STRING([--with-ldap-user-objectclass=LDAP_OBJECT_CLASS], [ldap object class to use for user authentication (default: inetOrgPerson)]),
    ldap_user_objectclass=$withval,
    ldap_user_objectclass="inetOrgPerson"
)

AC_ARG_WITH([ldap_attribute_username],
    AS_HELP_STRING([--with-ldap-attribute-username=LDAP_ATTRIBUTE], [user attribute to use for the username (default: uid)]),
    ldap_attribute_username=$withval,
    ldap_attribute_username="uid"
)

#
#   Icinga backend selection
#

AC_ARG_WITH([icinga_backend], 
    AS_HELP_STRING([--with-icinga-backend=(ido, statusdat, livestatus)], [backend to use for rb (default: statusdat)]),
    icinga_backend=$withval,
    icinga_backend="statusdat"
)

ICINGA_CHECK_BACKENDTYPE($icinga_backend, [--with-icinga-backend])

#
#   Ido settings
#

AC_ARG_WITH([ido_db_type],
    AS_HELP_STRING([--with-ido-db-type=(mysql, pgsql)], [database engine to use for retrieving data from the ido db (default: mysql)]),
    ido_db_type=$withval,
    ido_db_type="mysql"
)

ICINGA_CHECK_DBTYPE($ido_db_type, [--with-ido-db-type])

AC_ARG_WITH([ido_host],
    AS_HELP_STRING([--with-ido-host=HOST], [host to use for retrieving data from the ido db (default: localhost)]),
    ido_host=$withval,
    ido_host="localhost"
)

AC_ARG_WITH([ido_port],
    AS_HELP_STRING([--with-ido-port=PORT], [backend to use for retrieving data from the ido db (default: 3306 for mysql. 5432 for pgsql)]),
    ido_port=$withval,
    ido_port=db_default_port
)

AC_ARG_WITH([ido_db_name],
    AS_HELP_STRING([--with-ido-db-name=DATABASE], [database name to use for retrieving data from the ido db (default: icinga)]),
    ido_db_name=$withval,
    ido_db_name="icinga"
)

AC_ARG_WITH([ido_user],
    AS_HELP_STRING([--with-ido-user=USER], [user to use for retrieving data from the ido db (default: icinga)]),
    ido_user=$withval,
    ido_user="icinga"
)

AC_ARG_WITH([ido_password],
    AS_HELP_STRING([--with-ido-password=PASSWORD], [password to use for retrieving data from the ido db (default: icinga)]),
    ido_password=$withval,
    ido_password="icinga"
)

#
#   Statusdat file location
#

AC_ARG_WITH([statusdat_file],
    AS_HELP_STRING([--with-statusdat-file=FILE], [location of the status.dat file when retrieving data from status.dat (default: /usr/local/icinga/var/status.dat)]),
    statusdat_file=$withval,
    statusdat_file="/usr/local/icinga/var/status.dat"
)

AC_ARG_WITH([objects_cache_file],
    AS_HELP_STRING([--with-objects-cache-file=FILE], [location of the objects.cache file when retrieving data from status.dat (default: /usr/local/icinga/var/objects.cache)]),
    objects_cache_file=$withval,
    objects_cache_file="/usr/local/icinga/var/objects.cache"
)

#
#   Livestatus connection
#

AC_ARG_WITH([livestatus_socket], 
    AS_HELP_STRING([--with-livestatus-socket=FILE], [location of the livestatus socket (default: /usr/local/icinga/var/rw/live)]),
    livestatus_socket=$withval,
    livestatus_socket="/usr/local/icinga/var/rw/live"
)   

#
#   Icinga commandpipe
#

AC_ARG_WITH([icinga_commandpipe],
    AS_HELP_STRING([--with-icinga-commandpipe=FILE], [location of the command pipe used for sending commands (default: /usr/local/icinga/var/rw/icinga.cmd)]),
    icinga_commandpipe=$withval,
    icinga_commandpipe="/usr/local/icinga/var/rw/icinga.cmd"
)      

AC_ARG_WITH([objects_cache_file],
    AS_HELP_STRING([--with-objects-file=FILE], [location of the objects.cache file when retrieving data from status.dat (default: /usr/local/icinga/var/objects.cache)]),
    objects_cache_file=$withval,
    objects_cache_file="/usr/local/icinga/var/objects.cache"
)   

#
#   Livestatus connection
#

AC_ARG_WITH([livestatus_socket], 
    AS_HELP_STRING([--with-livestatus-socket=FILE], [location of the livestatus socket (default: /usr/local/icinga/var/rw/live)]),
    livestatus_socket=$withval,
    livestatus_socket="/usr/local/icinga/var/rw/live"
)   

#
# Icinga commandpipe
#

AC_ARG_WITH([icinga_commandpipe],
    AS_HELP_STRING([--with-icinga-commandpipe=FILE], [location of the command pipe used for sending commands (default: /usr/local/icinga/var/rw/icinga.cmd)]),
    icinga_commandpipe=$withval,
    icinga_commandpipe="/usr/local/icinga/var/rw/icinga.cmd"
)      

# Installation options
INSTALL_OPTS="-o $bin_user -g $bin_group"
INSTALL_OPTS_WEB="-o $web_user -g $web_group"

AS_IF([test "x$internal_db_type" = xmysql], [
    AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Mysql.php],[Zend Framework - MySQL PDO Adapter])
    AC_CHECK_PHP_MODULE([mysql])
    AS_IF([test "x$internal_db_port" == xdb_default_port], [internal_db_port=3306])
])

AS_IF([test "x$ido_db_type" = xmysql], [
    AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Mysql.php],[Zend Framework - MySQL PDO Adapter])
    AC_CHECK_PHP_MODULE([mysql])
    AS_IF([test "x$ido_port" = xdb_default_port], [ido_port=3306])
])

AS_IF([test "x$internal_db_type" = xpgsql], [
    AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Pgsql.php],[Zend Framework - PostgreSQL PDO Adapter])
    AC_CHECK_PHP_MODULE([pgsql])
    AS_IF([test "x$internal_db_port" = xdb_default_port], [internal_db_port=5432])
])

AS_IF([test "x$ido_db_type" = xpgsql], [
    AC_CHECK_PHP_INCLUDE([Zend/Db/Adapter/Pdo/Pgsql.php],[Zend Framework - PostgreSQL PDO Adapter])
    AC_CHECK_PHP_MODULE([pgsql])
    AS_IF([test "x$ido_port" = xdb_default_port], [ido_port=5432])
])

#
#   Disable authentication backends
#

ido_enabled="disabled        = \"1\""
statusdat_enabled="disabled        = \"1\""
livestatus_enabled="disabled        = \"1\""

AS_CASE([$icinga_backend],
    ["ido"], [ido_enabled=""],
    ["statusdat"], [statusdat_enabled=""],
    ["livestatus"], [livestatus_enabled=""],
    [statusdat_enabled=""])

ldap_auth_disabled="disabled    = \"1\""
AS_IF([test "x$ldap_authentication" != xno],
    AC_CHECK_PHP_MODULE([ldap])
    ldap_auth_disabled=""
)

internal_auth_disabled="disabled    = \"1\""
AS_IF([test "x$internal_authentication" != xno],
    AC_CHECK_PHP_MODULE([ldap])
    internal_auth_disabled=""
)

#
#   Substitution variables
#

# Installation directives
AC_SUBST(app_name)
AC_SUBST(web_user)
AC_SUBST(web_group)
AC_SUBST(web_path)
AC_SUBST(httpd_config_path)
AC_SUBST(bin_user)
AC_SUBST(bin_group)
AC_SUBST(icingaweb_config_path)
AC_SUBST(icingaweb_log_path)

# Internal db setup
AC_SUBST(internal_db_type)
AC_SUBST(internal_db_name)
AC_SUBST(internal_db_host)
AC_SUBST(internal_db_port)
AC_SUBST(internal_db_user)
AC_SUBST(internal_db_password)

# ldap setup 
AC_SUBST(ldap_host)
AC_SUBST(ldap_port)
AC_SUBST(ldap_rootdn)
AC_SUBST(ldap_binddn)
AC_SUBST(ldap_bindpass)
AC_SUBST(ldap_user_objectclass)
AC_SUBST(ldap_attribute_basedn)
AC_SUBST(ldap_attribute_username)

# backend setup
AC_SUBST(icinga_backend)

# ido backend variables
AC_SUBST(ido_enabled)
AC_SUBST(ido_db_type)
AC_SUBST(ido_host)
AC_SUBST(ido_port)
AC_SUBST(ido_db_name)
AC_SUBST(ido_user)
AC_SUBST(ido_password)

# status.dat backend
AC_SUBST(statusdat_enabled)
AC_SUBST(statusdat_file)
AC_SUBST(objects_cache_file)

# livestatus backend
AC_SUBST(livestatus_socket)
AC_SUBST(livestatus_enabled)

# command pipe
AC_SUBST(icinga_commandpipe)

# Comment placeholders for toggling backends

AC_SUBST(ldap_auth_disabled)
AC_SUBST(internal_auth_disabled)

# Application and installation
AC_SUBST(PHP)
AC_SUBST(INSTALL_OPTS)
AC_SUBST(INSTALL_OPTS_WEB)

#
#   Create config files
#
AC_CONFIG_FILES([
    Makefile
    config/authentication.ini
    config/config.ini
    config/resources.ini
    config/modules/monitoring/backends.ini
    config/modules/monitoring/instances.ini
    etc/apache/icingaweb.conf
    public/.htaccess
    public/index.php
])

#
#   Commit and write
#
AC_OUTPUT
