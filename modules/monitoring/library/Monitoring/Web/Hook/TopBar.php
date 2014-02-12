<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Web\Hook;

use Icinga\Web\Hook\TopBar as IcingaTopBar;
use Icinga\Module\Monitoring\DataView\StatusSummary as StatusSummaryView;
use Icinga\Web\Request;
use Zend_View;

/**
 * Render status summary into the topbar of icinga
 */
class TopBar implements IcingaTopBar
{
    /**
     * Function to generate top bar content
     *
     * @param   Request $request
     * @param   Zend_View $view
     *
     * @return  string
     */
    public function getHtml($request, $view)
    {
        $hostSummary = StatusSummaryView::fromRequest(
            $request,
            array(
                'hosts_up',
                'hosts_unreachable_handled',
                'hosts_unreachable_unhandled',
                'hosts_down_handled',
                'hosts_down_unhandled',
                'hosts_pending'
            )
        )->getQuery()->fetchRow();

        $serviceSummary = StatusSummaryView::fromRequest(
            $request,
            array(
                'services_ok',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_pending'
            )
        )->getQuery()->fetchRow();

        return $view->partial(
            'layout/topbar.phtml',
            'monitoring',
            array(
                'hostSummary'       => $hostSummary,
                'serviceSummary'    => $serviceSummary
            )
        );
    }
}
