<?php
namespace Icinga\Web\View;

use Icinga\Util\String;

$this->addHelperFunction('ellipsis', function ($string, $maxLength, $ellipsis = '...') {
    return String::ellipsis($string, $maxLength, $ellipsis);
});

$this->addHelperFunction('nl2br', function ($string) {
   return str_replace(array('\r\n', '\r', '\n'), '<br>', $string);
});
