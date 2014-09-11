<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Menu;

use Icinga\Module\Monitoring\Backend;
use Icinga\Web\Menu;
use Icinga\Web\Url;

class UnhandledHostMenuItemRenderer implements MenuItemRenderer {

    public function render(Menu $menu)
    {
        $statusSummary = Backend::createBackend()
            ->select()->from(
                'statusSummary',
                array(
                    'hosts_down_unhandled'
                )
            )->getQuery()->fetchRow();
        $badge = '';
        if ($statusSummary->hosts_down_unhandled) {
            $badge = sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-critical">%s</span></div>',
                t(sprintf('%d unhandled host problems', $statusSummary->hosts_down_unhandled)),
                $statusSummary->hosts_down_unhandled
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
