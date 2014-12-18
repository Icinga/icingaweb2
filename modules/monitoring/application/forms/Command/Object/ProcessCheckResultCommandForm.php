<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Web\Notification;
use Icinga\Module\Monitoring\Command\Object\ProcessCheckResultCommand;

/**
 * Form for submitting a passive host or service check result
 */
class ProcessCheckResultCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return mtp(
            'monitoring', 'Submit Passive Check Result', 'Submit Passive Check Results', count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Forms\Command\CommandForm::getHelp() For the method documentation.
     */
    public function getHelp()
    {
        return mt(
            'monitoring',
            'This command is used to submit passive host or service check results.'
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
                'label'         => mt('monitoring', 'Status'),
                'description'   => mt('monitoring', 'The state this check result should report'),
                'multiOptions'  => $object->getType() === $object::TYPE_HOST ? array(
                    ProcessCheckResultCommand::HOST_UP          => mt('monitoring', 'UP', 'icinga.state'),
                    ProcessCheckResultCommand::HOST_DOWN        => mt('monitoring', 'DOWN', 'icinga.state'),
                    ProcessCheckResultCommand::HOST_UNREACHABLE => mt('monitoring', 'UNREACHABLE', 'icinga.state')
                ) : array(
                    ProcessCheckResultCommand::SERVICE_OK       => mt('monitoring', 'OK', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_WARNING  => mt('monitoring', 'WARNING', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_CRITICAL => mt('monitoring', 'CRITICAL', 'icinga.state'),
                    ProcessCheckResultCommand::SERVICE_UNKNOWN  => mt('monitoring', 'UNKNOWN', 'icinga.state')
                )
            )
        );
        $this->addElement(
            'text',
            'output',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Output'),
                'description'   => mt('monitoring', 'The plugin output of this check result')
            )
        );
        $this->addElement(
            'text',
            'perfdata',
            array(
                'allowEmpty'    => true,
                'label'         => mt('monitoring', 'Performance Data'),
                'description'   => mt(
                    'monitoring',
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

        Notification::success(mtp(
            'monitoring',
            'Processing check result..',
            'Processing check results..',
            count($this->objects)
        ));

        return true;
    }
}
