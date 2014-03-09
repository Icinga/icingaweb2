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
 * Convert runtime summary data into a simple usable stdClass
 */
class Zend_View_Helper_RuntimeVariables extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return self
     */
    public function runtimeVariables()
    {
        return $this;
    }

    /**
     * Create a condensed row of object data
     *
     * @param   $result      	    stdClass
     *
     * @return  stdClass            Condensed row
     */
    public function create(stdClass $result)
    {
        $out = new stdClass();
        $out->total_hosts = $result->total_hosts;
        $out->total_scheduled_hosts = $result->total_scheduled_hosts;
        $out->total_services = $result->total_services;
        $out->total_scheduled_services = $result->total_scheduled_services;
        $out->average_services_per_host = $result->total_services / $result->total_hosts;
        $out->average_scheduled_services_per_host = $result->total_scheduled_services / $result->total_scheduled_hosts;

        return $out;
    }
}
// @codingStandardsIgnoreStop
