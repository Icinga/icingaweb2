<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleActiveHostChecks;

/**
 * Form for enabling/disabling active host checks on an Icinga instance
 */
class ToggleActiveHostChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Active Host Checks'));
        $this->setFeature('active_host_checks_enabled', mt('monitoring', 'Active Host Checks Being Executed'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleActiveHostChecks
     */
    public function getCommand()
    {
        return new ToggleActiveHostChecks();
    }
}
