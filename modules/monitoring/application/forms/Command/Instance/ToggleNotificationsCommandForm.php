<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleNotifications;

/**
 * Form for enabling/disabling host and service notifications on an Icinga instance
 */
class ToggleNotificationsCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Notifications'));
        $this->setFeature('notifications_enabled', mt('monitoring', 'Notifications Enabled'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleNotifications
     */
    public function getCommand()
    {
        return new ToggleNotifications();
    }
}
