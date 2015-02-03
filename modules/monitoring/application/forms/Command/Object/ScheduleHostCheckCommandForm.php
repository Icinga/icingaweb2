<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

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
        parent::createElements($formData);
        $this->addElements(array(
            array(
                'checkbox',
                'all_services',
                array(
                    'label'         => $this->translate('All Services'),
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
        Notification::success(mtp(
            'monitoring',
            'Scheduling host check..',
            'Scheduling host checks..',
            count($this->objects)
        ));
        return true;
    }
}
