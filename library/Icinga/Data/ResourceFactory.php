<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\Connection as DbConnection;

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

    public static function createResource($resourceName)
    {
        if (($resourceConfig = self::$resources->get($resourceName)) === null) {
            throw new ConfigurationError('BLUBB?!');
        }
        switch (strtolower($resourceConfig->type)) {
            case 'db':
                $resource = new DbConnection($resourceConfig);
                break;
            default:
                throw new ConfigurationError('BLUBB2?!');

        }
        return $resource;
    }
}
