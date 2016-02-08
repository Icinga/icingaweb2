<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Enable or disable a feature of an Icinga instance
 */
class ToggleInstanceFeatureCommand extends IcingaCommand
{
    /**
     * Feature for enabling or disabling active host checks on an Icinga instance
     */
    const FEATURE_ACTIVE_HOST_CHECKS = 'active_host_checks_enabled';

    /**
     * Feature for enabling or disabling active service checks on an Icinga instance
     */
    const FEATURE_ACTIVE_SERVICE_CHECKS = 'active_service_checks_enabled';

    /**
     * Feature for enabling or disabling host and service event handlers on an Icinga instance
     */
    const FEATURE_EVENT_HANDLERS = 'event_handlers_enabled';

    /**
     * Feature for enabling or disabling host and service flap detection on an Icinga instance
     */
    const FEATURE_FLAP_DETECTION = 'flap_detection_enabled';

    /**
     * Feature for enabling or disabling host and service notifications on an Icinga instance
     */
    const FEATURE_NOTIFICATIONS = 'notifications_enabled';

    /**
     * Feature for enabling or disabling processing of host checks via the OCHP command on an Icinga instance
     */
    const FEATURE_HOST_OBSESSING = 'obsess_over_hosts';

    /**
     * Feature for enabling or disabling processing of service checks via the OCHP command on an Icinga instance
     */
    const FEATURE_SERVICE_OBSESSING = 'obsess_over_services';

    /**
     * Feature for enabling or disabling passive host checks on an Icinga instance
     */
    const FEATURE_PASSIVE_HOST_CHECKS = 'passive_host_checks_enabled';

    /**
     * Feature for enabling or disabling passive service checks on an Icinga instance
     */
    const FEATURE_PASSIVE_SERVICE_CHECKS = 'passive_service_checks_enabled';

    /**
     * Feature for enabling or disabling the processing of host and service performance data on an Icinga instance
     */
    const FEATURE_PERFORMANCE_DATA = 'process_performance_data';

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
