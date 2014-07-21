<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BasePreferenceController;
use Icinga\Web\Widget\Tab;
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
        $this->getTabs()->activate('general');

        $form = new GeneralForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $this->savePreferences($form->getPreferences()->toArray());
                    Notification::success($this->translate('Preferences updated successfully'));
                    $this->redirectNow('preference');
                } catch (Exception $e) {
                    Notification::error(
                        sprintf(
                            $this->translate('Failed to persist preferences. (%s)'),
                            $e->getMessage()
                        )
                    );
                }
            }
        } else {
            $form->setPreferences($request->getUser()->getPreferences());
        }

        $this->view->form = $form;
    }
}
