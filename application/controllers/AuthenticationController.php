<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Authentication\Backend\AutoLoginBackend;
use Icinga\Web\Controller\ActionController;
use Icinga\Form\Authentication\LoginForm;
use Icinga\Authentication\AuthChain;
use Icinga\Application\Config;
use Icinga\Logger\Logger;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use Icinga\Web\Url;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends ActionController
{
    /**
     * This controller does not require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Log into the application
     */
    public function loginAction()
    {
        $auth = $this->Auth();
        $this->view->form = new LoginForm();
        $this->view->form->setRequest($this->_request);
        $this->view->title = $this->translate('Icingaweb Login');

        try {
            $redirectUrl = Url::fromPath($this->params->get('redirect', 'dashboard'));

            if ($auth->isAuthenticated()) {
                $this->rerenderLayout()->redirectNow($redirectUrl);
            }

            try {
                $config = Config::app('authentication');
            } catch (NotReadableError $e) {
                Logger::error(
                    new Exception('Cannot load authentication configuration. An exception was thrown:', 0, $e)
                );
                throw new ConfigurationError(
                    t(
                        'No authentication methods available. Authentication configuration could not be loaded.'
                        . ' Please check the system log or Icinga Web 2 log for more information'
                    )
                );
            }

            $chain = new AuthChain($config);
            if ($this->getRequest()->isGet()) {
                $user = new User('');
                foreach ($chain as $backend) {
                    if ($backend instanceof AutoLoginBackend) {
                        $authenticated  = $backend->authenticate($user);
                        if ($authenticated === true) {
                            $auth->setAuthenticated($user);
                            $this->rerenderLayout()->redirectNow($redirectUrl);
                        }
                    }
                }
            } elseif ($this->view->form->isSubmittedAndValid()) {
                $user = new User($this->view->form->getValue('username'));
                $password = $this->view->form->getValue('password');
                $backendsTried = 0;
                $backendsWithError = 0;

                foreach ($chain as $backend) {
                    if ($backend instanceof AutoLoginBackend) {
                        continue;
                    }
                    ++$backendsTried;
                    try {
                        $authenticated = $backend->authenticate($user, $password);
                    } catch (AuthenticationException $e) {
                        Logger::error($e);
                        ++$backendsWithError;
                        continue;
                    }
                    if ($authenticated === true) {
                        $auth->setAuthenticated($user);
                        $this->rerenderLayout()->redirectNow($redirectUrl);
                    }
                }
                if ($backendsTried === 0) {
                    throw new ConfigurationError(
                        t(
                            'No authentication methods available. It seems that no authentication method has been set'
                            . ' up. Please check the system log or Icinga Web 2 log for more information'
                        )
                    );
                }
                if ($backendsTried === $backendsWithError) {
                    throw new ConfigurationError(
                        $this->translate(
                            'No authentication methods available. It seems that all set up authentication methods have'
                            . ' errors. Please check the system log or Icinga Web 2 log for more information'
                        )
                    );
                }
                if ($backendsWithError) {
                    $this->view->form->addNote(
                        $this->translate(
                            'Note that not all authentication backends are available for authentication because they'
                            . ' have errors. Please check the system log or Icinga Web 2 log for more information'
                        )
                    );
                }
                $this->view->form->getElement('password')->addError($this->translate('Incorrect username or password'));
            }
        } catch (Exception $e) {
            $this->view->errorInfo = $e->getMessage();
        }
    }

    /**
     * Log out the current user
     */
    public function logoutAction()
    {
        $auth = $this->Auth();
        $auth->removeAuthorization();

        if ($auth->isAuthenticatedFromRemoteUser()) {
            $this->_helper->layout->setLayout('login');
            $this->_response->setHttpResponseCode(401);
        } else {
            $this->rerenderLayout()->redirectToLogin();
        }
    }
}
