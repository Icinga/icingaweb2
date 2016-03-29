<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Web\Notification;
use Icinga\Module\Monitoring\Command\Object\ProcessCheckResultCommand;

/**
 * Form for submitting a passive host or service check result
 */
class ProcessCheckResultCommandForm extends ObjectsCommandForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription($this->translate(
            'This command is used to submit passive host or service check results.'
        ));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural(
            'Submit Passive Check Result', 'Submit Passive Check Results', count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData)
    {
        foreach ($this->getObjects() as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            // Nasty, but as getObjects() returns everything but an object with a real
            // iterator interface this is the only way to fetch just the first element
            break;
        }

        $this->addElement(
            'select',
            'status',
            array(
                'required'      => true,
                'label'         => $this->translate('Status'),
                'description'   => $this->translate('The state this check result should report'),
                'multiOptions'  => $object->getType() === $object::TYPE_HOST ? $this->getHostMultiOptions() : array(
                    ProcessCheckResultCommand::SERVICE_OK       => $this->translate('OK', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_WARNING  => $this->translate('WARNING', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_CRITICAL => $this->translate('CRITICAL', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_UNKNOWN  => $this->translate('UNKNOWN', 'icinga.state')
                )
            )
        );
        $this->addElement(
            'text',
            'output',
            array(
                'required'      => true,
                'label'         => $this->translate('Output'),
                'description'   => $this->translate('The plugin output of this check result')
            )
        );
        $this->addElement(
            'text',
            'perfdata',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Performance Data'),
                'description'   => $this->translate(
                    'The performance data of this check result. Leave empty'
                    . ' if this check result has no performance data'
                )
            )
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $command = new ProcessCheckResultCommand();
            $command->setObject($object);
            $command->setStatus($this->getValue('status'));
            $command->setOutput($this->getValue('output'));

            if ($perfdata = $this->getValue('perfdata')) {
                $command->setPerformanceData($perfdata);
            }

            $this->getTransport($this->request)->send($command);
        }

        Notification::success($this->translatePlural(
            'Processing check result..',
            'Processing check results..',
            count($this->objects)
        ));

        return true;
    }

    /**
     * Returns the available host options based on the program version
     *
     * @return array
     */
    protected function getHostMultiOptions()
    {
        $options =  array(
            ProcessCheckResultCommand::HOST_UP => $this->translate('UP', 'icinga.state'),
            ProcessCheckResultCommand::HOST_DOWN => $this->translate('DOWN', 'icinga.state')
        );

        if (! $this->getBackend()->isIcinga2()) {
            $options[ProcessCheckResultCommand::HOST_UNREACHABLE] = $this->translate('UNREACHABLE', 'icinga.state');
        }

        return $options;
    }
}
