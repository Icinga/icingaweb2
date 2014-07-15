<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Tabextension;

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
                'icon'      => 'img/icons/dashboard.png',
                'title'     => 'Add To Dashboard',
                'url'       => Url::fromPath('dashboard/addurl'),
                'urlParams' => array(
                    'url' => Url::fromRequest()->getRelativeUrl()
                )
            )
        );
    }
}
