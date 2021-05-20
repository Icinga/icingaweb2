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
function N_($messageId)
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
    function t($messageId, $context = null)
    {
        return StaticTranslator::$instance->translate($messageId, $context);
    }

    /**
     * @see Translator::translateInDomain() For the function documentation.
     */
    function mt($domain, $messageId, $context = null)
    {
        return StaticTranslator::$instance->translateInDomain($domain, $messageId, $context);
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function tp($messageId, $messageId2, $number, $context = null)
    {
        return StaticTranslator::$instance->translatePlural($messageId, $messageId2, $number, $context);
    }

    /**
     * @see Translator::translatePluralInDomain() For the function documentation.
     */
    function mtp($domain, $messageId, $messageId2, $number, $context = null)
    {
        return StaticTranslator::$instance->translatePluralInDomain(
            $domain,
            $messageId,
            $messageId2,
            $number,
            $context
        );
    }

} else {

    /**
     * @see Translator::translate() For the function documentation.
     */
    function t($messageId, $context = null)
    {
        return $messageId;
    }

    /**
     * @see Translator::translate() For the function documentation.
     */
    function mt($domain, $messageId, $context = null)
    {
        return $messageId;
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function tp($messageId, $messageId2, $number, $context = null)
    {
        if ((int) $number !== 1) {
            return $messageId2;
        }

        return $messageId;
    }

    /**
     * @see Translator::translatePlural() For the function documentation.
     */
    function mtp($domain, $messageId, $messageId2, $number, $context = null)
    {
        if ((int) $number !== 1) {
            return $messageId2;
        }

        return $messageId;
    }

}
