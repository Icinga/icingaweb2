<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Module\Monitoring\Command\CustomNotificationCommand;

/**
 * For for command CustomNotification
 */
class CustomNotificationForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to send a custom notification about hosts or services. Useful in '
                . 'emergencies when you need to notify admins of an issue regarding a monitored system or '
                . 'service.'
            )
        );

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'    => t('Comment'),
                'rows'     => 4,
                'cols'     => 72,
                'required' => true,
                'helptext' => t(
                    'If you work with other administrators, you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'forced',
            array(
                'label'    => t('Forced'),
                'helptext' => t(
                    'Custom notifications normally follow the regular notification logic in Icinga. Selecting this '
                    . 'option will force the notification to be sent out, regardless of time restrictions, '
                    . 'whether or not notifications are enabled, etc.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'broadcast',
            array(
                'label'    => t('Broadcast'),
                'helptext' => t(
                    'Selecting this option causes the notification to be sent out to all normal (non-escalated) '
                    . ' and escalated contacts. These options allow you to override the normal notification logic '
                    . 'if you need to get an important message out.'
                )
            )
        );

        $this->setSubmitLabel(t('Send Custom Notification'));

        parent::create();
    }

    /**
     * Create the command object to send custom notifications
     *
     * @return CustomNotificationCommand
     */
    public function createCommand()
    {
        return new CustomNotificationCommand(
            new Comment(
                $this->getAuthorName(),
                $this->getValue('comment')
                ),
            $this->getValue('forced'),
            $this->getValue('broadcast')
        );
    }
}
