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

use Zend_Config;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\Connection as DbConnection;
use Icinga\Protocol\Livestatus\Connection as LivestatusConnection;
use Icinga\Protocol\Statusdat\Reader as StatusdatReader;

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

    public static function getResourceConfig($resourceName)
    {
        if (($resourceConfig = self::$resources->get($resourceName)) === null) {
            throw new ConfigurationError('Resource "' . $resourceName . '" couldn\'t be retrieved');
        }
        return $resourceConfig;
    }

    public static function createResource(Zend_Config $config)
    {
        switch (strtolower($config->type)) {
            case 'db':
                $resource = new DbConnection($config);
                break;
            case 'statusdat':
                $resource = new StatusdatReader($config);
                break;
            case 'livestatus':
                $resource = new LivestatusConnection($config->socket);
                break;
            default:
                throw new ConfigurationError('Unsupported resource type "' . $config->type . '"');

        }
        return $resource;
    }
}
