<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Authentication\Backend\AutoLoginBackend;
use Icinga\Web\Controller\ActionController;
use Icinga\Form\Authentication\LoginForm;
use Icinga\Authentication\AuthChain;
use Icinga\Application\Config;
use Icinga\Application\Logger;
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
        $this->view->form = $form = new LoginForm();
        $this->view->title = $this->translate('Icingaweb Login');

        try {
            $redirectUrl = $this->view->form->getValue('redirect');
            if ($redirectUrl) {
                $redirectUrl = Url::fromPath($redirectUrl);
            } else {
                $redirectUrl = Url::fromPath('dashboard');
            }

            if ($auth->isAuthenticated()) {
                $this->rerenderLayout()->redirectNow($redirectUrl);
            }

            try {
                $config = Config::app('authentication');
            } catch (NotReadableError $e) {
                throw new ConfigurationError(
                    $this->translate('Could not read your authentiction.ini, no authentication methods are available.'),
                    0,
                    $e
                );
            }

            $chain = new AuthChain($config);
            $request = $this->getRequest();
            if ($request->isPost() && $this->view->form->isValid($request->getPost())) {
                $user = new User($this->view->form->getValue('username'));
                $password = $this->view->form->getValue('password');
                $backendsTried = 0;
                $backendsWithError = 0;

                $redirectUrl = $form->getValue('redirect');

                if ($redirectUrl) {
                    $redirectUrl = Url::fromPath($redirectUrl);
                } else {
                    $redirectUrl = Url::fromPath('dashboard');
                }

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
                        $this->translate(
                            'No authentication methods available. Did you create'
                          . ' authentication.ini when installing Icinga Web 2?'
                         )
                    );
                }
                if ($backendsTried === $backendsWithError) {
                    throw new ConfigurationError(
                        $this->translate(
                            'All configured authentication methods failed.'
                          . ' Please check the system log or Icinga Web 2 log for more information.'
                        )
                    );
                }
                if ($backendsWithError) {
                    $this->view->form->getElement('username')->addError(
                        $this->translate(
                            'Please note that not all authentication methods were available.'
                          . ' Check the system log or Icinga Web 2 log for more information.'
                        )
                    );
                }
                $this->view->form->getElement('password')->addError($this->translate('Incorrect username or password'));
            } elseif ($request->isGet()) {
                $user = new User('');
                foreach ($chain as $backend) {
                    if ($backend instanceof AutoLoginBackend) {
                        $authenticated  = $backend->authenticate($user);
                        if ($authenticated === true) {
                            $auth->setAuthenticated($user);
                            $this->rerenderLayout()->redirectNow(
                                Url::fromPath(Url::fromRequest()->getParam('redirect', 'dashboard'))
                            );
                        }
                    }
                }
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
        if (! $auth->isAuthenticated()) {
            $this->redirectToLogin();
        }
        $isRemoteUser = $auth->getUser()->isRemoteUser();
        $auth->removeAuthorization();
        if ($isRemoteUser === true) {
            $this->_response->setHttpResponseCode(401);
        } else {
            $this->redirectToLogin();
        }
    }
}
