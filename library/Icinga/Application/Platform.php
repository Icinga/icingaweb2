<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
            self::$domain = array_shift(preg_split('~\.~', self::$hostname, 2));
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
     * Return whether the given Zend framework class exists
     *
     * @param   string  $name   The name of the class to check
     *
     * @return  bool
     */
    public static function zendClassExists($name)
    {
        if (class_exists($name)) {
            return true;
        }

        return (@include str_replace('_', '/', $name) . '.php') !== false;
    }
}
