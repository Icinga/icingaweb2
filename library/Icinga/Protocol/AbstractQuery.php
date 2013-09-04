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

namespace Icinga\Protocol;

/**
 * Class AbstractQuery
 * @package Icinga\Protocol
 */
abstract class AbstractQuery
{
    /**
     *
     */
    const SORT_ASC = 1;

    /**
     *
     */
    const SORT_DESC = -1;

    /**
     * @param $key
     * @param null $val
     * @return mixed
     */
    abstract public function where($key, $val = null);

    /**
     * @param $col
     * @return mixed
     */
    abstract public function order($col);

    /**
     * @param null $count
     * @param null $offset
     * @return mixed
     */
    abstract public function limit($count = null, $offset = null);

    /**
     * @param $table
     * @param null $columns
     * @return mixed
     */
    abstract public function from($table, $columns = null);

    /**
     * @return bool
     */
    public function hasOrder()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasColumns()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return array();
    }

    /**
     * @return bool
     */
    public function hasLimit()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasOffset()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getLimit()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getOffset()
    {
        return null;
    }
}
