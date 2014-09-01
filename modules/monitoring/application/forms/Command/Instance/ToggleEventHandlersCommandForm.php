<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use Icinga\Module\Monitoring\Command\Instance\ToggleEventHandlers;

/**
 * Form for enabling/disabling host and service event handlers on an Icinga instance
 */
class ToggleEventHandlersCommandForm extends ToggleFeatureCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Toggle Event Handlers'));
        $this->setFeature('event_handlers_enabled', mt('monitoring', 'Event Handlers Enabled'));
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleEventHandlers
     */
    public function getCommand()
    {
        return new ToggleEventHandlers();
    }
}
