<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Tabextension;

use \Icinga\Web\Url;
use \Icinga\Config\Config as IcingaConfig;
use \Icinga\Web\Widget\Tabs;
use \Icinga\Web\Widget\Dashboard;

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
     *
     * @see     \Icinga\Web\Widget\Tabextension::apply()
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'dashboard',
            array(
                'title'     => '{{DASHBOARD_ICON}} Add To Dashboard',
                'url'       => Url::fromPath('dashboard/addurl'),
                'urlParams' => array(
                    'url' => Url::fromRequest()->getRelativeUrl()
                )
            )
        );
    }
}
