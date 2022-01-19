<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

use ipl\Stdlib\Contract\Translator;
use ipl\I18n\StaticTranslator;

/**
 * No-op translate
 *
 * Supposed to be used for marking a string as available for translation without actually translating it immediately.
 * The returned string is the one given in the input. This does only work with the standard gettext macros t() and mt().
 *
 * @param   string  $messageId
 *
 * @return  string
 */
function N_(string $messageId): string
{
    return $messageId;
}

// Workaround for test issues, this is required unless our tests are able to
// accomplish "real" bootstrapping
if (function_exists('t')) {
    return;
}

if (extension_loaded('gettext')) {

    /**
     * @see Translator::translate() For the function documentation.
     */
    function t(string $messageId, ?string $context = null): string
    {
        return StaticTranslator::$instance->translate($messageId, $context);
    }

    /**
     * @see Translator::translateInDomain() For the function documentation.
     */
    function mt(string $domain, string $messageId, ?string $context = null): string
    {
        return StaticTranslator::$instance->translateInDomain($domain, $messageId, $context);
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function tp(string $messageId, string $messageId2, ?int $number, ?string $context = null): string
    {
        return StaticTranslator::$instance->translatePlural($messageId, $messageId2, $number ?? 0, $context);
    }

    /**
     * @see Translator::translatePluralInDomain() For the function documentation.
     */
    function mtp(string $domain, string $messageId, string $messageId2, ?int $number, ?string $context = null): string
    {
        return StaticTranslator::$instance->translatePluralInDomain(
            $domain,
            $messageId,
            $messageId2,
            $number ?? 0,
            $context
        );
    }

} else {

    /**
     * @see Translator::translate() For the function documentation.
     */
    function t(string $messageId, ?string $context = null): string
    {
        return $messageId;
    }

    /**
     * @see Translator::translate() For the function documentation.
     */
    function mt(string $domain, string $messageId, ?string $context = null): string
    {
        return $messageId;
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function tp(string $messageId, string $messageId2, ?int $number, ?string $context = null): string
    {
        if ((int) $number !== 1) {
            return $messageId2;
        }

        return $messageId;
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function mtp(string $domain, string $messageId, string $messageId2, ?int $number, ?string $context = null): string
    {
        if ((int) $number !== 1) {
            return $messageId2;
        }

        return $messageId;
    }

}
