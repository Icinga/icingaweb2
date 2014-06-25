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
            putenv('LC_ALL=' . $locale); // Failsafe, Win and Unix
            putenv('LANG=' . $locale); // Windows fix, untested
        }
    }

    /**
     * Split and return the language code and country code of the given locale or the current locale
     *
     * @param   string  $locale     The locale code to split, or null to split the current locale
     *
     * @return  stdClass            An object with a 'language' and 'country' attribute
     */
    public static function splitLocaleCode($locale = null)
    {
        $matches = array();
        $locale = $locale !== null ? $locale : setlocale(LC_ALL, 0);
        if (preg_match('@([a-z]{2})[_-]([A-Z]{2})@', $locale, $matches)) {
            list($languageCode, $countryCode) = array_slice($matches, 1);
        } elseif ($locale === 'C') {
            list($languageCode, $countryCode) = preg_split('@[_-]@', static::DEFAULT_LOCALE, 2);
        } else {
            $languageCode = $locale;
            $countryCode = null;
        }

        return (object) array('language' => $languageCode, 'country' => $countryCode);
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

    /**
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @param   string  $header     The HTTP "Accept-Language" header
     *
     * @return  string              The browser's preferred locale code
     */
    public static function getPreferredLocaleCode($header)
    {
        $headerValues = explode(',', $header);
        for ($i = 0; $i < count($headerValues); $i++) {
            // In order to accomplish a stable sort we need to take the original
            // index into account as well during element comparison
            $headerValues[$i] = array($headerValues[$i], $i);
        }
        usort( // Sort DESC but keep equal elements ASC
            $headerValues,
            function ($a, $b) {
                $qValA = (float) (strpos($a[0], ';') > 0 ? substr(array_pop((explode(';', $a[0], 2))), 2) : 1);
                $qValB = (float) (strpos($b[0], ';') > 0 ? substr(array_pop((explode(';', $b[0], 2))), 2) : 1);
                return $qValA < $qValB ? 1 : ($qValA > $qValB ? -1 : ($a[1] > $b[1] ? 1 : ($a[1] < $b[1] ? -1 : 0)));
            }
        );
        for ($i = 0; $i < count($headerValues); $i++) {
            // We need to reset the array to its original structure once it's sorted
            $headerValues[$i] = $headerValues[$i][0];
        }
        $requestedLocales = array();
        foreach ($headerValues as $headerValue) {
            if (strpos($headerValue, ';') > 0) {
                $parts = explode(';', $headerValue, 2);
                $headerValue = $parts[0];
            }
            $requestedLocales[] = str_replace('-', '_', $headerValue);
        }

        $similarMatch = null;
        $availableLocales = static::getAvailableLocaleCodes();
        $perfectMatch = array_shift((array_intersect($requestedLocales, $availableLocales)));
        foreach ($requestedLocales as $requestedLocale) {
            if ($perfectMatch === $requestedLocale) {
                // The perfect match must be preferred when reached before a similar match is found
                return $perfectMatch;
            }
            $similarMatches = array();
            $localeObj = static::splitLocaleCode($requestedLocale);
            foreach ($availableLocales as $availableLocale) {
                if (static::splitLocaleCode($availableLocale)->language === $localeObj->language) {
                    $similarMatches[] = $availableLocale;
                }
            }
            if (!empty($similarMatches)) {
                $similarMatch = array_shift($similarMatches); // There is no "best" similar match, just use the first
                break;
            }
        }

        if (!$perfectMatch && $similarMatch) {
            return $similarMatch;
        } elseif ($similarMatch && static::splitLocaleCode($similarMatch)->language === static::splitLocaleCode($perfectMatch)->language) {
            return $perfectMatch;
        } elseif ($similarMatch) {
            return $similarMatch;
        }

        return static::DEFAULT_LOCALE;
    }
}
