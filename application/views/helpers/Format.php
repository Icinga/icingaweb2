<?php

use Icinga\Util\Format;

class Zend_View_Helper_Format extends Zend_View_Helper_Abstract
{
    public function format()
    {
        return Format::getInstance();
    }
}

