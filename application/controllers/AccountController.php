<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Authentication\User\UserBackend;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Forms\Account\ChangePasswordForm;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Controller;
use ipl\Web\Common\CalloutType;
use ipl\Web\Widget\Callout;

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
                    $userBackend = UserBackend::create($user->getAdditional('backend_name'));
                } catch (ConfigurationError $e) {
                    $userBackend = null;
                }
                if ($userBackend !== null) {
                    $changePasswordForm = new ChangePasswordForm();
                    $changePasswordForm
                        ->setBackend($userBackend)
                        ->handleRequest();
                    $this->view->changePasswordForm = $changePasswordForm;
                }
            }
        }

        if (! $config->get('config_resource')) {
            if ($this->hasPermission('config/general')) {
                $errorMessage = $this->translate(
                    'To establish a valid database connection set the configuration'
                    . ' Database field in the Application Settings.'
                );
            } else {
                $errorMessage = $this->translate(
                    'You do not have permission to change this setting. Please contact an administrator.'
                );
            }

            $this->view->callout = new Callout(
                CalloutType::Error,
                $errorMessage,
                $this->translate('The configuration database has not been configured'),
            );

            return;
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
