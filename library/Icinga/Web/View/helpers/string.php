<?php
namespace Icinga\Web\View;

use Icinga\Util\String;

$this->addHelperFunction('ellipsis', function ($string, $maxLength, $ellipsis = '...') {
    return String::ellipsis($string, $maxLength, $ellipsis);
});
