<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Object\AcknowledgeProblemCommand;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for acknowledging host or service problems
 */
class AcknowledgeProblemCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return mtp(
            'monitoring', 'Acknowledge problem', 'Acknowledge problems', count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            new Note(
                'command-info',
                array(
                    'value' => mt(
                        'monitoring',
                        'This command is used to acknowledge host or service problems. When a problem is acknowledged,'
                        . ' future notifications about problems are temporarily disabled until the host or service'
                        . ' recovers.'
                    )
                )
            ),
            array(
                'textarea',
                'comment',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'Comment'),
                    'description'   => mt(
                        'monitoring',
                        'If you work with other administrators, you may find it useful to share information about the'
                        . ' the host or service that is having problems. Make sure you enter a brief description of'
                        . ' what you are doing.'
                    )
                )
            ),
            array(
                'checkbox',
                'persistent',
                array(
                    'label'         => mt('monitoring', 'Persistent Comment'),
                    'description'   => mt(
                        'monitoring',
                        'If you would like the comment to remain even when the acknowledgement is removed, check this'
                        . ' option.'
                    )
                )
            ),
            array(
                'checkbox',
                'expire',
                array(
                    'label'         => mt('monitoring', 'Use Expire Time'),
                    'description'   => mt('monitoring', 'If the acknowledgement should expire, check this option.'),
                    'autosubmit'    => true
                )
            )
        ));
        if (isset($formData['expire']) && (bool) $formData['expire'] === true) {
            $expireTime = new DateTime();
            $expireTime->add(new DateInterval('PT1H'));
            $this->addElement(
                new DateTimePicker(
                    'expire_time',
                    array(
                        'label'         => mt('monitoring', 'Expire Time'),
                        'value'         => $expireTime,
                        'description'   => mt(
                            'monitoring',
                            'Enter the expire date and time for this acknowledgement here. Icinga will delete the'
                            . ' acknowledgement after this time expired.'
                        )
                    )
                )
            );
            $this->addDisplayGroup(
                array('expire', 'expire_time'),
                'expire-expire_time',
                array(
                    'decorators' => array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group'))
                    )
                )
            );
        }
        $this->addElements(array(
            array(
                'checkbox',
                'sticky',
                array(
                    'label'         => mt('monitoring', 'Sticky Acknowledgement'),
                    'value'         => true,
                    'description'   => mt(
                        'monitoring',
                        'If you want the acknowledgement to disable notifications until the host or service recovers,'
                        . ' check this option.'
                    )
                )
            ),
            array(
                'checkbox',
                'notify',
                array(
                    'label'         => mt('monitoring', 'Send Notification'),
                    'value'         => true,
                    'description'   => mt(
                        'monitoring',
                        'If you do not want an acknowledgement notification to be sent out to the appropriate contacts,'
                        . ' uncheck this option.'
                    )
                )
            )
        ));
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $ack = new AcknowledgeProblemCommand();
            $ack
                ->setObject($object)
                ->setComment($this->getElement('comment')->getValue())
                ->setAuthor($request->getUser()->getUsername())
                ->setPersistent($this->getElement('persistent')->isChecked())
                ->setSticky($this->getElement('sticky')->isChecked())
                ->setNotify($this->getElement('notify')->isChecked());
            if ($this->getElement('expire')->isChecked()) {
                $ack->setExpireTime($this->getElement('expire_time')->getValue()->getTimestamp());
            }
            $this->getTransport($request)->send($ack);
        }
        Notification::success(mtp(
            'monitoring',
            'Acknowledging problem..',
            'Acknowledging problems..',
            count($this->objects)
        ));
        return true;
    }
}
