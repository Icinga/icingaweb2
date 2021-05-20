<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use ipl\I18n\GettextTranslator;
use ipl\I18n\Locale;
use ipl\I18n\StaticTranslator;

/**
 * Helper class to ease internationalization when using gettext
 *
 * @deprecated Use {@see \ipl\I18n\StaticTranslator::$instance} or {@see \ipl\I18n\Translation} instead
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
     * Translate a string
     *
     * Falls back to the default domain in case the string cannot be translated using the given domain
     *
     * @param   string      $text       The string to translate
     * @param   string      $domain     The primary domain to use
     * @param   string|null $context    Optional parameter for context based translation
     *
     * @return  string                  The translated string
     */
    public static function translate($text, $domain, $context = null)
    {
        return StaticTranslator::$instance->translateInDomain($domain, $text, $context);
    }

    /**
     * Translate a plural string
     *
     * Falls back to the default domain in case the string cannot be translated using the given domain
     *
     * @param   string      $textSingular   The string in singular form to translate
     * @param   string      $textPlural     The string in plural form to translate
     * @param   integer     $number         The amount to determine from whether to return singular or plural
     * @param   string      $domain         The primary domain to use
     * @param   string|null $context        Optional parameter for context based translation
     *
     * @return string                       The translated string
     */
    public static function translatePlural($textSingular, $textPlural, $number, $domain, $context = null)
    {
        return StaticTranslator::$instance->translatePluralInDomain(
            $domain,
            $textSingular,
            $textPlural,
            $number,
            $context
        );
    }

    /**
     * Emulated pgettext()
     *
     * @link http://php.net/manual/de/book.gettext.php#89975
     *
     * @param $text
     * @param $domain
     * @param $context
     *
     * @return string
     */
    public static function pgettext($text, $domain, $context)
    {
        return StaticTranslator::$instance->translateInDomain($domain, $text, $context);
    }

    /**
     * Emulated pngettext()
     *
     * @link http://php.net/manual/de/book.gettext.php#89975
     *
     * @param $textSingular
     * @param $textPlural
     * @param $number
     * @param $domain
     * @param $context
     *
     * @return string
     */
    public static function pngettext($textSingular, $textPlural, $number, $domain, $context)
    {
        return StaticTranslator::$instance->translatePluralInDomain(
            $domain,
            $textSingular,
            $textPlural,
            $number,
            $context
        );
    }

    /**
     * Register a new gettext domain
     *
     * @param   string  $name       The name of the domain to register
     * @param   string  $directory  The directory where message catalogs can be found
     *
     * @return  void
     */
    public static function registerDomain($name, $directory)
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        $translator->addTranslationDirectory($directory, $name);
    }

    /**
     * Set the locale to use
     *
     * @param   string  $localeName     The name of the locale to use
     *
     * @return  void
     */
    public static function setupLocale($localeName)
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        $translator->setLocale($localeName);
    }

    /**
     * Split and return the language code and country code of the given locale or the current locale
     *
     * @param   string  $locale     The locale code to split, or null to split the current locale
     *
     * @return  object              An object with a 'language' and 'country' attribute
     */
    public static function splitLocaleCode($locale = null)
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        if ($locale === null) {
            $locale = $translator->getLocale();
        }

        return (new Locale())->parseLocale($locale);
    }

    /**
     * Return a list of all locale codes currently available in the known domains
     *
     * @return  array
     */
    public static function getAvailableLocaleCodes()
    {
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        return $translator->listLocales();
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
        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        return (new Locale())->getPreferred($header, $translator->listLocales());
    }
}
