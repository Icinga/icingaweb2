<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Translator;

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
