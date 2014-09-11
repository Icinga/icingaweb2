<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\ScheduleHostCheckCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Module\Monitoring\Form\Command\Object\ObjectsCommandForm;
use Icinga\Web\Notification;
use Icinga\Web\Request;

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
        $this->setSubmitLabel(mt('monitoring', 'Check Now'));
        $this->setAttrib('class', 'inline link-like');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
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
            $this->getTransport($request)->send($check);
        }
        Notification::success(mt('monitoring', 'Scheduling check..'));
        return true;
    }
}
