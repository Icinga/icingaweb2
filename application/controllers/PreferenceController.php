<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BasePreferenceController;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tab;
use Icinga\Application\Config;
use Icinga\Form\PreferenceForm;
use Icinga\Exception\ConfigurationError;
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
            'general' => new Tab(
                array(
                    'title'     => 'General settings',
                    'url'       => Url::fromPath('/preference')
                )
            )
        );
    }

    /**
     * Show form to adjust user preferences
     */
    public function indexAction()
    {
        $storeConfig = Config::app()->preferences;
        if ($storeConfig === null) {
            throw new ConfigurationError(t('You need to configure how to store preferences first.'));
        }

        $user = $this->getRequest()->getUser();
        $form = new PreferenceForm();
        $form->setPreferences($user->getPreferences());
        $form->setStore(PreferencesStore::create($storeConfig, $user));
        $form->handleRequest();

        $this->view->form = $form;
        $this->getTabs()->activate('general');
    }
}
