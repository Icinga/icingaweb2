<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Authentication\Manager;

/**
 * Class Zend_View_Helper_Auth
 */
class Zend_View_Helper_Auth extends Zend_View_Helper_Abstract
{
    public function auth()
    {
        return Manager::getInstance();
    }
}

// @codingStandardsIgnoreEnd