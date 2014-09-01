<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\TogglePassiveHostChecks;

/**
 * Form for enabling/disabling passive host checks on an Icinga instance
 */
class TogglePassiveHostChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Passive Host Checks'));
        $this->setFeature('passive_host_checks_enabled', mt('monitoring', 'Passive Host Checks Being Accepted'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return TogglePassiveHostChecks
     */
    public function getCommand()
    {
        return new TogglePassiveHostChecks();
    }
}
