<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Navigation\Renderer;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Navigation\Renderer\BadgeNavigationItemRenderer;

class BackendAvailabilityNavigationItemRenderer extends BadgeNavigationItemRenderer
{
    /**
     * Cached count
     *
     * @var int
     */
    protected $count;

    /**
     * Get whether or not the monitoring backend is currently running
     *
     * @return  object
     */
    protected function isCurrentlyRunning()
    {
        $programStatus = MonitoringBackend::instance()
            ->select()
            ->from(
                'programstatus',
                array('is_currently_running', 'notifications_enabled')
            )
            ->fetchRow();

        if ($programStatus === false) {
            throw new Exception(sprintf(
                mt('monitoring', '%s is currently not up and running'),
                MonitoringBackend::instance()->getName()
            ));
        }

        return $programStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        if ($this->count === null) {
            try {
                $programStatus = $this->isCurrentlyRunning();
            } catch (Exception $e) {
                Logger::debug($e);
                $this->count = 1;
                $this->state = static::STATE_UNKNOWN;
                $this->title = $e->getMessage();
                return $this->count;
            }
            $count = 0;
            $titles = array();
            if (! (bool) $programStatus->notifications_enabled) {
                $count = 1;
                $this->state = static::STATE_WARNING;
                $titles[] = mt('monitoring', 'Notifications are disabled');
            }
            if (! (bool) $programStatus->is_currently_running) {
                $count = 1;
                $this->state = static::STATE_CRITICAL;
                array_unshift($titles, sprintf(
                    mt('monitoring', 'Monitoring backend %s is not running'),
                    MonitoringBackend::instance()->getName()
                ));
            }
            $this->count = $count;
            $this->title = implode('. ', $titles);
        }

        return $this->count;
    }
}
