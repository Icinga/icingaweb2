<?php

class Zend_View_Helper_CheckSource extends Zend_View_Helper_Abstract
{
    protected static $purifier;

    public function checkSource($source)
    {
        if (empty($source)) {
            return '';
        }
        return $this->view->escape($source);
    }
}
