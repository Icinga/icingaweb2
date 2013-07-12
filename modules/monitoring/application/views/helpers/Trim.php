<?php

class Zend_View_Helper_Trim extends Zend_View_Helper_Abstract
{
    public function start()
    {
        ob_start("Zend_View_Helper_Trim::trimfunc");
    }

    public static function trimfunc($string)
    {
        return trim($string);
    }

    public function end()
    {
        ob_end_flush();
    }
}
