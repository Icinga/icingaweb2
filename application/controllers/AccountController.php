<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Authentication\User\UserBackend;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Forms\Account\ChangePasswordForm;
use Icinga\Forms\Account\TotpForm;
use Icinga\Forms\PreferenceForm;
use Icinga\Authentication\Totp;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Controller;
use Icinga\Web\Session;

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
            ->add('account', array(
                'title' => $this->translate('Update your account'),
                'label' => $this->translate('My Account'),
                'url'   => 'account'
            ))
            ->add('navigation', array(
                'title' => $this->translate('List and configure your own navigation items'),
                'label' => $this->translate('Navigation'),
                'url'   => 'navigation'
            ))
            ->add(
                'devices',
                array(
                    'title' => $this->translate('List of devices you are logged in'),
                    'label' => $this->translate('My Devices'),
                    'url'   => 'my-devices'
                )
            );
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

        // create a form to add and enable 2FA via TOTP

        if ( $user->can('user/two-factor-authentication') ) {

            $totp = Session::getSession()->get('icingaweb_totp', null) ?? new Totp($user->getUsername());
            $totpForm = (new TotpForm())
                ->setPreferences($user->getPreferences())
                ->setTotp($totp);
            if (isset($config->config_resource)) {
                $totpForm->setStore(PreferencesStore::create(new ConfigObject(array(
                    'resource'  => $config->config_resource
                )), $user));
            }

//            $db = Icinga::app()->;
//            Totp::on()
            $totpForm->handleRequest();

            $this->view->totpForm = $totpForm;
        }

        $form = new PreferenceForm();
        $form->setPreferences($user->getPreferences());
        if (isset($config->config_resource)) {
            $form->setStore(PreferencesStore::create(new ConfigObject(array(
                'resource'  => $config->config_resource
            )), $user));
        }
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('My Account');
        $this->getTabs()->activate('account');
    }
}
