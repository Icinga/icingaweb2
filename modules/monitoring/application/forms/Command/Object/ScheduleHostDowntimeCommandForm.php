<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\PropagateHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Web\Notification;

/**
 * Form for scheduling host downtimes
 */
class ScheduleHostDowntimeCommandForm extends ScheduleServiceDowntimeCommandForm
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
                    'label'         => $this->translate('All Services'),
                    'description'   => $this->translate(
                        'Schedule downtime for all services on the hosts and the hosts themselves.'
                    )
                )
            ),
            array(
                'select',
                'child_hosts',
                array(
                    'label'        => $this->translate('Child Hosts'),
                    'required'     => true,
                    'multiOptions' => array(
                        0 => $this->translate('Do nothing with child hosts'),
                        1 => $this->translate('Schedule triggered downtime for all child hosts'),
                        2 => $this->translate('Schedule non-triggered downtime for all child hosts')
                    ),
                    'description' => $this->translate(
                        'Define what should be done with the child hosts of the hosts.'
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
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            $childHosts = (int) $this->getElement('child_hosts')->getValue();
            $allServices = $this->getElement('all_services')->isChecked();
            if ($childHosts === 0) {
                $hostDowntime = new ScheduleHostDowntimeCommand();
                if ($allServices === true) {
                    $hostDowntime->setForAllServices();
                };
            } else {
                $hostDowntime = new PropagateHostDowntimeCommand();
                if ($childHosts === 1) {
                    $hostDowntime->setTriggered();
                }
                if ($allServices === true) {
                    foreach ($object->services as $service) {
                        $serviceDowntime = new ScheduleServiceDowntimeCommand();
                        $serviceDowntime->setObject($service);
                        $this->scheduleDowntime($serviceDowntime, $this->request);
                    }
                }
            }
            $hostDowntime->setObject($object);
            $this->scheduleDowntime($hostDowntime, $this->request);
        }
        Notification::success($this->translatePlural(
            'Scheduling host downtime..',
            'Scheduling host downtimes..',
            count($this->objects)
        ));
        return true;
    }
}
