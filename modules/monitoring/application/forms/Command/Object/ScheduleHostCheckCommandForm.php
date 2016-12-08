<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Application\Config;
use Icinga\Module\Monitoring\Command\Object\ScheduleHostCheckCommand;
use Icinga\Web\Notification;

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
        $config = Config::module('monitoring');

        parent::createElements($formData);
        $this->addElements(array(
            array(
                'checkbox',
                'all_services',
                array(
                    'label'         => $this->translate('All Services'),
                    'value'         => (bool) $config->get('settings', 'hostcheck_all_services', false),
                    'description'   => $this->translate(
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
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            $check = new ScheduleHostCheckCommand();
            $check
                ->setObject($object)
                ->setOfAllServices($this->getElement('all_services')->isChecked());
            $this->scheduleCheck($check, $this->request);
        }
        Notification::success($this->translatePlural(
            'Scheduling host check..',
            'Scheduling host checks..',
            count($this->objects)
        ));
        return true;
    }
}
