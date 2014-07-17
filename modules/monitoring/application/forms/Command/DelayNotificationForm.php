<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Icinga\Module\Monitoring\Command\DelayNotificationCommand;

/**
 * Form for the delay notification command
 */
class DelayNotificationForm extends CommandForm
{
    /**
     * Maximum delay amount in minutes
     */
    const MAX_DELAY = 1440; // 1 day

    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(t('This command is used to delay the next problem notification that is sent out.'));

        $this->addElement(
            'text',
            'minutes',
            array(
                'label'         => t('Notification Delay (Minutes From Now)'),
                'style'         => 'width: 80px;',
                'value'         => 0,
                'required'      => true,
                'validators'    => array(
                    array(
                        'between',
                        true,
                        array(
                            'min' => 1,
                            'max' => self::MAX_DELAY
                        )
                    )
                ),
                'helptext'      => t(
                    'The notification delay will be disregarded if the host/service changes state before the next '
                    . 'notification is scheduled to be sent out.'
                )
            )
        );

        $this->setSubmitLabel(t('Delay Notification'));

        parent::create();
    }

    /**
     * Create the command object to delay notifications
     *
     * @return DelayNotificationCommand
     */
    public function createCommand()
    {
        return new DelayNotificationCommand($this->getValue('minutes') * 60);
    }
}
