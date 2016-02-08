<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Enable or disable a feature of an Icinga object, i.e. host or service
 */
class ToggleObjectFeatureCommand extends ObjectCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST,
        self::TYPE_SERVICE
    );

    /**
     * Feature for enabling or disabling active checks of a host or service
     */
    const FEATURE_ACTIVE_CHECKS = 'active_checks_enabled';

    /**
     * Feature for enabling or disabling passive checks of a host or service
     */
    const FEATURE_PASSIVE_CHECKS = 'passive_checks_enabled';

    /**
     * Feature for enabling or disabling processing of host or service checks via the OCHP command for a host or service
     */
    const FEATURE_OBSESSING = 'obsessing';

    /**
     * Feature for enabling or disabling notifications for a host or service
     *
     * Notifications will be sent out only if notifications are enabled on a program-wide basis as well.
     */
    const FEATURE_NOTIFICATIONS = 'notifications_enabled';

    /**
     * Feature for enabling or disabling event handler for a host or service
     */
    const FEATURE_EVENT_HANDLER = 'event_handler_enabled';

    /**
     * Feature for enabling or disabling flap detection for a host or service.
     *
     * In order to enable flap detection flap detection must be enabled on a program-wide basis as well.
     */
    const FEATURE_FLAP_DETECTION = 'flap_detection_enabled';

    /**
     * Feature that is to be enabled or disabled
     *
     * @var string
     */
    protected $feature;

    /**
     * Whether the feature should be enabled or disabled
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Set the feature that is to be enabled or disabled
     *
     * @param   string $feature
     *
     * @return  $this
     */
    public function setFeature($feature)
    {
        $this->feature = (string) $feature;
        return $this;
    }

    /**
     * Get the feature that is to be enabled or disabled
     *
     * @return string
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Set whether the feature should be enabled or disabled
     *
     * @param   bool $enabled
     *
     * @return  $this
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = (bool) $enabled;
        return $this;
    }

    /**
     * Get whether the feature should be enabled or disabled
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
