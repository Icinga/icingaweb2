<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

class Platform
{
    protected static $domain;
    protected static $hostname;
    protected static $fqdn;

    public static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public static function isLinux()
    {
        return strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX';
    }

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

    public static function getHostname()
    {
        if (self::$hostname === null) {
            self::discoverHostname();
        }
        return self::$hostname;
    }

    public static function getDomain()
    {
        if (self::$domain === null) {
            self::discoverHostname();
        }
        return self::$domain;
    }

    public static function getFqdn()
    {
        if (self::$fqdn === null) {
            self::discoverHostname();
        }
        return self::$fqdn;
    }

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
}
