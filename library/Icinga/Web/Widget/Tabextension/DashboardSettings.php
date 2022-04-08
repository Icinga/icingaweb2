<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Widget\Tabs;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Dashboard settings
 */
class DashboardSettings implements Tabextension
{
    /** @var array|null url params to be attached to the cog icon being rendered on the dashboard tab */
    private $urlParams;

    /**
     * DashboardSettings constructor.
     *
     * @param array $urlParams
     */
    public function __construct(array $urlParams = [])
    {
        $this->urlParams = $urlParams;
    }

    /**
     * Apply this tabextension to the provided tabs
     *
     * @param Tabs $tabs The tabbar to modify
     */
    public function apply(Tabs $tabs)
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams($this->urlParams);
        $tabs->add('dashboard_settings', [
            'icon' => 'service',
            'url'  => (string) $url,
        ]);
    }
}
