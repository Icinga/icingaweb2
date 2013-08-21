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

use \PDO;
use \Zend_Config;
use \Zend_Db;
use \Zend_Db_Adapter_Abstract;
use \Icinga\Application\Logger;
use \Icinga\Util\ConfigAwareFactory;
use \Icinga\Exception\ConfigurationError;
use \Icinga\Exception\ProgrammingError;

/**
 * Create resources using short identifiers referring to configuration entries
 */
class DbAdapterFactory implements ConfigAwareFactory
{
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
     * Array of PDO driver options
     *
     * @see http://www.php.net/manual/en/pdo.constants.php
     * @var array
     */
    private static $defaultPdoDriverOptions = array(
        PDO::ATTR_TIMEOUT => 2,
        PDO::ATTR_CASE    => PDO::CASE_LOWER
    );

    /**
     * Array of Zend_Db adapter options
     *
     * @see http://framework.zend.com/manual/1.12/en/zend.db.html
     * @var array
     */
    private static $defaultZendDbAdapterOptions = array(
        Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
        Zend_Db::CASE_FOLDING           => Zend_Db::CASE_LOWER,
        Zend_Db::FETCH_MODE             => Zend_Db::FETCH_OBJ
    );

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
        self::$resources = null;
        self::$factoryClass = null;
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
     * @throws ConfigurationError
     * @throws ProgrammingError
     * @param  string $identifier        The name of the resource
     *
     * @return Zend_Db_Adapter_Abstract
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
        if (array_key_exists($identifier, self::$resourceCache)) {
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
     * @return Zend_Db_Adapter_Abstract    The created Zend_Db_Adapter
     *
     * @throws ConfigurationError          When the specified db type is invalid
     */
    private static function createDbAdapter($config)
    {
        if ($config->type !== 'db') {
            $msg = 'Resource type must be "db" but is "' . $config->type . '"';
            Logger::error($msg);
            throw new ConfigurationError($msg);
        }
        $options = array(
            'dbname'         => $config->dbname,
            'host'           => $config->host,
            'username'       => $config->username,
            'password'       => $config->password,
            'options'        => self::$defaultZendDbAdapterOptions,
            'driver_options' => self::$defaultPdoDriverOptions
        );
        switch ($config->db) {
            case 'mysql':
                return self::callFactory('Pdo_Mysql', $options);
            case 'pgsql':
                return self::callFactory('Pdo_Pgsql', $options);
            default:
                if (!$config->db) {
                    $msg = 'Database type is missing (e.g. db=mysql).';
                } else {
                    $msg = 'Unsupported db type ' . $config->db . '.';
                }
                Logger::error($msg);
                throw new ConfigurationError($msg);
        }
    }

    /**
     * Call the currently set factory class
     *
     * @param  string $adapter              The name of the used db adapter
     * @param  array $options               An array or Zend_Config object with adapter
     *                                      parameters
     *
     * @return Zend_Db_Adapter_Abstract     The created adapter
     */
    private static function callFactory($adapter, array $options)
    {
        $factory = self::$factoryClass;

        $optionModifierCallback = __CLASS__.  '::get'. ucfirst(str_replace('_', '', $adapter)). 'Options';

        if (is_callable($optionModifierCallback)) {
            $options = call_user_func($optionModifierCallback, $options);
        }

        return $factory::factory($adapter, $options);
    }

    /**
     * Get modified attributes for driver PDO_Mysql
     *
     * @param array $options
     *
     * @return array
     */
    private static function getPdoMysqlOptions(array $options)
    {
        /*
         * Set MySQL server SQL modes to behave as closely as possible to Oracle and PostgreSQL. Note that the
         * ONLY_FULL_GROUP_BY mode is left on purpose because MySQL requires you to specify all non-aggregate columns
         * in the group by list even if the query is grouped by the master table's primary key which is valid
         * ANSI SQL though. Further in that case the query plan would suffer if you add more columns to the group by
         * list.
         */
        $options['driver_options'][PDO::MYSQL_ATTR_INIT_COMMAND] =
            'SET SESSION SQL_MODE=\'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,'
            . 'NO_AUTO_CREATE_USER,ANSI_QUOTES,PIPES_AS_CONCAT,NO_ENGINE_SUBSTITUTION\';';

        if (!isset($options['port'])) {
            $options['port'] = 3306;
        }

        return $options;
    }

    /**
     * Get modified attributes for driver PDO_PGSQL
     *
     * @param array $options
     *
     * @return array
     */
    private static function getPdoPgsqlOptions(array $options)
    {
        if (!isset($options['port'])) {
            $options['port'] = 5432;
        }

        return $options;
    }
}
