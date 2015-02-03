<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
        $this->setAttrib('class', 'inline link-like');
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
                    'ignore'        => true,
                    'type'          => 'submit',
                    'value'         => $this->translate('Check now'),
                    'label'         => '<i class="icon-reschedule"></i> ' . $this->translate('Check now'),
                    'decorators'    => array('ViewHelper'),
                    'escape'        => false,
                    'class'         => 'link-like'
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
