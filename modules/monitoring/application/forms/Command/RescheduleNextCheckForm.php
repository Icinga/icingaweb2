<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Zend_Form_Element_Checkbox;
use Icinga\Util\DateTimeFactory;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Module\Monitoring\Command\ScheduleCheckCommand;

/**
 * Form for scheduling checks
 */
class RescheduleNextCheckForm extends WithChildrenCommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to schedule the next check of hosts/services. Icinga will re-queue the '
                . 'check at the time you specify.'
            )
        );

        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'checktime',
                    'label'     => t('Check Time'),
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'value'     => DateTimeFactory::create()->getTimestamp(),
                    'required'  => !$this->getRequest()->getPost('forcecheck'),
                    'helptext'  => t('Set the date/time when this check should be executed.')
                )
            )
        );

        $this->addElement(
            new Zend_Form_Element_Checkbox(
                array(
                    'name'     => 'forcecheck',
                    'label'    => t('Force Check'),
                    'value'    => true,
                    'helptext' => t(
                        'If you select this option, Icinga will force a check regardless of both what time the '
                        . 'scheduled check occurs and whether or not checks are enabled.'
                    )
                )
            )
        );

        // TODO: As of the time of writing it's possible to set hosts AND services as affected by this command but
        // with children only makes sense on hosts
        if ($this->getWithChildren() === true) {
            $this->addNote(t('TODO: Help message when with children is enabled'));
        } else {
            $this->addNote(t('TODO: Help message when with children is disabled'));
        }

        $this->setSubmitLabel(t('Reschedule Check'));

        parent::create();
    }

    /**
     * Create the command object to schedule checks
     *
     * @return ScheduleCheckCommand
     */
    public function createCommand()
    {
        $command = new ScheduleCheckCommand(
            $this->getValue('checktime'),
            $this->getValue('forcecheck')
        );
        return $command->excludeHost($this->getWithChildren());
    }
}
