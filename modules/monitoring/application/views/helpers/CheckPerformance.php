<?php
// @codingStandardsIgnoreStart
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

/**
 * Convert check summary data into a simple usable stdClass
 */
class Zend_View_Helper_CheckPerformance extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return self
     */
    public function checkPerformance()
    {
        return $this;
    }

    /**
     * Create a condensed row of object data
     *
     * @param   array $results      Array of stdClass
     *
     * @return  stdClass            Condensed row
     */
    public function create(array $results)
    {
        $out = new stdClass();
        $out->host_passive_count = 0;
        $out->host_passive_latency_avg = 0;
        $out->host_passive_execution_avg = 0;
        $out->service_passive_count = 0;
        $out->service_passive_latency_avg = 0;
        $out->service_passive_execution_avg = 0;
        $out->service_active_count = 0;
        $out->service_active_latency_avg = 0;
        $out->service_active_execution_avg = 0;
        $out->host_active_count = 0;
        $out->host_active_latency_avg = 0;
        $out->host_active_execution_avg = 0;

        foreach ($results as $row) {
            $key = $row->object_type . '_' . $row->check_type . '_';
            $out->{$key . 'count'} = $row->object_count;
            $out->{$key . 'latency_avg'} = $row->latency / $row->object_count;
            $out->{$key . 'execution_avg'} = $row->execution_time / $row->object_count;
        }
        return $out;
    }
}
// @codingStandardsIgnoreStop