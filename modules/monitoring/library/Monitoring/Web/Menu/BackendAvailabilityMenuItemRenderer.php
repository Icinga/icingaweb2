<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Menu;

use Icinga\Web\Menu as Menu;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Menu\MenuItemRenderer;

class BackendAvailabilityMenuItemRenderer extends MenuItemRenderer
{
    /**
     * Checks whether the monitoring backend is running or not
     *
     * @return mixed
     */
    protected function isCurrentlyRunning()
    {
        return MonitoringBackend::instance()->select()->from(
            'programstatus',
            array(
                'is_currently_running'
            )
        )->getQuery()->fetchRow()->is_currently_running;
    }

    /**
     * @see MenuItemRenderer::render()
     */
    public function render(Menu $menu)
    {
        return $this->getBadge() . $this->createLink($menu);
    }

    protected function getBadge()
    {
        if (! (bool)$this->isCurrentlyRunning()) {
            return sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-critical">%s</span></div>',
                mt('monitoring', 'monitoring backend is not running'),
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
        if (! (bool)$this->isCurrentlyRunning()) {
            return array(
                'problems' => 1,
                'title' => mt('monitoring', 'monitoring backend is not running')
            );
        }
        return null;
    }
}
