<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\ScheduleHostCheckCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Web\Notification;

/**
 * Form for immediately checking hosts or services
 */
class CheckNowCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * SubmitButton Label
     *
     * @var SubmitButtonLabel
     */
    protected $submitButtonLabel = 'Check now';

    /**
     * Set the submit button label
     *
     * @param   String $submitButtonLabel
     *
     * @return  $this
     */
    public function setSubmitButtonLabel(String $submitButtonLabel)
    {
        $this->submitButtonLabel = $submitButtonLabel;
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::addSubmitButton() For the method documentation.
     */
    public function addSubmitButton()
    {
        $this->addElements(array(
            array(
                'button',
                'btn_submit',
                array(
                    'class'         => 'link-button spinner',
                    'decorators'    => array(
                        'ViewHelper',
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                    ),
                    'escape'        => false,
                    'ignore'        => true,
                    'label'         => $this->getView()->icon('arrows-cw') . $this->translate($this->submitButtonLabel),
                    'type'          => 'submit',
                    'title'         => $this->translate('Schedule the next active check to run immediately'),
                    'value'         => $this->translate($this->submitButtonLabel)
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
            if (! $object->active_checks_enabled
                && ! $this->Auth()->hasPermission('monitoring/command/schedule-check')
            ) {
                continue;
            }

            if ($object->getType() === $object::TYPE_HOST) {
                $check = new ScheduleHostCheckCommand();
            } else {
                $check = new ScheduleServiceCheckCommand();
            }
            $check
                ->setObject($object)
                ->setForced()
                ->setCheckTime(time());
            $this->getTransport($this->request)->send($check);
        }
        Notification::success(mtp(
            'monitoring',
            'Scheduling check..',
            'Scheduling checks..',
            count($this->objects)
        ));
        return true;
    }
}
