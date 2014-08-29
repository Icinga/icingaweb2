<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Icinga\Exception\ProgrammingError;
use Icinga\Data\Selectable;
use Icinga\Data\Queryable;
use Icinga\Data\ConnectionInterface;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;

/**
 * Data view and query loader tied to a backend type
 */
class Backend implements Selectable, Queryable, ConnectionInterface
{
    /**
     * Resource
     *
     * @var mixed
     */
    protected $resource;

    /**
     * Type
     *
     * @var string
     */
    protected $type;

    protected $name;

    /**
     * Create a new backend
     *
     * @param   mixed   $resource
     * @param   string  $type
     */
    public function __construct($resource, $type)
    {
        $this->resource = $resource;
        $this->type = $type;
    }

    // Temporary workaround, we have no way to know our name
    protected function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Create a backend
     *
     * @param   string $backendName Name of the backend or null for creating the default backend which is the first INI
     *                              configuration entry not being disabled
     *
     * @return  Backend
     * @throws  ConfigurationError  When no backend has been configured or all backends are disabled or the
     *                              configuration for the requested backend does either not exist or it's disabled
     */
    public static function createBackend($backendName = null)
    {
        $allBackends = array();
        $defaultBackend = null;
        foreach (IcingaConfig::module('monitoring', 'backends') as $name => $config) {
            if (!(bool) $config->get('disabled', false) && $defaultBackend === null) {
                $defaultBackend = $config;
            }
            $allBackends[$name] = $config;
        }
        if (empty($allBackends)) {
            throw new ConfigurationError('No backend has been configured');
        }
        if ($defaultBackend === null) {
            throw new ConfigurationError('All backends are disabled');
        }
        if ($backendName === null) {
            $backendConfig = $defaultBackend;
        } else {
            if (!array_key_exists($backendName, $allBackends)) {
                throw new ConfigurationError('No configuration for backend %s', $backendName);
            }
            $backendConfig = $allBackends[$backendName];
            if ((bool) $backendConfig->get('disabled', false)) {
                throw new ConfigurationError(
                    'Configuration for backend %s available but backend is disabled',
                    $backendName
                );
            }
        }
        $resource = ResourceFactory::create($backendConfig->resource);
        if ($backendConfig->type === 'ido' && $resource->getDbType() !== 'oracle') {
            // TODO(el): The resource should set the table prefix
            $resource->setTablePrefix('icinga_');
        }
        $backend = new Backend($resource, $backendConfig->type);
        $backend->setName($backendName);
        return $backend;
    }

public function getResource()
{
    return $this->resource;
}

    /**
     * Backend entry point
     *
     * @return self
     */
    public function select()
    {
        return $this;
    }

    /**
     * Create a data view to fetch data from
     *
     * @param   string  $viewName
     * @param   array   $columns
     *
     * @return  DataView
     */
    public function from($viewName, array $columns = null)
    {
        $viewClass = $this->resolveDataViewName($viewName);
        return new $viewClass($this, $columns);
    }

    /**
     * View name to class name resolution
     *
     * @param   string $viewName
     *
     * @return  string
     * @throws  ProgrammingError When the view does not exist
     */
    protected function resolveDataViewName($viewName)
    {
        $viewClass = '\\Icinga\\Module\\Monitoring\\DataView\\' . ucfirst($viewName);
        if (!class_exists($viewClass)) {
            throw new ProgrammingError(
                'DataView %s does not exist',
                ucfirst($viewName)
            );
        }
        return $viewClass;
    }

    public function getQueryClass($name)
    {
        return $this->resolveQueryName($name);
    }

    /**
     * Query name to class name resolution
     *
     * @param   string $queryName
     *
     * @return  string
     * @throws  ProgrammingError When the query does not exist for this backend
     */
    protected function resolveQueryName($queryName)
    {
        $queryClass = '\\Icinga\\Module\\Monitoring\\Backend\\'
            . ucfirst($this->type)
            . '\\Query\\'
            . ucfirst($queryName)
            . 'Query';
        if (!class_exists($queryClass)) {
            throw new ProgrammingError(
                'Query "%s" does not exist for backend %s',
                ucfirst($queryName),
                ucfirst($this->type)
            );
        }
        return $queryClass;
    }
}
