<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

# namespace Icinga\Application\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\AuthChain;
use Icinga\Authentication\Backend\ExternalBackend;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\User;
use Icinga\Web\Controller\ActionController;
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
        $icinga = Icinga::app();
        if ($icinga->setupTokenExists() && $icinga->requiresSetup()) {
            $this->redirectNow(Url::fromPath('setup'));
        }

        $triedOnlyExternalAuth = null;
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
                    $this->translate('Could not read your authentication.ini, no authentication methods are available.'),
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
                    if ($backend instanceof ExternalBackend) {
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
                    $this->view->form->addError(
                        $this->translate(
                            'No authentication methods available. Did you create'
                            . ' authentication.ini when setting up Icinga Web 2?'
                         )
                    );
                } else if ($backendsTried === $backendsWithError) {
                    $this->view->form->addError(
                        $this->translate(
                            'All configured authentication methods failed.'
                            . ' Please check the system log or Icinga Web 2 log for more information.'
                        )
                    );
                } elseif ($backendsWithError) {
                    $this->view->form->addError(
                        $this->translate(
                            'Please note that not all authentication methods were available.'
                            . ' Check the system log or Icinga Web 2 log for more information.'
                        )
                    );
                }
                if ($backendsTried > 0 && $backendsTried !== $backendsWithError) {
                    $this->view->form->getElement('password')->addError($this->translate('Incorrect username or password'));
                }
            } elseif ($request->isGet()) {
                $user = new User('');
                foreach ($chain as $backend) {
                    $triedOnlyExternalAuth = $triedOnlyExternalAuth === null;
                    if ($backend instanceof ExternalBackend) {
                        $authenticated  = $backend->authenticate($user);
                        if ($authenticated === true) {
                            $auth->setAuthenticated($user);
                            $this->rerenderLayout()->redirectNow(
                                Url::fromPath(Url::fromRequest()->getParam('redirect', 'dashboard'))
                            );
                        }
                    } else {
                        $triedOnlyExternalAuth = false;
                    }
                }
            }
        } catch (Exception $e) {
            $this->view->form->addError($e->getMessage());
        }

        $this->view->requiresExternalAuth = $triedOnlyExternalAuth && ! $auth->isAuthenticated();
        $this->view->requiresSetup = Icinga::app()->requiresSetup();
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
