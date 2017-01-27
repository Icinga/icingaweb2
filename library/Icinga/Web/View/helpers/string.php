<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Util\StringHelper;

$this->addHelperFunction('ellipsis', function ($string, $maxLength, $ellipsis = '...') {
    return StringHelper::ellipsis($string, $maxLength, $ellipsis);
});

$this->addHelperFunction('nl2br', function ($string) {
    return nl2br(str_replace(array('\r\n', '\r', '\n'), '<br>', $string), false);
});
