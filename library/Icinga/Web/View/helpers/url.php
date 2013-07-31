<?php

namespace Icinga\Web\View;

use Icinga\Web\Url;

function url($path = null, $params = null)
{
    if ($path === null) {
        $url = Url::fromRequest();
        if ($params !== null) {
            $url->setParams($params);
        }
    } else {
        $url = Url::fromPath($path, $params);
    }
    return $url;
}

