<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Controller;

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
            ));
    }

    /**
     * My account
     */
    public function indexAction()
    {
        $config = Config::app()->getSection('global');
        $user = $this->Auth()->getUser();

        $form = new PreferenceForm();
        $form->setPreferences($user->getPreferences());
        if ($config->get('config_backend', 'ini') !== 'none') {
            $form->setStore(PreferencesStore::create(new ConfigObject(array(
                'store'     => $config->get('config_backend', 'ini'),
                'resource'  => $config->config_resource
            )), $user));
        }
        $form->handleRequest();

        $this->view->form = $form;
        $this->getTabs()->activate('account');
    }
}
