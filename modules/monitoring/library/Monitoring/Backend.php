<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Zend_config;
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
     * @param Zend_config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->config   = $config;
        $this->resource = ResourceFactory::createResource($config->resource);
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
    public function from($table, array $columns)
    {
        $queryClass = '\\Icinga\\Module\\Monitoring\\Backend\\'
            . ucfirst($this->config->type)
            . '\\Query\\'
            . ucfirst($table)
            . 'Query';
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
        reset($configs);
        return key($configs);
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

        $config = self::$backendConfigs[$name];
        self::$backendInstances[$name] = $backend = new self($config);
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
