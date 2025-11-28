<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule a service check
 */
class ScheduleServiceCheckCommand extends ObjectCommand
{
    /**
     * {@inheritdoc}
     */
    protected $allowedObjects = array(
        self::TYPE_SERVICE
    );

    /**
     * Time when the next check of a host or service is to be scheduled
     *
     * If active checks are disabled on a host- or service-specific or program-wide basis or the host or service is
     * already scheduled to be checked at an earlier time, etc. The check may not actually be scheduled at the time
     * specified. This behaviour can be overridden by setting `ScheduledCheck::$forced' to true.
     *
     * @var int Unix timestamp
     */
    protected $checkTime;

    /**
     * Whether the check is forced
     *
     * Forced checks are performed regardless of what time it is (e.g. time period restrictions are ignored) and whether
     * or not active checks are enabled on a host- or service-specific or program-wide basis.
     *
     * @var bool
     */
    protected $forced = false;

    /**
     * Set the time when the next check of a host or service is to be scheduled
     *
     * @param   int $checkTime Unix timestamp
     *
     * @return  $this
     */
    public function setCheckTime($checkTime)
    {
        $this->checkTime = (int) $checkTime;
        return $this;
    }

    /**
     * Get the time when the next check of a host or service is to be scheduled
     *
     * @return int Unix timestamp
     */
    public function getCheckTime()
    {
        return $this->checkTime;
    }

    /**
     * Set whether the check is forced
     *
     * @param   bool $forced
     *
     * @return  $this
     */
    public function setForced($forced = true)
    {
        $this->forced = (bool) $forced;
        return $this;
    }

    /**
     * Get whether the check is forced
     *
     * @return bool
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ScheduleCheck';
    }
}
