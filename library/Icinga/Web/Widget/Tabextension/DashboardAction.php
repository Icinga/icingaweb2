<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that allows to add the current URL to a dashboard
 *
 * Displayed as a dropdown field in the tabs
 */
class DashboardAction implements Tabextension
{
    /**
     * Applies the dashboard actions to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'dashboard',
            array(
                'icon'      => 'dashboard',
                'label'     => t('Add To Dashboard'),
                'url'       => Url::fromPath(Dashboard::BASE_ROUTE . '/add-to-dashlet'),
                'urlParams' => array(
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl()),
                ),
                'tagParams' => array(
                    'data-icinga-modal' => true,
                    'data-no-icinga-ajax' => true
                )
            )
        );
    }
}
