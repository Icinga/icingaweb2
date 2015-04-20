<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Menu;

use Icinga\Web\Menu as Menu;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Menu\MenuItemRenderer;

class MonitoringMenuItemRenderer extends MenuItemRenderer
{
    protected static $summary;

    protected $columns = array();

    protected static function summary($column = null)
    {
        if (self::$summary === null) {
            self::$summary = MonitoringBackend::instance()->select()->from(
                'statusSummary',
                array(
                    'hosts_down_unhandled',
                    'services_critical_unhandled'
                )
            )->getQuery()->fetchRow();
        }

        if ($column === null) {
            return self::$summary;
        } elseif (isset(self::$summary->$column)) {
            return self::$summary->$column;
        } else {
            return null;
        }
    }

    protected function getBadgeTitle()
    {
        $translations = array(
            'hosts_down_unhandled'        => mt('monitoring', '%d unhandled hosts down'),
            'services_critical_unhandled' => mt('monitoring', '%d unhandled services critical')
        );

        $titles = array();
        $sum = $this->summary();

        foreach ($this->columns as $col) {
            if (isset($sum->$col) && $sum->$col > 0) {
                $titles[] = sprintf($translations[$col], $sum->$col);
            }
        }

        return implode(', ', $titles);
    }

    protected function countItems()
    {
        $sum = self::summary();
        $count = 0;

        foreach ($this->columns as $col) {
            if (isset($sum->$col)) {
                $count += $sum->$col;
            }
        }

        return $count;
    }

    public function render(Menu $menu)
    {
        return $this->getBadge() . $this->createLink($menu);
    }

    protected function getBadge()
    {
        if ($count = $this->countItems()) {
            return sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-critical">%s</span></div>',
                $this->getBadgeTitle(),
                $count
            );
        }
        return '';
    }
}
