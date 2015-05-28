<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_View_Helper_Abstract;
use Icinga\Module\Monitoring\Object\Macro;

class Zend_View_Helper_ResolveMacros extends Zend_View_Helper_Abstract
{
    public function resolveMacros($input, $object)
    {
        return Macro::resolveMacros($input, $object);
    }

    public function resolveMacro($macro, $object)
    {
        return Macro::resolveMacro($macro, $object);
    }
}
