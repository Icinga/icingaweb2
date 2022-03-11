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
    /** @var array|null url params to be attached to the dropdown menus. */
    private $urlParam;

    /**
     * DashboardSettings constructor.
     *
     * @param array $urlParam
     */
    public function __construct(array $urlParam = [])
    {
        $this->urlParam = $urlParam;
    }

    /**
     * Apply this tabextension to the provided tabs
     *
     * @param Tabs $tabs The tabbar to modify
     */
    public function apply(Tabs $tabs)
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/settings');
        $url =  empty($this->urlParam) ? $url : $url->addParams($this->urlParam);
        $tabs->add(
            'dashboard_settings',
            [
               'icon'       => 'service',
                'url'       => (string) $url,
                'priority'  => -100
            ]
        );
    }
}
