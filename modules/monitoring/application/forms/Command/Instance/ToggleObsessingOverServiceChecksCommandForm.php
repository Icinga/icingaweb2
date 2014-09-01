<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleObsessingOverServiceChecks;

/**
 * Form for enabling/disabling processing of service checks via the OCHP command on an Icinga instance
 */
class ToggleObsessingOverServiceChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Obsessing Over Service Checks'));
        $this->setFeature('obsess_over_services', mt('monitoring', 'Obsessing Over Services'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleObsessingOverServiceChecks
     */
    public function getCommand()
    {
        return new ToggleObsessingOverServiceChecks();
    }
}
