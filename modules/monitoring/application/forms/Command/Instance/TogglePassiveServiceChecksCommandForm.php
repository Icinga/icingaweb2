<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\TogglePassiveServiceChecks;

/**
 * Form for enabling/disabling passive service checks on an Icinga instance
 */
class TogglePassiveServiceChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Passive Service Checks'));
        $this->setFeature('passive_service_checks_enabled', mt('monitoring', 'Passive Service Checks Being Accepted'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return TogglePassiveServiceChecks
     */
    public function getCommand()
    {
        return new TogglePassiveServiceChecks();
    }
}
