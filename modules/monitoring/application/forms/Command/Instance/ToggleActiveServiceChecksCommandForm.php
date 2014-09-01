<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleActiveServiceChecks;

/**
 * Form for enabling/disabling active service checks on an Icinga instance
 */
class ToggleActiveServiceChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Active Service Checks'));
        $this->setFeature('active_service_checks_enabled', mt('monitoring', 'Active Service Checks Being Executed'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleActiveServiceChecks
     */
    public function getCommand()
    {
        return new ToggleActiveServiceChecks();
    }
}
