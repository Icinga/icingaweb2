<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend;

use Icinga\Application\Config;

/**
 * Icinga Backend Abstract
 *
 * @package Icinga\Backend
 */
abstract class AbstractBackend
{
    /**
     * @var \Zend_Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $extensions = array();

    /**
     * Backend constructor, should not be overwritten
     *
     * Makes sure that $this->config exists. Config is a Zend_Config
     * object right now, only the main Config is an Icinga one
     *
     * return void
     */
    final public function __construct(\Zend_Config $config = null)
    {
        if ($config == null) {
            $config = new \Zend_Config(array());
        }
        $this->config = $config;
        $this->init();
    }

    /**
     * Override this function for initialization tasks
     *
     * return void
     */
    protected function init()
    {
    }

    /**
     * Dummy function for fluent code
     *
     * return \Icinga\Backend\Ido
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
     * @param array $fields
     * @throws \Exception
     * @return
     * @internal param \Icinga\Backend\Fields $array return \Icinga\Backend\Ido\Query* return \Icinga\Backend\Ido\Query
     */
    public function from($virtual_table, $fields = array())
    {
        $classname = $this->tableToClassName($virtual_table);
        if (!class_exists($classname)) {
            throw new \Exception(sprintf('Asking for invalid virtual table %s', $classname));
        }
        $query = new $classname($this, $fields);
        return $query;
    }

    /**
     * @param $virtual_table
     * @return bool
     */
    public function hasView($virtual_table)
    {
        return class_exists($this->tableToClassName($virtual_table));
    }

    /**
     * @param $virtual_table
     * @return string
     */
    protected function tableToClassName($virtual_table)
    {
        if (strpos($virtual_table, "/") !== false) {
            list($module, $classname) = explode("/", $virtual_table, 2);
            $class = array_pop(explode("\\", get_class($this)));
            return 'Icinga\\' . ucfirst($module) . '\\Backend\\' . $class . '\\' . ucfirst($classname) . 'Query';
        } else {
            return get_class($this) . '\\' . ucfirst($virtual_table . 'Query');
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return preg_replace('~^.+\\\(.+?)$~', '$1', get_class($this));
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->getName();
    }
}
