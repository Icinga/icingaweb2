<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\TogglePerformanceData;

/**
 * Form for enabling/disabling the processing of host and service performance data on an Icinga instance
 */
class TogglePerformanceDataCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Performance Data'));
        $this->setFeature('process_performance_data', mt('monitoring', 'Performance Data Being Processed'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return TogglePerformanceData
     */
    public function getCommand()
    {
        return new TogglePerformanceData();
    }
}
