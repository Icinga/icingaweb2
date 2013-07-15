<?php

namespace Monitoring\Backend;

use Icinga\Data\DatasourceInterface;
use Icinga\Exception\ProgrammingError;
use Icinga\Application\Benchmark;
use Zend_Config;

class AbstractBackend implements DatasourceInterface
{
    protected $config;

    public function __construct(Zend_Config $config = null)
    {
        if ($config === null) {
            // $config = new Zend_Config(array()); ???
        }
        $this->config = $config;
        $this->init();
    }

    protected function init()
    {
    }
    
    /**
     * Dummy function for fluent code
     *
     * return self
     */
    public function select()
    {
        return $this;
    }

    /**
     * Create a Query object instance for given virtual table and desired fields
     *
     * Leave fields empty to get all available properties
     *
     * @param string Virtual table name
     * @param array  Fields
     * return self
     */
    public function from($virtual_table, $fields = array())
    {
        $classname = $this->tableToClassName($virtual_table);
        if (! class_exists($classname)) {
            throw new ProgrammingError(
                sprintf(
                    'Asking for invalid virtual table %s',
                    $classname
                )
            );
        }
        $query = new $classname($this, $fields);
        return $query;
    }

    public function hasView($virtual_table)
    {
        // TODO: This is no longer enough, have to check for Query right now
        return class_exists($this->tableToClassName($virtual_table));
    }

    protected function tableToClassName($virtual_table)
    {
        return 'Monitoring\\View\\'
             // . $this->getName()
             // . '\\'
             . ucfirst($virtual_table)
             . 'View';
    }

    public function getName()
    {
        return preg_replace('~^.+\\\(.+?)$~', '$1', get_class($this));
    }

    public function __toString()
    {
        return $this->getName();
    }
}
