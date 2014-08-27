<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use \Icinga\Web\Form\Element\DateTimePicker;
use \Icinga\Protocol\Commandpipe\Comment;
use \Icinga\Util\DateTimeFactory;
use \Icinga\Module\Monitoring\Command\AcknowledgeCommand;

/**
 * Form for problem acknowledgements
 */
class AcknowledgeForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to acknowledge host or service problems. When a problem is '
                . 'acknowledged, future notifications about problems are temporarily disabled until the '
                . 'host/service changes from its current state.'
            )
        );

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'     => t('Comment'),
                'rows'      => 4,
                'cols'      => 72,
                'required'  => true,
                'helptext'  => t(
                    ' If you work with other administrators you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'persistent',
            array(
                'label'    => t('Persistent Comment'),
                'value'    => false,
                'helptext' => t(
                    'If you would like the comment to remain even when the acknowledgement is removed, '
                    . 'check this option.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'expire',
            array(
                'label'    => t('Use Expire Time'),
                'helptext' => t('If the acknowledgement should expire, check this option.')
            )
        );
        $this->enableAutoSubmit(array('expire'));

        if ($this->getRequest()->getPost('expire', '0') === '1') {
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
                        ),
                        'jspicker'  => true
                    )
                )
            );
        }

        $this->addElement(
            'checkbox',
            'sticky',
            array(
                'label'    => t('Sticky Acknowledgement'),
                'value'    => true,
                'helptext' => t(
                    'If you want the acknowledgement to disable notifications until the host/service '
                    . 'recovers, check this option.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'notify',
            array(
                'label'    => t('Send Notification'),
                'value'    => true,
                'helptext' => t(
                    'If you do not want an acknowledgement notification to be sent out to the appropriate '
                    . 'contacts, uncheck this option.'
                )
            )
        );

        $this->setSubmitLabel(t('Acknowledge Problem'));

        parent::create();
    }

    /**
     * Add validator for dependent fields
     *
     * @param   array $data
     *
     * @see     \Icinga\Web\Form::preValidation()
     */
    protected function preValidation(array $data)
    {
        if (isset($data['expire']) && intval($data['expire']) === 1) {
            $expireTime = $this->getElement('expiretime');
            $expireTime->setRequired(true);
        }
    }

    /**
     * Create the acknowledgement command object
     *
     * @return AcknowledgeCommand
     */
    public function createCommand()
    {
        return new AcknowledgeCommand(
            new Comment(
                $this->getAuthorName(),
                $this->getValue('comment'),
                $this->getValue('persistent')
            ),
            $this->getValue('expire') ? $this->getValue('expire') : -1,
            $this->getValue('notify'),
            $this->getValue('sticky')
        );
    }
}
