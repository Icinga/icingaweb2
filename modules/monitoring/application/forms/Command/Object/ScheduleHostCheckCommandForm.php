<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\ScheduleHostCheckCommand;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling host checks
 */
class ScheduleHostCheckCommandForm extends ScheduleServiceCheckCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        parent::createElements($formData);
        $this->addElements(array(
            array(
                'checkbox',
                'all_services',
                array(
                    'label'         => mt('monitoring', 'All Services'),
                    'description'   => mt(
                        'monitoring',
                        'Schedule check for all services on the hosts and the hosts themselves.'
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
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            $check = new ScheduleHostCheckCommand();
            $check
                ->setObject($object)
                ->setOfAllServices($this->getElement('all_services')->isChecked());
            $this->scheduleCheck($check, $request);
        }
        Notification::success(mtp(
            'monitoring',
            'Scheduling host check..',
            'Scheduling host checks..',
            count($this->objects)
        ));
        return true;
    }
}
