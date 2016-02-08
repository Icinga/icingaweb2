<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Web\Controller\BasePreferenceController;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tab;

/**
 * Application wide preference controller for user preferences
 *
 * @TODO(el): Rename to PreferencesController: https://dev.icinga.org/issues/10014
 */
class PreferenceController extends BasePreferenceController
{
    /**
     * Create tabs for this preference controller
     *
     * @return  array
     *
     * @see     BasePreferenceController::createProvidedTabs()
     */
    public static function createProvidedTabs()
    {
        return array(
            'preferences' => new Tab(array(
                'title' => t('Adjust the preferences of Icinga Web 2 according to your needs'),
                'label' => t('Preferences'),
                'url'   => 'preference'
            )),
            'navigation' => new Tab(array(
                'title' => t('List and configure your own navigation items'),
                'label' => t('Navigation'),
                'url'   => 'navigation'
            ))
        );
    }

    /**
     * Show form to adjust user preferences
     */
    public function indexAction()
    {
        $config = Config::app()->getSection('global');
        $user = $this->getRequest()->getUser();

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
        $this->getTabs()->activate('preferences');
    }
}
