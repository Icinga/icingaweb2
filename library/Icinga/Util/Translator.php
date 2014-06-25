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

use Exception;

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

    protected static $locale = 'C';

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
     */
    public static function translate($text, $domain)
    {
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
        if (setlocale(LC_ALL, $localeName . '.UTF-8') === false && setlocale(LC_ALL, $localeName) === false) {
            setlocale(LC_ALL, 'C'); // C == "use whatever is hardcoded"
            if ($localeName !== self::DEFAULT_LOCALE) {
                throw new Exception("Cannot set locale '$localeName' for category 'LC_ALL'");
            }
        } else {
            $locale = setlocale(LC_ALL, 0);
            self::$locale = $locale;
            putenv('LC_ALL=' . $locale); // Failsafe, Win and Unix
            putenv('LANG=' . $locale); // Windows fix, untested
        }
    }

    public static function getLocale()
    {
        return self::$locale;
    }

    public static function getLanguage()
    {
        return self::$locale === 'C' ? 'en' : substr(self::$locale, 0, 2);
    }

    /**
     * Return a list of all locale codes currently available in the known domains
     *
     * @return  array
     */
    public static function getAvailableLocaleCodes()
    {
        $codes = array();
        $postfix = '.UTF-8';
        foreach (array_values(self::$knownDomains) as $directory) {
            $dh = opendir($directory);
            while (false !== ($name = readdir($dh))) {
                if (substr($name, 0, 1) === '.') continue;
                if (substr($name, -6) !== $postfix) continue;
                if (is_dir($directory . DIRECTORY_SEPARATOR . $name)) {
                    $codes[] = substr($name, 0, -6);
                }
            }
        }
        return $codes;
    }
}
