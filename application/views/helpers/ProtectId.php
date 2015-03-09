<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Class Zend_View_Helper_Util
 */
class Zend_View_Helper_ProtectId extends Zend_View_Helper_Abstract
{
    public function protectId($id) {
        return Zend_Controller_Front::getInstance()->getRequest()->protectId($id);
    }
}
