<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

/**
 * Platform tests for icingaweb
 */
class Platform
{
    /**
     * Domain name
     *
     * @var string
     */
    protected static $domain;

    /**
     * Host name
     *
     * @var string
     */
    protected static $hostname;

    /**
     * Fully qualified domain name
     *
     * @var string
     */
    protected static $fqdn;

    /**
     * Return the operating system's name
     *
     * @return  string
     */
    public static function getOperatingSystemName()
    {
        return php_uname('s');
    }

    /**
     * Test of windows
     *
     * @return bool
     */
    public static function isWindows()
    {
        return strtoupper(substr(self::getOperatingSystemName(), 0, 3)) === 'WIN';
    }

    /**
     * Test of linux
     *
     * @return bool
     */
    public static function isLinux()
    {
        return strtoupper(substr(self::getOperatingSystemName(), 0, 5)) === 'LINUX';
    }

    /**
     * Test of CLI environment
     *
     * @return bool
     */
    public static function isCli()
    {
        if (PHP_SAPI == 'cli') {
            return true;
        } elseif ((PHP_SAPI == 'cgi' || PHP_SAPI == 'cgi-fcgi')
            && empty($_SERVER['SERVER_NAME'])) {
            return true;
        }
        return false;
    }

    /**
     * Get the hostname
     *
     * @return string
     */
    public static function getHostname()
    {
        if (self::$hostname === null) {
            self::discoverHostname();
        }
        return self::$hostname;
    }

    /**
     * Get the domain name
     *
     * @return string
     */
    public static function getDomain()
    {
        if (self::$domain === null) {
            self::discoverHostname();
        }
        return self::$domain;
    }

    /**
     * Get the fully qualified domain name
     *
     * @return string
     */
    public static function getFqdn()
    {
        if (self::$fqdn === null) {
            self::discoverHostname();
        }
        return self::$fqdn;
    }

    /**
     * Initialize domain and host strings
     */
    protected static function discoverHostname()
    {
        self::$hostname = gethostname();
        self::$fqdn = gethostbyaddr(gethostbyname(self::$hostname));

        if (substr(self::$fqdn, 0, strlen(self::$hostname)) === self::$hostname) {
            self::$domain = substr(self::$fqdn, strlen(self::$hostname) + 1);
        } else {
            $parts = preg_split('~\.~', self::$hostname, 2);
            self::$domain = array_shift($parts);
        }
    }

    /**
     * Return the version of PHP
     *
     * @return  string
     */
    public static function getPhpVersion()
    {
        return phpversion();
    }

    /**
     * Return the username PHP is running as
     *
     * @return  string
     */
    public static function getPhpUser()
    {
        if (static::isWindows()) {
            return get_current_user(); // http://php.net/manual/en/function.get-current-user.php#75059
        }

        if (function_exists('posix_geteuid')) {
            $userInfo = posix_getpwuid(posix_geteuid());
            return $userInfo['name'];
        }
    }

    /**
     * Test for php extension
     *
     * @param   string  $extensionName  E.g. mysql, ldap
     *
     * @return  bool
     */
    public static function extensionLoaded($extensionName)
    {
        return extension_loaded($extensionName);
    }

    /**
     * Return the value for the given PHP configuration option
     *
     * @param   string  $option     The option name for which to return the value
     *
     * @return  string|false
     */
    public static function getPhpConfig($option)
    {
        return ini_get($option);
    }

    /**
     * Return whether the given class exists
     *
     * @param   string  $name   The name of the class to check
     *
     * @return  bool
     */
    public static function classExists($name)
    {
        if (@class_exists($name)) {
            return true;
        }

        if (strpos($name, '_') !== false) {
            // Assume it's a Zend-Framework class
            return (@include str_replace('_', '/', $name) . '.php') !== false;
        }

        return false;
    }

    /**
     * Return whether it's possible to connect to a MySQL database
     *
     * Checks whether the mysql pdo extension has been loaded and the Zend framework adapter for MySQL is available
     *
     * @return  bool
     */
    public static function hasMysqlSupport()
    {
        return static::extensionLoaded('mysql') && static::classExists('Zend_Db_Adapter_Pdo_Mysql');
    }

    /**
     * Return whether it's possible to connect to a PostgreSQL database
     *
     * Checks whether the pgsql pdo extension has been loaded and the Zend framework adapter for PostgreSQL is available
     *
     * @return  bool
     */
    public static function hasPostgresqlSupport()
    {
        return static::extensionLoaded('pgsql') && static::classExists('Zend_Db_Adapter_Pdo_Pgsql');
    }
}
