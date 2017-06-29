<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateTime;
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

        $this->addElement(
            'checkbox',
            'all_services',
            array(
                'description'   => $this->translate(
                    'Schedule downtime for all services on the hosts and the hosts themselves.'
                ),
                'label'         => $this->translate('All Services')
            )
        );

        if (! $this->getBackend()->isIcinga2()
            || version_compare($this->getBackend()->getProgramVersion(), '2.6.0', '>=')
        ) {
            $this->addElement(
                'select',
                'child_hosts',
                array(
                    'description' => $this->translate(
                        'Define what should be done with the child hosts of the hosts.'
                    ),
                    'label'        => $this->translate('Child Hosts'),
                    'multiOptions' => array(
                        0 => $this->translate('Do nothing with child hosts'),
                        1 => $this->translate('Schedule triggered downtime for all child hosts'),
                        2 => $this->translate('Schedule non-triggered downtime for all child hosts')
                    ),
                    'value'         => 0
                )
            );
        }

        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $end = $this->getValue('end')->getTimestamp();
        if ($end <= $this->getValue('start')->getTimestamp()) {
            $endElement = $this->_elements['end'];
            $endElement->setValue($endElement->getValue()->format($endElement->getFormat()));
            $endElement->addError($this->translate('The end time must be greater than the start time'));
            return false;
        }

        $now = new DateTime;
        if ($end <= $now->getTimestamp()) {
            $endElement = $this->_elements['end'];
            $endElement->setValue($endElement->getValue()->format($endElement->getFormat()));
            $endElement->addError($this->translate('A downtime must not be in the past'));
            return false;
        }

        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            if (($childHostsEl = $this->getElement('child_hosts')) !== null) {
                $childHosts = (int) $childHostsEl->getValue();
            } else {
                $childHosts = 0;
            }
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
