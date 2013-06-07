<?php

if (function_exists('_')) {
    function t($messageId = null)
    {
        $msg = _($messageId);
        if (! $msg) {
            return $messageId;
        }
        return $msg;
    }

    function mt($domain, $messageId = null)
    {
        $msg = dgettext($domain, $messageId);
        if (! $msg) {
            return $messageId;
        }
        return $msg;
    }
} else {
    function t($messageId = null)
    {
        return $messageId;
    }

    function mt($domain, $messageId = null)
    {
        return $messageId;
    }
}
