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
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

interface UserBackend
{
    /**
     * Create a userbackend from the given configuration or resource
     *
     * @param $config
     */
    public function __construct($config);

    /**
     * Test if the username exists
     *
     * @param Credentials $credentials
     * @return boolean
     */
    public function hasUsername(Credentials $credentials);

    /**
     * Authenticate
     *
     * @param Credentials $credentials
     * @return User
     */
    public function authenticate(Credentials $credentials);

    /**
     * Get the number of users available through this backend
     *
     * @return int
     */
    public function getUserCount();
}
