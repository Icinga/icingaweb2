<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Util\Translator;

if (extension_loaded('gettext')) {
    function t($messageId)
    {
        return Translator::translate($messageId, Translator::DEFAULT_DOMAIN);
    }

    function mt($domain, $messageId)
    {
        return Translator::translate($messageId, $domain);
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
