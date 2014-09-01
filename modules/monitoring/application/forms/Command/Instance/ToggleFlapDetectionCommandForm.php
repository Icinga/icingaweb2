<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleFlapDetection;

/**
 * Form for enabling/disabling host and service flap detection on an Icinga instance
 */
class ToggleFlapDetectionCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Flap Detection'));
        $this->setFeature('flap_detection_enabled', mt('monitoring', 'Flap Detection Enabled'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleFlapDetection
     */
    public function getCommand()
    {
        return new ToggleFlapDetection();
    }
}
