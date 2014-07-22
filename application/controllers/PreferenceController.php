<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BasePreferenceController;
use Icinga\Web\Widget\Tab;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Web\Url;
use Icinga\Form\Preference\GeneralForm;
use Icinga\Web\Notification;

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
     * General settings for date and time
     */
    public function indexAction()
    {
        $form = new GeneralForm();
        $this->getTabs()->activate('general');
        $form->setConfiguration(IcingaConfig::app())
            ->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            try {
                $this->savePreferences($form->getPreferences());
                Notification::success(t('Preferences updated successfully'));
                // Recreate form to show new values
                // TODO(el): It must sufficient to call $form->populate(...)
                $form = new GeneralForm();
                $form->setConfiguration(IcingaConfig::app());
                $form->setRequest($this->getRequest());
            } catch (Exception $e) {
                Notification::error(sprintf(t('Failed to persist preferences. (%s)'), $e->getMessage()));
            }
        }
        $this->view->form = $form;
    }
}
