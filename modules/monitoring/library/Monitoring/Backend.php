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

namespace Monitoring;

use \Exception;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Authentication\Manager as AuthManager;
use \Monitoring\Backend\AbstractBackend;

/**
 * Container for monitoring backends
 */
class Backend
{
    /**
     * Array of backends
     *
     * @var array
     */
    protected static $instances = array();

    /**
     * Array of configuration settings for backends
     *
     * @var array
     */
    protected static $backendConfigs;

    /**
     * Locked constructor
     */
    final protected function __construct()
    {
    }

    /**
     * Test if configuration key exist
     *
     * @param   string $name
     *
     * @return  bool
     */
    public static function exists($name)
    {
        $configs = self::getBackendConfigs();
        return array_key_exists($name, $configs);
    }

    /**
     * Get the first configuration name of all backends
     *
     * @return  string
     *
     * @throws  Exception
     */
    public static function getDefaultName()
    {
        $configs = self::getBackendConfigs();
        if (empty($configs)) {
            throw new Exception(
                'Cannot get default backend as no backend has been configured'
            );
        }
        reset($configs);
        return key($configs);
    }

    /**
     * Getter for backend configuration with lazy initializing
     *
     * @return array
     */
    public static function getBackendConfigs()
    {
        if (self::$backendConfigs === null) {
            $resources = IcingaConfig::app('resources');
            foreach ($resources as $resource) {

            }
            $backends = IcingaConfig::module('monitoring', 'backends');
            foreach ($backends as $name => $config) {
                self::$backendConfigs[$name] = $config;
            }
        }

        return self::$backendConfigs;
    }

    /**
     * Get a backend by name or a default one
     *
     * @param   string $name
     *
     * @return  AbstractBackend
     *
     * @throws  Exception
     */
    public static function getBackend($name = null)
    {
        if (! array_key_exists($name, self::$instances)) {
            if ($name === null) {
                $name = self::getDefaultName();
            } else {
                if (!self::exists($name)) {
                    throw new Exception(
                        sprintf(
                            'There is no such backend: "%s"',
                            $name
                        )
                    );
                }
            }

            $config = self::$backendConfigs[$name];
            $type = $config->type;
            $type[0] = strtoupper($type[0]);
            $class = '\\Monitoring\\Backend\\' . $type;
            self::$instances[$name] = new $class($config);
        }
        return self::$instances[$name];
    }

    /**
     * Get backend by name or by user configuration
     *
     * @param   string $name
     *
     * @return  AbstractBackend
     */
    public static function getInstance($name = null)
    {
        if (array_key_exists($name, self::$instances)) {
            return self::$instances[$name];
        } else {
            if ($name === null) {
                // TODO: Remove this, will be chosen by Environment
                $name = AuthManager::getInstance()->getSession()->get('backend');
            }
            return self::getBackend($name);
        }
    }
}
