<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Web\Controller\BasePreferenceController;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tab;
use Icinga\Application\Config;
use Icinga\Forms\PreferenceForm;
use Icinga\User\Preferences\PreferencesStore;

/**
 * Application wide preference controller for user preferences
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
            'preferences' => new Tab(
                array(
                    'title' => t('Adjust the preferences of Icinga Web 2 according to your needs'),
                    'label' => t('Preferences'),
                    'url'   => Url::fromPath('/preference')
                )
            )
        );
    }

    /**
     * Show form to adjust user preferences
     */
    public function indexAction()
    {
        $storeConfig = Config::app()->getSection('preferences');

        $user = $this->getRequest()->getUser();
        $form = new PreferenceForm();
        $form->setPreferences($user->getPreferences());
        if ($storeConfig->get('store', 'ini') !== 'none') {
            $form->setStore(PreferencesStore::create($storeConfig, $user));
        }
        $form->handleRequest();

        $this->view->form = $form;
        $this->getTabs()->activate('preferences');
    }
}
