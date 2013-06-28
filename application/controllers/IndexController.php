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
        if ($this->action_name !== 'welcome') {
            $this->_forward('welcome');
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
