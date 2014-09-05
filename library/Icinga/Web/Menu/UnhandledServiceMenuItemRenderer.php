<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Menu;

use Icinga\Module\Monitoring\Backend;
use Icinga\Web\Menu;
use Icinga\Web\Url;

class UnhandledServiceMenuItemRenderer implements MenuItemRenderer {

    public function render(Menu $menu)
    {
        $statusSummary = Backend::createBackend()
            ->select()->from(
                'statusSummary',
                array(
                    'services_critical_unhandled'
                )
            )->getQuery()->fetchRow();
        $badge = '';
        if ($statusSummary->services_critical_unhandled) {
            $badge = sprintf(
                '<div class="badge-container"><span class="badge badge-critical">%s</span></div>',
                $statusSummary->services_critical_unhandled
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
