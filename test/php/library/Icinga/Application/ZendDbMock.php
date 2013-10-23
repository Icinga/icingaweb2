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

namespace Tests\Icinga\Application;

/**
 * Partially emulate the functionality of Zend_Db
 */
class ZendDbMock
{

    /**
     * The config that was used in the last call of the factory function
     *
     * @var mixed
     */
    private static $config;

    /**
     * Name of the adapter class that was used in the last call of the factory function
     *
     * @var mixed
     */
    private static $adapter;

    /**
     * Mock the factory-method of Zend_Db and save the given parameters
     *
     * @param   $adapter    String  name of base adapter class, or Zend_Config object
     * @param   $config     mixed   OPTIONAL; an array or Zend_Config object with adapter
     *                                parameters
     *
     * @return  stdClass    Empty object
     */
    public static function factory($adapter, $config)
    {
        self::$config = $config;
        self::$adapter = $adapter;
        return new \stdClass();
    }

    /**
     * Get the name of the adapter class that was used in the last call
     * of the factory function
     *
     * @return String
     */
    public static function getAdapter()
    {
        return self::$adapter;
    }

    /**
     * Get the config that was used in the last call of the factory function
     *
     * @return mixed
     */
    public static function getConfig()
    {
        return self::$config;
    }
}
