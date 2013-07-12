<?php

namespace Icinga\Web\View;

use Icinga\Web\Url;

function url($path = null, $params = null)
{
    if ($path === null) {
        $url = Url::current();
        if ($params !== null) {
            $url->setParams($params);
        }
    } else {
        $url = Url::create($path, $params);
    }
    return $url;
}

