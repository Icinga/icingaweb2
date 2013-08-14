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

namespace Icinga\Application;

use Zend_Config;
use Zend_Db;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Tests\Icinga\Application\ZendDbMock;

/**
 * Create resources using short identifiers referring to configuration entries
 */
class DbAdapterFactory implements ConfigAwareFactory {

    /**
     * Resource definitions
     *
     * @var Zend_Config
     */
    private static $resources;

    /**
     * The factory class used to create instances of Zend_Db_Adapter
     *
     * @var String
     */
    private static $factoryClass;

    /**
     * Resource cache to allow multiple use
     *
     * @var array
     */
    private static $resourceCache = array();

    /**
     * Set the configuration that stores the available resources
     *
     * @param   mixed   $config     The configuration containing the resources
     *
     * @param   array   $options    Additional options that affect the factories behaviour:
     *                              * factory : Set the factory class that creates instances
     *                                of Zend_Db_Adapter for the different database types
     *                                (used for testing)
     */
    public static function setConfig($config, array $options = null)
    {
        if (is_array($config)) {
            $config = new Zend_Config($config);
        }
        self::$resources = $config;
        if (isset($options['factory'])) {
            self::$factoryClass = $options['factory'];
        } else {
            self::$factoryClass = 'Zend_Db';
        }
    }

    /**
     * Reset the factory configuration back to the default state
     */
    public static function resetConfig()
    {
        unset(self::$resources);
        unset(self::$factoryClass);
    }

    /**
     * Get a list of all resources available to this factory
     *
     * @return array    An array containing all resources compatible to this factory
     */
    public static function getResources()
    {
        $resources = self::$resources->toArray();
        foreach ($resources as $identifier => $resource) {
            if ($resource['type'] !== 'db') {
                unset($resources[$identifier]);
            }
        }
        return $resources;
    }

    /**
     * Return if a resource with the given identifier exists
     *
     * @param $identifier   The name of the resource
     *
     * @return boolean      If the resource exists and is compatible
     */
    public static function resourceExists($identifier)
    {
        return isset(self::$resources->{$identifier})
               && (self::$resources->{$identifier}->type === 'db');
    }

    /**
     * Get the resource with the given $identifier
     *
     * @param   $identifier     The name of the resource
     */
    public static function getDbAdapter($identifier)
    {
        if (!isset(self::$resources)) {
            $msg = 'Creation of resource ' . $identifier . ' not possible, because there is no configuration present.'
                . ' Make shure this factory class was initialised correctly during the application bootstrap.';
                Logger::error($msg);
            throw new ProgrammingError($msg);
        }
        if (!isset(self::$resources->{$identifier})) {
            $msg = 'Creation of resource "'
                . $identifier
                . '" not possible, because there is no matching resource present in the configuration ';
            Logger::error($msg);
            throw new ConfigurationError($msg);
        }
        if (array_key_exists($identifier,self::$resourceCache)) {
            return self::$resourceCache[$identifier];
        } else {
            $res = self::createDbAdapter(self::$resources->{$identifier});
            self::$resourceCache[$identifier] = $res;
            return $res;
        }
    }

    /**
     * Create the Db_Adapter for the given configuration section
     *
     * @param   mixed       $config         The configuration section containing the
     *                                        db information
     *
     * @return \Zend_Db_Adapter_Abstract    The created Zend_Db_Adapter
     *
     * @throws \ConfigurationError          When the specified db type is invalid
     */
    private static function createDbAdapter($config)
    {
        if ($config->type !== 'db') {
            throw new ConfigurationError(
                'Resource type must be "db" but is "' . $config->type . '"');
        }
        $options = array(
            'dbname'    => $config->dbname,
            'host'      => $config->host,
            'username'  => $config->username,
            'password'  => $config->password,
        );
        switch ($config->db) {
            case 'mysql':
                return self::callFactory('Pdo_Mysql',$options);

            case 'pgsql':
                return self::callFactory('Pdo_Pgsql',$options);

            default:
                throw new ConfigurationError('Unsupported db type ' . $config->db . '.');
        }
    }

    /**
     * Call the currently set factory class
     *
     * @param  $adapter                     The name of the used db adapter
     * @param  $options                     OPTIONAL: an array or Zend_Config object with adapter
     *                                        parameters
     *
     * @return Zend_Db_Adapter_Abstract     The created adapter
     */
    private static function callFactory($adapter, $options)
    {
        $factory = self::$factoryClass;
        return $factory::factory($adapter,$options);
    }
}