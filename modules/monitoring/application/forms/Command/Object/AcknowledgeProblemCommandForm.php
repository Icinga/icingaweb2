<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Application\Config;
use Icinga\Module\Monitoring\Command\Object\AcknowledgeProblemCommand;
use Icinga\Web\Notification;

/**
 * Form for acknowledging host or service problems
 */
class AcknowledgeProblemCommandForm extends ObjectsCommandForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription($this->translate(
            'This command is used to acknowledge host or service problems. When a problem is acknowledged,'
            . ' future notifications about problems are temporarily disabled until the host or service'
            . ' recovers.'
        ));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Acknowledge problem', 'Acknowledge problems', count($this->objects));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $config = Config::module('monitoring');

        $this->addElements(array(
            array(
                'textarea',
                'comment',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Comment'),
                    'description'   => $this->translate(
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
                    'label'         => $this->translate('Persistent Comment'),
                    'value'         => (bool) $config->get('settings', 'acknowledge_persistent', false),
                    'description'   => $this->translate(
                        'If you would like the comment to remain even when the acknowledgement is removed, check this'
                        . ' option.'
                    )
                )
            ),
            array(
                'checkbox',
                'expire',
                array(
                    'label'         => $this->translate('Use Expire Time'),
                    'value'         => (bool) $config->get('settings', 'acknowledge_expire', false),
                    'description'   => $this->translate(
                        'If the acknowledgement should expire, check this option.'
                    ),
                    'autosubmit'    => true
                )
            )
        ));
        if (isset($formData['expire']) && (bool) $formData['expire'] === true) {
            $expireTime = new DateTime();
            $expireTime->add(new DateInterval('PT1H'));
            $this->addElement(
                'dateTimePicker',
                'expire_time',
                array(
                    'label'         => $this->translate('Expire Time'),
                    'value'         => $expireTime,
                    'description'   => $this->translate(
                        'Enter the expire date and time for this acknowledgement here. Icinga will delete the'
                        . ' acknowledgement after this time expired.'
                    )
                )
            );
            $this->addDisplayGroup(
                array('expire', 'expire_time'),
                'expire-expire_time',
                array(
                    'decorators' => array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'div'))
                    )
                )
            );
        }
        $this->addElements(array(
            array(
                'checkbox',
                'sticky',
                array(
                    'label'         => $this->translate('Sticky Acknowledgement'),
                    'value'         => (bool) $config->get('settings', 'acknowledge_sticky', false),
                    'description'   => $this->translate(
                        'If you want the acknowledgement to remain until the host or service recovers even if the host'
                        . ' or service changes state, check this option.'
                    )
                )
            ),
            array(
                'checkbox',
                'notify',
                array(
                    'label'         => $this->translate('Send Notification'),
                    'value'         => (bool) $config->get('settings', 'acknowledge_notify', true),
                    'description'   => $this->translate(
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
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $ack = new AcknowledgeProblemCommand();
            $ack
                ->setObject($object)
                ->setComment($this->getElement('comment')->getValue())
                ->setAuthor($this->request->getUser()->getUsername())
                ->setPersistent($this->getElement('persistent')->isChecked())
                ->setSticky($this->getElement('sticky')->isChecked())
                ->setNotify($this->getElement('notify')->isChecked());
            if ($this->getElement('expire')->isChecked()) {
                $ack->setExpireTime($this->getElement('expire_time')->getValue()->getTimestamp());
            }
            $this->getTransport($this->request)->send($ack);
        }
        Notification::success($this->translatePlural(
            'Acknowledging problem..',
            'Acknowledging problems..',
            count($this->objects)
        ));
        return true;
    }
}
