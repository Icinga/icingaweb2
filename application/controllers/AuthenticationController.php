<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Authentication\Auth;
use Icinga\Web\Notification;

/**
 * Class AuthenticationController
 * @package Icinga\Application\Controllers
 */
class AuthenticationController extends ActionController
{
    /**
     * @var bool
     */
    protected $handlesAuthentication = true;

    /**
     * @var bool
     */
    protected $modifiesSession = true;

    /**
     *
     */
    public function loginAction()
    {
        $this->replaceLayout = true;
        $this->view->form = $this->widget('form', array('name' => 'login'));
    }

    /**
     *
     */
    public function logoutAction()
    {
        $this->replaceLayout = true;
        Auth::getInstance()->forgetAuthentication();
        Notification::success('You have been logged out');
        $this->_forward('login');
    }
}

// @codingStandardsIgnoreEnd