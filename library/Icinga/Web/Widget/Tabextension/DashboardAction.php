<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
                'icon'      => 'dashboard',
                'title'     => 'Add To Dashboard',
                'url'       => Url::fromPath('dashboard/new-dashlet'),
                'urlParams' => array(
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl())
                )
            )
        );
    }
}
