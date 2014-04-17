<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Icinga\Exception\ProgrammingError;
use Icinga\Data\Selectable;
use Icinga\Data\Queryable;

/**
 * Data view and query loader tied to a backend type
 */
class Backend implements Selectable, Queryable
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
        $queryClass = $this->resolveQueryName($viewClass::getQueryName());
        return new $viewClass(new $queryClass($this->resource), $columns);
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
            throw new ProgrammingError('DataView ' . ucfirst($viewName) . ' does not exist');
        }
        return $viewClass;
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
                'Query ' . ucfirst($queryName) . ' does not exist for backend ' . ucfirst($this->type)
            );
        }
        return $queryClass;
    }
}
