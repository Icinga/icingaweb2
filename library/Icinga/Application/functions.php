<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

use Icinga\Util\Translator;

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
     * (non-PHPDoc)
     * @see Translator::translate() For the function documentation.
     */
    function t($messageId, $context = null)
    {
        return Translator::translate($messageId, Translator::DEFAULT_DOMAIN, $context);
    }

    /**
     * (non-PHPDoc)
     * @see Translator::translate() For the function documentation.
     */
    function mt($domain, $messageId, $context = null)
    {
        return Translator::translate($messageId, $domain, $context);
    }

    /**
     * (non-PHPDoc)
     * @see Translator::translatePlural() For the function documentation.
     */
    function tp($messageId, $messageId2, $number, $context = null)
    {
        return Translator::translatePlural($messageId, $messageId2, $number, Translator::DEFAULT_DOMAIN, $context);
    }

    /**
     * (non-PHPDoc)
     * @see Translator::translatePlural() For the function documentation.
     */
    function mtp($domain, $messageId, $messageId2, $number, $context = null)
    {
        return Translator::translatePlural($messageId, $messageId2, $number, $domain, $context);
    }

} else {

    /**
     * (non-PHPDoc)
     * @see Translator::translate() For the function documentation.
     */
    function t($messageId, $context = null)
    {
        return $messageId;
    }

    /**
     * (non-PHPDoc)
     * @see Translator::translate() For the function documentation.
     */
    function mt($domain, $messageId, $context = null)
    {
        return $messageId;
    }

    /**
     * (non-PHPDoc)
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
     * (non-PHPDoc)
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
