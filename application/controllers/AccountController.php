<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Authentication\User\UserBackend;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Forms\Account\ChangePasswordForm;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Controller;
use Icinga\Web\Session;
use ipl\Html\Contract\Form;

/**
 * My Account
 */
class AccountController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->getTabs()
            ->add('account', [
                'title' => $this->translate('Update your account'),
                'label' => $this->translate('My Account'),
                'url'   => 'account',
            ])
            ->add('navigation', [
                'title' => $this->translate('List and configure your own navigation items'),
                'label' => $this->translate('Navigation'),
                'url'   => 'navigation',
            ])
            ->add('devices', [
                'title' => $this->translate('List of devices you are logged in'),
                'label' => $this->translate('My Devices'),
                'url'   => 'my-devices',
            ])
            ->add('two-factor', [
                'title' => $this->translate('Configure two-factor authentication'),
                'label' => $this->translate('Two-Factor Auth'),
                'url'   => 'two-factor/config',
            ]);
    }

    /**
     * My account
     */
    public function indexAction()
    {
        $config = Config::app()->getSection('global');
        $user = $this->Auth()->getUser();
        if ($user->getAdditional('backend_type') === 'db') {
            if ($user->can('user/password-change')) {
                try {
                    /** @var DbUserBackend $userBackend */
                    $userBackend = UserBackend::create($user->getAdditional('backend_name'));
                } catch (ConfigurationError $e) {
                    $userBackend = null;
                }
                if ($userBackend !== null) {
                    $changePasswordForm = (new ChangePasswordForm($userBackend))
                        ->setCsrfCounterMeasureId(Session::getSession()->getId())
                        ->on(Form::ON_SUBMIT, function (ChangePasswordForm $_): void {
                            $this->redirectNow('__REFRESH__');
                        })
                        ->handleRequest(ServerRequest::fromGlobals());
                    $this->view->changePasswordForm = $changePasswordForm;
                }
            }
        }

        $form = new PreferenceForm();
        $form->setPreferences($user->getPreferences());
        if (isset($config->config_resource)) {
            $form->setStore(PreferencesStore::create(new ConfigObject([
                'resource' => $config->config_resource
            ]), $user));
        }
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('My Account');
        $this->getTabs()->activate('account');
    }
}
