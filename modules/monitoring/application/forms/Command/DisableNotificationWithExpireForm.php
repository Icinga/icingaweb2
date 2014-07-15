<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Icinga\Module\Monitoring\Command\DisableNotificationWithExpireCommand;
use Icinga\Util\DateTimeFactory;
use Icinga\Web\Form\Element\DateTimePicker;

/**
 * Provide expiration when notifications should be disabled
 */
class DisableNotificationWithExpireForm extends CommandForm
{
    /**
     * Build form content
     */
    protected function create()
    {
        $this->addNote('Disable notifications for a specific time on a program-wide basis');

        $now = DateTimeFactory::create();
        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'expiretime',
                    'label'     => t('Expire Time'),
                    'value'     => $now->getTimestamp() + 3600,
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'helptext'  => t(
                        'Enter the expire date/time for this acknowledgement here. Icinga will '
                        . ' delete the acknowledgement after this date expired.'
                    )
                )
            )
        );

        $this->setSubmitLabel('Disable notifications');

        parent::create();
    }


    /**
     * Create command object for CommandPipe protocol
     *
     * @return AcknowledgeCommand
     */
    public function createCommand()
    {
        $command = new DisableNotificationWithExpireCommand();
        $command->setExpirationTimestamp($this->getValue('expiretime'));
        return $command;
    }
}
