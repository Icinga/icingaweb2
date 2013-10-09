<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Zend_Config;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\Connection as DbConnection;
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
            throw new ConfigurationError('BLUBB?!');
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
            default:
                throw new ConfigurationError('BLUBB2?!');

        }
        return $resource;
    }
}
