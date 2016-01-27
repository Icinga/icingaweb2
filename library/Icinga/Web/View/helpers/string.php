<?php
namespace Icinga\Web\View;

use Icinga\Util\StringHelper;

$this->addHelperFunction('ellipsis', function ($string, $maxLength, $ellipsis = '...') {
    return StringHelper::ellipsis($string, $maxLength, $ellipsis);
});

$this->addHelperFunction('nl2br', function ($string) {
   return str_replace(array('\r\n', '\r', '\n'), '<br>', $string);
});
