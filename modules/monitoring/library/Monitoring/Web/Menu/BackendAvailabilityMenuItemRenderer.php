<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Menu;

use Icinga\Web\Menu;
use Icinga\Web\Menu\MenuItemRenderer;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

class BackendAvailabilityMenuItemRenderer extends MenuItemRenderer
{
    /**
     * Get whether or not the monitoring backend is currently running
     *
     * @return bool
     */
    protected function isCurrentlyRunning()
    {
        $programStatus = MonitoringBackend::instance()
            ->select()
            ->from(
                'programstatus',
                array('is_currently_running')
            )
            ->fetchRow();
        return $programStatus !== false ? (bool) $programStatus : false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(Menu $menu)
    {
        return $this->getBadge() . $this->createLink($menu);
    }

    /**
     * Get the problem badge HTML
     *
     * @return string
     */
    protected function getBadge()
    {
        if (! $this->isCurrentlyRunning()) {
            return sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-critical">%d</span></div>',
                sprintf(
                    mt('monitoring', 'Monitoring backend %s is not running'), MonitoringBackend::instance()->getName()
                ),
                1
            );
        }
        return '';
    }

    /**
     * Get the problem data for the summary
     *
     * @return array|null
     */
    public function getSummary()
    {
        if (! $this->isCurrentlyRunning()) {
            return array(
                'problems'  => 1,
                'title'     => sprintf(
                    mt('monitoring', 'Monitoring backend %s is not running'), MonitoringBackend::instance()->getName()
                )
            );
        }
        return null;
    }
}
