<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
     * Return the Linux distribution's name
     * or 'linux' if the name could not be found out
     * or false if the OS isn't Linux or an error occurred
     *
     * @param int $reliable
     *      3: Only parse /etc/os-release (or /usr/lib/os-release).
     *          For the paranoid ones.
     *      2: If that (3) doesn't help, check /etc/*-release, too.
     *          If something is unclear, return 'linux'.
     *      1: Almost equal to mode 2. The possible return values also include:
     *          'redhat' -- unclear whether RHEL/Fedora/...
     *          'suse' -- unclear whether SLES/openSUSE/...
     *      0: If even that (1) doesn't help, check /proc/version, too.
     *          This may not work (as expected) on LXC containers!
     *          (No reliability at all!)
     *
     * @return string|bool
     */
    public static function getLinuxDistro($reliable = 2)
    {
        if (! self::isLinux()) {
            return false;
        }

        foreach (array('/etc/os-release', '/usr/lib/os-release') as $osReleaseFile) {
            if (false === ($osRelease = @file(
                $osReleaseFile,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            ))) {
                continue;
            }

            foreach ($osRelease as $osInfo) {
                if (false === ($res = @preg_match('/(?<!.)[ \t]*#/ms', $osInfo))) {
                    return false;
                }
                if ($res === 1) {
                    continue;
                }

                $matches = array();
                if (false === ($res = @preg_match(
                    '/(?<!.)[ \t]*ID[ \t]*=[ \t]*(\'|"|)(.*?)(?:\1)[ \t]*(?!.)/msi',
                    $osInfo,
                    $matches
                ))) {
                    return false;
                }
                if (! ($res === 0 || $matches[2] === '' || $matches[2] === 'linux')) {
                    return $matches[2];
                }
            }
        }

        if ($reliable > 2) {
            return 'linux';
        }

        foreach (array(
            'fedora' => '/etc/fedora-release',
            'centos' => '/etc/centos-release'
        ) as $distro => $releaseFile) {
            if (! (false === (
                $release = @file_get_contents($releaseFile)
            ) || false === strpos(strtolower($release), $distro))) {
                return $distro;
            }
        }

        if (false !== ($release = @file_get_contents('/etc/redhat-release'))) {
            $release = strtolower($release);
            if (false !== strpos($release, 'red hat enterprise linux')) {
                return 'rhel';
            }
            foreach (array('fedora', 'centos') as $distro) {
                if (false !== strpos($release, $distro)) {
                    return $distro;
                }
            }
            return $reliable < 2 ? 'redhat' : 'linux';
        }

        if (false !== ($release = @file_get_contents('/etc/SuSE-release'))) {
            $release = strtolower($release);
            foreach (array(
                'opensuse'  => 'opensuse',
                'sles'      => 'suse linux enterprise server',
                'sled'      => 'suse linux enterprise desktop'
            ) as $distro => $name) {
                if (false !== strpos($release, $name)) {
                    return $distro;
                }
            }
            return $reliable < 2 ? 'suse' : 'linux';
        }

        if ($reliable < 1) {
            if (false === ($procVersion = @file_get_contents('/proc/version'))) {
                return false;
            }
            $procVersion = strtolower($procVersion);
            foreach (array(
                'redhat'    => 'red hat',
                'suse'      => 'suse linux',
                'ubuntu'    => 'ubuntu',
                'debian'    => 'debian'
            ) as $distro => $name) {
                if (false !== strpos($procVersion, $name)) {
                    return $distro;
                }
            }
        }

        return 'linux';
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
     * Return whether it's possible to connect to a LDAP server
     *
     * Checks whether the ldap extension is loaded
     *
     * @return  bool
     */
    public static function hasLdapSupport()
    {
        return static::extensionLoaded('ldap');
    }

    /**
     * Return whether it's possible to connect to any of the supported database servers
     *
     * @return bool
     */
    public static function hasDatabaseSupport()
    {
        return static::hasMssqlSupport() || static::hasMysqlSupport() || static::hasOciSupport()
            || static::hasOracleSupport() || static::hasPostgresqlSupport();
    }

    /**
     * Return whether it's possible to connect to a MSSQL database
     *
     * Checks whether the mssql/dblib pdo or sqlsrv extension has
     * been loaded and Zend framework adapter for MSSQL is available
     *
     * @return  bool
     */
    public static function hasMssqlSupport()
    {
        if ((static::extensionLoaded('mssql') || static::extensionLoaded('pdo_dblib'))
            && static::classExists('Zend_Db_Adapter_Pdo_Mssql')
        ) {
            return true;
        }

        return static::extensionLoaded('sqlsrv') && static::classExists('Zend_Db_Adapter_Sqlsrv');
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
        return static::extensionLoaded('pdo_mysql') && static::classExists('Zend_Db_Adapter_Pdo_Mysql');
    }

    /**
     * Return whether it's possible to connect to a IBM DB2 database
     *
     * Checks whether the ibm pdo extension has been loaded and the Zend framework adapter for IBM is available
     *
     * @return  bool
     */
    public static function hasIbmSupport()
    {
        return static::extensionLoaded('pdo_ibm') && static::classExists('Zend_Db_Adapter_Pdo_Ibm');
    }

    /**
     * Return whether it's possible to connect to a Oracle database using OCI8
     *
     * Checks whether the OCI8 extension has been loaded and the Zend framework adapter for Oracle is available
     *
     * @return  bool
     */
    public static function hasOciSupport()
    {
        return static::extensionLoaded('oci8') && static::classExists('Zend_Db_Adapter_Oracle');
    }

    /**
     * Return whether it's possible to connect to a Oracle database using PDO_OCI
     *
     * Checks whether the OCI PDO extension has been loaded and the Zend framework adapter for Oci is available
     *
     * @return  bool
     */
    public static function hasOracleSupport()
    {
        return static::extensionLoaded('pdo_oci') && static::classExists('Zend_Db_Adapter_Pdo_Mysql');
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
        return static::extensionLoaded('pdo_pgsql') && static::classExists('Zend_Db_Adapter_Pdo_Pgsql');
    }
}
