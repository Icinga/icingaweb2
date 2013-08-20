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

namespace Icinga\Module\Monitoring\Backend\Statusdat\Criteria;

/**
 * Class Order
 *
 * Constants for order definitions.
 * These only describe logical orders without going into storage specific
 * details, like which fields are used for ordering. It's completely up to the query to determine what to do with these
 * constants (although the result should be consistent among the different storage apis).
 *
 * @package Icinga\Backend\Criteria
 */
class Order
{
    /**
     * Order by the newest events. What this means has to be determined in the context.
     * Mostly this affects last_state_change
     *
     * @var string
     */
    const STATE_CHANGE = "state_change";

    /**
     * Order by the state of service objects. Mostly this is critical->unknown->warning->ok,
     * but also might take acknowledgments and downtimes in account
     *
     * @var string
     */
    const SERVICE_STATE = "service_state";

    /**
     * Order by the state of host objects. Mostly this is critical->unknown->warning->ok,
     * but also might take acknowledgments and downtimes in account
     *
     * @var string
     */
    const HOST_STATE = "host_state";

    /**
     * @var string
     */
    const HOST_NAME = "host_name";

    /**
     *
     * @var string
     */
    const SERVICE_NAME = "service_description";
}
