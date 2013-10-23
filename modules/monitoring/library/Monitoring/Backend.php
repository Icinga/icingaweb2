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

namespace Icinga\Module\Monitoring;

use Icinga\Module\Monitoring\Exception\UnsupportedBackendException;
use Zend_Config;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\DatasourceInterface;
use Icinga\Data\ResourceFactory;
use Icinga\Util\ConfigAwareFactory;

class Backend implements ConfigAwareFactory, DatasourceInterface
{
    /**
     * Resource config
     *
     * @var Zend_config
     */
    private $config;

    /**
     * The resource the backend utilizes
     *
     * @var mixed
     */
    private $resource;

    private static $backendInstances = array();

    private static $backendConfigs = array();

    /**
     * Create a new backend from the given resource config
     *
     * @param Zend_Config $backendConfig
     * @param Zend_Config $resourceConfig
     */
    public function __construct(Zend_Config $backendConfig, Zend_Config $resourceConfig)
    {
        $this->config   = $backendConfig;
        $this->resource = ResourceFactory::createResource($resourceConfig);
    }

    /**
     * Set backend configs
     *
     * @param Zend_Config $backendConfigs
     */
    public static function setConfig($backendConfigs)
    {
        foreach ($backendConfigs as $name => $config) {
            self::$backendConfigs[$name] = $config;
        }
    }

    /**
     * Backend entry point
     *
     * return self
     */
    public function select()
    {
        return $this;
    }

    /**
     * Create query to retrieve columns and rows from the the given table
     *
     * @param   string  $table
     * @param   array   $columns
     *
     * @return  Query
     */
    public function from($table, array $columns = null)
    {
        $queryClass = '\\Icinga\\Module\\Monitoring\\Backend\\'
            . ucfirst($this->config->type)
            . '\\Query\\'
            . ucfirst($table)
            . 'Query';
        if (!class_exists($queryClass)) {
            throw new UnsupportedBackendException('Query '
                . ucfirst($table)
                . ' Is Not Available For Backend '
                . ucfirst($this->config->type)
            );
        }
        return new $queryClass($this->resource, $columns);
    }

    /**
     * Get the resource which was created in the constructor
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get backend configs
     *
     * @return Zend_Config
     */
    public static function getBackendConfigs()
    {
        if (empty(self::$backendConfigs)) {
            self::setConfig(IcingaConfig::module('monitoring', 'backends'));
        }
        return self::$backendConfigs;
    }

    /**
     * Retrieve the name of the default backend which is the INI's first entry
     *
     * @return  string
     * @throws  ConfigurationError When no backend has been configured
     */
    public static function getDefaultBackendName()
    {
        $configs = self::getBackendConfigs();
        if (empty($configs)) {
            throw new ConfigurationError(
                'Cannot get default backend as no backend has been configured'
            );
        }

        // We won't have disabled backends
        foreach ($configs as $name => $config) {
            if (!$config->get('disabled') == '1') {
                return $name;
            }
        }

        throw new ConfigurationError(
            'All backends are disabled'
        );
    }

    /**
     * Create the backend with the given name
     *
     * @param   $name
     *
     * @return  Backend
     */
    public static function createBackend($name)
    {
        if (array_key_exists($name, self::$backendInstances)) {
            return self::$backendInstances[$name];
        }

        if ($name === null) {
            $name = self::getDefaultBackendName();
        }

        $config = null;
        self::getBackendConfigs();
        if (isset(self::$backendConfigs[$name])) {
            /** @var Zend_Config $config */
            $config = self::$backendConfigs[$name];
            if ($config->get('disabled') == '1') {
                $config = null;
            }
        }

        if ($config === null) {
            throw new ConfigurationError(
                'No configuration for backend:' . $name
            );
        }

        self::$backendInstances[$name] = $backend = new self(
            $config,
            ResourceFactory::getResourceConfig($config->resource)
        );
        switch (strtolower($config->type)) {
            case 'ido':
                if ($backend->getResource()->getDbType() !== 'oracle') {
                    $backend->getResource()->setTablePrefix('icinga_');
                }
                break;

        }
        return $backend;
    }
}
