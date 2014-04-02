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

namespace Icinga\Data;

use Icinga\Exception\ProgrammingError;
use Zend_Config;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\Connection as DbConnection;
use Icinga\Protocol\Livestatus\Connection as LivestatusConnection;
use Icinga\Protocol\Statusdat\Reader as StatusdatReader;
use Icinga\Protocol\Ldap\Connection as LdapConnection;
use Icinga\Protocol\File\Reader as FileReader;

class ResourceFactory implements ConfigAwareFactory
{
    /**
     * @var Zend_Config
     */
    private static $resources;

    public static function setConfig($config)
    {
        self::$resources = $config;
    }

    /**
     * Get the configuration for a specific resource
     *
     * @param $resourceName String      The resource's name
     *
     * @return              Zend_Config The configuration of the resource
     * @throws \Icinga\Exception\ConfigurationError
     */
    public static function getResourceConfig($resourceName)
    {
        self::assertResourcesExist();
        if (($resourceConfig = self::$resources->get($resourceName)) === null) {
            throw new ConfigurationError(
                'Cannot load resource config "' . $resourceName . '". Resource does not exist'
            );
        }
        return $resourceConfig;
    }

    /**
     * Return the configuration of all existing resources, or get all resources of a given type.
     *
     * @param  String|null  $type   Fetch only resources that have the given type.
     *
     * @return Zend_Config          The configuration containing all resources
     */
    public static function getResourceConfigs($type = null)
    {
        self::assertResourcesExist();
        if (!isset($type)) {
            return self::$resources;
        } else {
            $resources = array();
            foreach (self::$resources as $name => $resource) {
                if (strtolower($resource->type) === $type) {
                    $resources[$name] = $resource;
                }
            }
            return new Zend_Config($resources);
        }
    }

    /**
     * Check if the existing resources are set. If not, throw an error.
     *
     * @throws \Icinga\Exception\ProgrammingError
     */
    private static function assertResourcesExist()
    {
        if (!isset(self::$resources)) {
            throw new ProgrammingError(
                "The ResourceFactory must be initialised by setting a config, before it can be used"
            );
        }
    }

    /**
     * Create a single resource from the given configuration.
     *
     * NOTE: The factory does not test if the given configuration is valid and the resource is accessible, this
     * depends entirely on the implementation of the returned resource.
     *
     * @param Zend_Config $config                   The configuration for the created resource.
     *
     * @return DbConnection|LdapConnection|LivestatusConnection|StatusdatReader An objects that can be used to access
     *         the given resource. The returned class depends on the configuration property 'type'.
     * @throws \Icinga\Exception\ConfigurationError When an unsupported type is given
     */
    public static function createResource(Zend_Config $config)
    {
        switch (strtolower($config->type)) {
            case 'db':
                $resource = new DbConnection($config);
                break;
            case 'ldap':
                $resource = new LdapConnection($config);
                break;
            case 'statusdat':
                $resource = new StatusdatReader($config);
                break;
            case 'livestatus':
                $resource = new LivestatusConnection($config->socket);
                break;
            case 'file':
                $resource = new FileReader($config);
                break;
            default:
                throw new ConfigurationError('Unsupported resource type "' . $config->type . '"');
        }
        return $resource;
    }

    public static function getBackendType($resource)
    {

    }
}
