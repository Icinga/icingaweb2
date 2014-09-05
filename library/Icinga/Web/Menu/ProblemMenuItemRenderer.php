<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Menu;

use Icinga\Module\Monitoring\Backend;
use Icinga\Web\Menu;
use Icinga\Web\Url;

class ProblemMenuItemRenderer implements MenuItemRenderer {

    public function render(Menu $menu)
    {
        $statusSummary = Backend::createBackend()
            ->select()->from(
                'statusSummary',
                array(
                    'hosts_down_unhandled',
                    'services_critical_unhandled'
                )
            )->getQuery()->fetchRow();
        $unhandled = $statusSummary->hosts_down_unhandled + $statusSummary->services_critical_unhandled;
        $badge = '';
        if ($unhandled) {
            $badge = sprintf(
                '<div class="badge-container"><span class="badge badge-critical">%s</span></div>',
                $unhandled
            );
        }
        return sprintf(
            '<a href="%s">%s%s %s</a>',
            $menu->getUrl() ?: '#',
            $menu->getIcon() ? '<img src="' . Url::fromPath($menu->getIcon()) . '" class="icon" /> ' : '',
            htmlspecialchars($menu->getTitle()),
            $badge
        );
    }
}
