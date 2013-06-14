<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Application\Icinga;

/**
 * Class IndexController
 * @package Icinga\Application\Controllers
 */
class IndexController extends ActionController
{
    /**
     * @var bool
     */
    protected $modifiesSession = true;

    /**
     *
     */
    public function preDispatch()
    {
        parent::preDispatch(); // -> auth :(
        $enabled = Icinga::app()->moduleManager()->listEnabledModules();
        $default = array('docs', 'certificates');
        if (count(array_diff($enabled, $default)) === 0) {
            if ($this->action_name !== 'welcome') {
                $this->_forward('welcome');
            }
        } else {
            $this->_forward('index', 'dashboard');
        }
    }

    /**
     *
     */
    public function welcomeAction()
    {
    }
}

// @codingStandardsIgnoreEnd