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
    function mtp($domain, $messageId, $messageId2, $n)
    {
        return Translator::translatePlural($messageId, $messageId2, $n, $domain);
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
}
