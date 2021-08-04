<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Module\Monitoring\Command\Object\ApiScheduleHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\PropagateHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Module\Monitoring\Command\Transport\ApiCommandTransport;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Web\Notification;

/**
 * Form for scheduling host downtimes
 */
class ScheduleHostDowntimeCommandForm extends ScheduleServiceDowntimeCommandForm
{
    /** @var bool */
    protected $hostDowntimeAllServices;

    public function init()
    {
        $this->start = new DateTime();
        $config = Config::module('monitoring');
        $this->commentText = $config->get('settings', 'hostdowntime_comment_text');

        $this->hostDowntimeAllServices = (bool) $config->get('settings', 'hostdowntime_all_services', false);

        $fixedEnd = clone $this->start;
        $fixed = $config->get('settings', 'hostdowntime_end_fixed', 'PT1H');
        $this->fixedEnd = $fixedEnd->add(new DateInterval($fixed));

        $flexibleEnd = clone $this->start;
        $flexible = $config->get('settings', 'hostdowntime_end_flexible', 'PT1H');
        $this->flexibleEnd = $flexibleEnd->add(new DateInterval($flexible));

        $flexibleDuration = $config->get('settings', 'hostdowntime_flexible_duration', 'PT2H');
        $this->flexibleDuration = new DateInterval($flexibleDuration);
    }

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
                'label'         => $this->translate('All Services'),
                'value'         => $this->hostDowntimeAllServices
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

        // Send all_services API parameter if Icinga is equal to or greater than 2.11.0
        $allServicesNative = version_compare($this->getBackend()->getProgramVersion(), '2.11.0', '>=');
        // Use ApiScheduleHostDowntimeCommand only when Icinga is equal to or greater than 2.11.0 and
        // when an API command transport is requested or only API command transports are configured:
        $useApiDowntime = $allServicesNative;
        if ($useApiDowntime) {
            $transport = $this->getTransport($this->getRequest());
            if ($transport instanceof CommandTransport) {
                foreach ($transport::getConfig() as $config) {
                    if (strtolower($config->transport) !== 'api') {
                        $useApiDowntime = false;
                        break;
                    }
                }
            } elseif (! $transport instanceof ApiCommandTransport) {
                $useApiDowntime = false;
            }
        }

        foreach ($this->objects as $object) {
            if ($useApiDowntime) {
                $hostDowntime = (new ApiScheduleHostDowntimeCommand())
                    ->setForAllServices($this->getElement('all_services')->isChecked())
                    ->setChildOptions((int) $this->getElement('child_hosts')->getValue());
                // Code duplicated for readability and scope
                $hostDowntime->setObject($object);
                $this->scheduleDowntime($hostDowntime, $this->request);

                continue;
            }

            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            if (($childHostsEl = $this->getElement('child_hosts')) !== null) {
                $childHosts = (int) $childHostsEl->getValue();
            } else {
                $childHosts = 0;
            }
            $allServices = $this->getElement('all_services')->isChecked();
            if ($childHosts === 0) {
                $hostDowntime = (new ScheduleHostDowntimeCommand())
                    ->setForAllServicesNative($allServicesNative);
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
