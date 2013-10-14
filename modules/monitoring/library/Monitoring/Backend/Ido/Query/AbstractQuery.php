<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Db\Query;
use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Icinga\Filter\Query\Tree;
use Icinga\Filter\Filterable;
use Icinga\Module\Monitoring\Filter\Backend\IdoQueryConverter;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;

abstract class AbstractQuery extends Query implements Filterable
{
    protected $prefix;
    protected $idxAliasColumn;
    protected $idxAliasTable;
    protected $columnMap = array();
    protected $query;
    protected $customVars = array();
    protected $joinedVirtualTables = array();
    protected $object_id       = 'object_id';
    protected $host_id         = 'host_id';
    protected $hostgroup_id    = 'hostgroup_id';
    protected $service_id      = 'service_id';
    protected $servicegroup_id = 'servicegroup_id';
    protected $contact_id      = 'contact_id';
    protected $contactgroup_id = 'contactgroup_id';
    protected $aggregateColumnIdx = array();
    protected $allowCustomVars = false;

    public function isAggregateColumn($column)
    {
        return array_key_exists($column, $this->aggregateColumnIdx);
    }

    public function order($col, $dir = null)
    {
        $this->requireColumn($col);
        if ($this->isCustomvar($col)) {
            // TODO: Doesn't work right now. Does it?
            $col = $this->getCustomvarColumnName($col);
        } elseif ($this->hasAliasName($col)) {
            $col = $this->aliasToColumnName($col);
        } else {
            throw new \InvalidArgumentException('Can\'t order by column '.$col);
        }
        $this->order_columns[] = array($col, $dir);
        return $this;
    }

    public function applyFilter(Tree $filter)
    {
        foreach ($filter->getAttributes() as $target) {
            $this->requireColumn($target);
        }
        $converter = new IdoQueryConverter($this);
        $converter->treeToSql($filter, $this->baseQuery);
    }

    public function isValidFilterTarget($field)
    {
        return $this->getMappedField($field) !== null;
    }

    public function getMappedField($field)
    {
        foreach ($this->columnMap as $columnSource => $columnSet) {
            if (isset($columnSet[$field])) {
                return $columnSet[$field];
            }
        }
        return null;
    }

    public function isTimestamp($field)
    {
        $mapped = $this->getMappedField($field);
        if ($mapped === null) {
            return false;
        }
        return stripos($mapped, 'UNIX_TIMESTAMP') !== false;
    }

    protected function init()
    {
        parent::init();
        // TODO: $this->applyDbSpecificWorkarounds
        $this->prefix = $this->ds->getTablePrefix();

        if ($this->ds->getDbType() === 'oracle') {
            $this->object_id = $this->host_id = $this->service_id
                = $this->hostgroup_id = $this->servicegroup_id
                = $this->contact_id = $this->contactgroup_id = 'id'; // REALLY?
            foreach ($this->columnMap as $table => & $columns) {
                foreach ($columns as $key => & $value) {
                    $value = preg_replace('/UNIX_TIMESTAMP/', 'localts2unixts', $value);
                    $value = preg_replace('/ COLLATE .+$/', '', $value);
                }
            }
        }
        if ($this->ds->getDbType() === 'pgsql') {
            foreach ($this->columnMap as $table => & $columns) {
                foreach ($columns as $key => & $value) {
                    $value = preg_replace('/ COLLATE .+$/', '', $value);
                    $value = preg_replace('/inet_aton\(([[:word:].]+)\)/i', '$1::inet - \'0.0.0.0\'', $value);
                }
            }
        }

        $this->joinBaseTables();
        $this->prepareAliasIndexes();
    }

    protected function joinBaseTables()
    {
        reset($this->columnMap);
        $table = key($this->columnMap);

        $this->baseQuery = $this->db->select()->from(
            array($table => $this->prefix . $table),
            array()
        );

        $this->joinedVirtualTables = array($table => true);
    }

    protected function prepareAliasIndexes()
    {
        foreach ($this->columnMap as $tbl => & $cols) {
            foreach ($cols as $alias => $col) {
                $this->idxAliasTable[$alias] = $tbl;
                $this->idxAliasColumn[$alias] = preg_replace('~\n\s*~', ' ', $col);
            }
        }
    }

    protected function beforeCreatingCountQuery()
    {
    }

    protected function beforeCreatingSelectQuery()
    {
        $this->setRealColumns();
        $classParts = explode('\\', get_class($this));
        Benchmark::measure(sprintf('%s ready to run', array_pop($classParts)));
    }

    public function setRealColumns()
    {
        $columns = $this->columns;
        $this->columns = array();
        if (empty($columns)) {
            $columns = $this->getDefaultColumns();
        }

        foreach ($columns as $alias => $col) {
            $this->requireColumn($col);
            if ($this->isCustomvar($col)) {
                $name = $this->getCustomvarColumnName($col);
            } else {
                $name = $this->aliasToColumnName($col);
            }
            if (is_int($alias)) {
                $alias = $col;
            }

            $this->columns[$alias] = preg_replace('|\n|', ' ', $name);
        }
        return $this;
    }

    protected function getDefaultColumns()
    {
        reset($this->columnMap);
        $table = key($this->columnMap);
        return array_keys($this->columnMap[$table]);
    }

    protected function requireColumn($alias)
    {
        if ($this->hasAliasName($alias)) {
            $this->requireVirtualTable($this->aliasToTableName($alias));
        } elseif ($this->isCustomVar($alias)) {
            $this->requireCustomvar($alias);
        } else {
            throw new ProgrammingError(sprintf('Got invalid column: %s', $alias));
        }
        return $this;
    }

    protected function hasAliasName($alias)
    {
        return array_key_exists($alias, $this->idxAliasColumn);
    }

    protected function requireVirtualTable($name)
    {
        if ($this->hasJoinedVirtualTable($name)) {
            return $this;
        }
        return $this->joinVirtualTable($name);
    }

    protected function joinVirtualTable($table)
    {
        $func = 'join' . ucfirst($table);
        if (method_exists($this, $func)) {
            $this->$func();
        } else {
            throw new ProgrammingError(
                sprintf(
                    'Cannot join "%s", no such table found',
                    $table
                )
            );
        }
        $this->joinedVirtualTables[$table] = true;
        return $this;
    }

    protected function aliasToTableName($alias)
    {
        return $this->idxAliasTable[$alias];
    }

    protected function isCustomVar($alias)
    {
        return $this->allowCustomVars && $alias[0] === '_';
    }

    protected function requireCustomvar($customvar)
    {
        if (! $this->hasCustomvar($customvar)) {
            $this->joinCustomvar($customvar);
        }
        return $this;
    }

    protected function hasCustomvar($customvar)
    {
        return array_key_exists($customvar, $this->customVars);
    }

    protected function joinCustomvar($customvar)
    {
        // TODO: This is not generic enough yet
        list($type, $name) = $this->customvarNameToTypeName($customvar);
        $alias = ($type === 'host' ? 'hcv_' : 'scv_') . strtolower($name);

        $this->customVars[$customvar] = $alias;

        // TODO: extend if we allow queries with only hosts / only services
        //       ($leftcol s.host_object_id vs h.host_object_id
        if ($this->hasJoinedVirtualTable('services')) {
            $leftcol = 's.' . $type . '_object_id';
        } else {
            $leftcol = 'h.' . $type . '_object_id';
        }
        $joinOn = $leftcol
                . ' = '
                . $alias
                . '.object_id'
                . ' AND '
                . $alias
                . '.varname = '
                . $this->db->quote(strtoupper($name));

        $this->baseQuery->joinLeft(
            array($alias => $this->prefix . 'customvariablestatus'),
            $joinOn,
            array()
        );

        return $this;
    }

    protected function customvarNameToTypeName($customvar)
    {
        // TODO: Improve this:
        if (! preg_match('~^_(host|service)_([a-zA-Z0-9_]+)$~', $customvar, $m)) {
            throw new ProgrammingError(
                sprintf(
                    'Got invalid custom var: "%s"',
                    $customvar
                )
            );
        }
        return array($m[1], $m[2]);
    }

    protected function hasJoinedVirtualTable($name)
    {
        return array_key_exists($name, $this->joinedVirtualTables);
    }

    protected function getCustomvarColumnName($customvar)
    {
        return $this->customVars[$customvar] . '.varvalue';
    }

    public function aliasToColumnName($alias)
    {
        return $this->idxAliasColumn[$alias];
    }

    protected function createSubQuery($queryName, $columns = array())
    {
        $class = '\\'
            . substr(__CLASS__, 0, strrpos(__CLASS__, '\\') + 1)
            . ucfirst($queryName) . 'Query';
        $query = new $class($this->ds, $columns);
        return $query;
    }
}
