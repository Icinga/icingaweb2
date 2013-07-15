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

namespace Icinga\Authentication;

/**
*   Data holder object for authentication information
*
*   This object should be used instead of passing names and
*   passwords as primitives in order to allow additional information
*   to be provided (like the domain) when needed
**/
class Credentials
{
    protected $username;
    protected $password;
    protected $domain;
    
    /**
    *   Create a new credential object
    *   
    *   @param String   $username
    *   @param String   $password
    *   @param String   $domain
    **/
    public function __construct($username = "", $password = null, $domain = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->domain = $domain;
    }

    /**
    *   @return String
    **/
    public function getUsername()
    {
        return $this->username;
    }

    /**
    *   @param String $username
    **/
    public function setUsername($username)
    {
        return $this->username = $username;
    }
    
    /**
    *   @return String
    **/
    public function getPassword()
    {
        return $this->password;
    }

    /**
    *   @param String  $password
    **/
    public function setPassword($password)
    {
        return $this->password = $password;
    }

    /**
    *   @return String
    **/
    public function getDomain()
    {
        return $this->domain;
    }

    /**
    *   @param String  $domain
    **/
    public function setDomain($domain)
    {
        return $this->domain = $domain;
    }
}
