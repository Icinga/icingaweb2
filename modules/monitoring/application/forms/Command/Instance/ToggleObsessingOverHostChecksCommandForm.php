<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleObsessingOverHostChecks;

/**
 * Form for enabling/disabling processing of host checks via the OCHP command on an Icinga instance
 */
class ToggleObsessingOverHostChecksCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Obsessing Over Host Checks'));
        $this->setFeature('obsess_over_hosts', mt('monitoring', 'Obsessing Over Hosts'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleObsessingOverHostChecks
     */
    public function getCommand()
    {
        return new ToggleObsessingOverHostChecks();
    }
}
