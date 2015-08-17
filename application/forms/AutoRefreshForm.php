<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Application\Logger;
use Icinga\User\Preferences;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Url;

/**
 * Form class to adjust user auto refresh preferences
 */
class AutoRefreshForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_auto_refresh');
        // Post against the current location
        $this->setAction('');
    }

    /**
     * Adjust preferences and persist them
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        /** @var Preferences $preferences */
        $preferences = $this->getRequest()->getUser()->getPreferences();
        $icingaweb = $preferences->get('icingaweb');

        if ((bool) $preferences->getValue('icingaweb', 'auto_refresh', true) === false) {
            $icingaweb['auto_refresh'] = '1';
            $notification = $this->translate('Auto refresh successfully enabled');
        } else {
            $icingaweb['auto_refresh'] = '0';
            $notification = $this->translate('Auto refresh successfully disabled');
        }
        $preferences->icingaweb = $icingaweb;

        Session::getSession()->user->setPreferences($preferences);
        Notification::success($notification);

        $this->getResponse()->setHeader('X-Icinga-Rerender-Layout', 'yes');
        $this->setRedirectUrl(Url::fromRequest()->without('renderLayout'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $preferences = $this->getRequest()->getUser()->getPreferences();

        if ((bool) $preferences->getValue('icingaweb', 'auto_refresh', true) === false) {
            $value = $this->translate('Enable auto refresh');
        } else {
            $value = $this->translate('Disable auto refresh');
        }

        $this->addElements(array(
            array(
                'button',
                'btn_submit',
                array(
                    'ignore'        => true,
                    'type'          => 'submit',
                    'value'         => $value,
                    'decorators'    => array('ViewHelper'),
                    'escape'        => false,
                    'class'         => 'link-like'
                )
            )
        ));
    }
}
