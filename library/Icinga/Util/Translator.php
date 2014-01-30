<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use \Exception;

/**
 * Helper class to ease internationalization when using gettext
 */
class Translator
{
    /**
     * The default gettext domain used as fallback
     */
    const DEFAULT_DOMAIN = 'icinga';

    /**
     * The locale code that is used in the project
     */
    const DEFAULT_LOCALE = 'en_US';

    /**
     * Known gettext domains and directories
     *
     * @var array
     */
    private static $knownDomains = array();

    /**
     * Translate a string
     *
     * Falls back to the default domain in case the string cannot be translated using the given domain
     *
     * @param   string  $text       The string to translate
     * @param   string  $domain     The primary domain to use
     *
     * @return  string              The translated string
     *
     * @throws  Exception           In case the given domain is unknown
     */
    public static function translate($text, $domain)
    {
        if ($domain !== self::DEFAULT_DOMAIN && !array_key_exists($domain, self::$knownDomains)) {
            throw new Exception("Cannot translate string '$text' with unknown domain '$domain'");
        }

        $res = dgettext($domain, $text);
        if ($res === $text && $domain !== self::DEFAULT_DOMAIN) {
            return dgettext(self::DEFAULT_DOMAIN, $text);
        }
        return $res;
    }

    /**
     * Register a new gettext domain
     *
     * @param   string  $name       The name of the domain to register
     * @param   string  $directory  The directory where message catalogs can be found
     *
     * @throws  Exception           In case the domain was not successfully registered
     */
    public static function registerDomain($name, $directory)
    {
        if (bindtextdomain($name, $directory) === false) {
            throw new Exception("Cannot register domain '$name' with path '$directory'");
        }
        bind_textdomain_codeset($name, 'UTF-8');
        self::$knownDomains[$name] = $directory;
    }

    /**
     * Set the locale to use
     *
     * @param   string  $localeName     The name of the locale to use
     *
     * @throws  Exception               In case the locale's name is invalid
     */
    public static function setupLocale($localeName)
    {
        if (setlocale(LC_ALL, $localeName . '.UTF-8') === false) {
            throw new Exception("Cannot set locale '$localeName.UTF-8' for category 'LC_ALL'");
        }
        putenv('LC_ALL=' . $localeName . '.UTF-8'); // Failsafe, Win and Unix
        putenv('LANG=' . $localeName . '.UTF-8'); // Windows fix, untested
    }

    /**
     * Return a list of all locale codes currently available in the known domains
     *
     * @return  array
     */
    public static function getAvailableLocaleCodes()
    {
        $codes = array();

        foreach (array_values(self::$knownDomains) as $directory) {
            $dh = opendir($directory);
            while (false !== ($name = readdir($dh))) {
                if (!preg_match('@\.|\.\.@', $name) && is_dir($directory . DIRECTORY_SEPARATOR . $name)) {
                    $codes[] = $name;
                }
            }
        }

        return $codes;
    }
}
