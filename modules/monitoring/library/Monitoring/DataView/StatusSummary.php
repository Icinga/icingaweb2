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

namespace Icinga\Module\Monitoring\DataView;

class StatusSummary extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            /*'hosts_up',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'services_ok',
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_pending',
            'services_active_checked',
            'services_not_active_checked',
            'services_passive_checked',
            'services_not_passive_checked',
            'hosts_active_checked',
            'hosts_not_active_checked',
            'hosts_passive_checked',
            'hosts_not_passive_checked',
            'services_critical_on_problem_hosts',
            'services_warning_on_problem_hosts',
            'services_unknown_on_problem_hosts'*/

            'hosts_up',
            'hosts_up_disabled',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_down_disabled',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_unreachable_disabled',
            'hosts_pending',
            'hosts_pending_disabled',
            'hosts_active_checked',
            'hosts_not_active_checked',
            'hosts_passive_checked',
            'hosts_not_passive_checked',
            'services_ok',
            'services_ok_disabled',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_warning_on_problem_hosts',
            'services_warning_disabled',
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_critical_on_problem_hosts',
            'services_critical_disabled',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_unknown_on_problem_hosts',
            'services_unknown_disabled',
            'services_pending',
            'services_pending_disabled',
            'services_active_checked',
            'services_not_active_checked',
            'services_passive_checked',
            'services_not_passive_checked'
        );
    }
}
