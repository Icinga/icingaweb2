<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Authentication\Credentials as Credentials;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Form\Builder as FormBuilder;

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

    private function getAuthForm()
    {
        return array(
            'username' => array(
                'text',
                array(
                    'label' => t('Username'),
                    'required' => true,
                )
            ),
            'password' => array(
                'password',
                array(
                    'label' => t('Password'),
                    'required' => true
                )
            ),
            'submit' => array(
                'submit',
                array(
                    'label' => t('Login'),
                    'class' => 'pull-right'
                )
            )
        );
    }

    /**
     *
     */
    public function loginAction()
    {
        $this->replaceLayout = true;
        $credentials = new Credentials();
        $this->view->form = FormBuilder::fromArray(
            $this->getAuthForm(),
            array(
                "CSRFProtection" => false, // makes no sense here
                "model" => &$credentials
            )
        );
        try {
            $auth = AuthManager::getInstance(null, array(
                "writeSession" => true 
            ));
            if ($auth->isAuthenticated()) {
                $this->redirectNow('index?_render=body');
            }
            if ($this->getRequest()->isPost() && $this->view->form->isSubmitted()) {
                $this->view->form->repopulate();
                if ($this->view->form->isValid()) {
                    if (!$auth->authenticate($credentials)) {
                        $this->view->form->getElement('password')->addError(t('Please provide a valid username and password'));
                    } else {
                        $this->redirectNow('index?_render=body');
                    }
                }
            }
        } catch (\Icinga\Exception\ConfigurationError $configError) {
            $this->view->errorInfo = $configError->getMessage();
        }
    }

    /**
     *
     */
    public function logoutAction()
    {
        $auth = AuthManager::getInstance(null, array(
            "writeSession" => true 
        ));
        $this->replaceLayout = true;
        $auth->removeAuthorization();
        $this->_forward('login');
    }
}

// @codingStandardsIgnoreEnd
