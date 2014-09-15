<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Translator;

if (extension_loaded('gettext')) {
    function t($messageId)
    {
        return Translator::translate($messageId, Translator::DEFAULT_DOMAIN);
    }

    function mt($domain, $messageId)
    {
        return Translator::translate($messageId, $domain);
    }

    function tp($messageId, $messageId2, $number)
    {
        return Translator::translatePlural($messageId, $messageId2, $number, Translator::DEFAULT_DOMAIN);
    }

    function mtp($domain, $messageId, $messageId2, $number)
    {
        return Translator::translatePlural($messageId, $messageId2, $number, $domain);
    }
} else {
    function t($messageId)
    {
        return $messageId;
    }

    function mt($domain, $messageId)
    {
        return $messageId;
    }

    function tp($messageId, $messageId2, $number)
    {
        if ($number === 0 || $number > 1 || $number < 0) {
            return $messageId2;
        }
        return $messageId;
    }

    function mt($domain, $messageId, $messageId2, $number)
    {
        if ($number === 0 || $number > 1 || $number < 0) {
            return $messageId2;
        }
        return $messageId;
    }
}
