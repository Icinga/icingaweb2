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

namespace Tests\Icinga\Web;

/**
 * Request mock that implements all methods required by the
 * Url class
 *
 */
class RequestMock
{
    /**
     * The path of the request
     *
     * @var string
     */
    public $path = "";

    /**
     * The baseUrl of the request
     *
     * @var string
     */
    public $baseUrl = '/';

    /**
     * An array of query parameters that the request should resemble
     *
     * @var array
     */
    public $query = array();

    /**
     * Returns the path set for the request
     *
     * @return string
     */
    public function getPathInfo()
    {
        return $this->path;
    }

    /**
     * Returns the baseUrl set for the request
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns the query set for the request
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }
}
