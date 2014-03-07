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
            'hosts_up',
            'hosts_up_not_checked',
            'hosts_pending',
            'hosts_pending_not_checked',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_down_passive',
            'hosts_down_not_checked',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_unreachable_passive',
            'hosts_unreachable_not_checked',
            'hosts_active',
            'hosts_passive',
            'hosts_not_checked',
            'hosts_not_processing_event_handlers',
            'hosts_not_triggering_notifications',
            'hosts_without_flap_detection',
            'hosts_flapping',
            'services_ok',
            'services_ok_not_checked',
            'services_pending',
            'services_pending_not_checked',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_warning_passive',
            'services_warning_not_checked',
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_critical_passive',
            'services_critical_not_checked',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_unknown_passive',
            'services_unknown_not_checked',
            'services_active',
            'services_passive',
            'services_not_checked',
            'services_not_processing_event_handlers',
            'services_not_triggering_notifications',
            'services_without_flap_detection',
            'services_flapping',


            'services_ok_on_ok_hosts',
            'services_ok_not_checked_on_ok_hosts',
            'services_pending_on_ok_hosts',
            'services_pending_not_checked_on_ok_hosts',
            'services_warning_handled_on_ok_hosts',
            'services_warning_unhandled_on_ok_hosts',
            'services_warning_passive_on_ok_hosts',
            'services_warning_not_checked_on_ok_hosts',
            'services_critical_handled_on_ok_hosts',
            'services_critical_unhandled_on_ok_hosts',
            'services_critical_passive_on_ok_hosts',
            'services_critical_not_checked_on_ok_hosts',
            'services_unknown_handled_on_ok_hosts',
            'services_unknown_unhandled_on_ok_hosts',
            'services_unknown_passive_on_ok_hosts',
            'services_unknown_not_checked_on_ok_hosts',
            'services_ok_on_problem_hosts',
            'services_ok_not_checked_on_problem_hosts',
            'services_pending_on_problem_hosts',
            'services_pending_not_checked_on_problem_hosts',
            'services_warning_handled_on_problem_hosts',
            'services_warning_unhandled_on_problem_hosts',
            'services_warning_passive_on_problem_hosts',
            'services_warning_not_checked_on_problem_hosts',
            'services_critical_handled_on_problem_hosts',
            'services_critical_unhandled_on_problem_hosts',
            'services_critical_passive_on_problem_hosts',
            'services_critical_not_checked_on_problem_hosts',
            'services_unknown_handled_on_problem_hosts',
            'services_unknown_unhandled_on_problem_hosts',
            'services_unknown_passive_on_problem_hosts',
            'services_unknown_not_checked_on_problem_hosts'
        );
    }
}
