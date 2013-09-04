<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Db\Query;
use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;

abstract class AbstractQuery extends Query
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
    protected $service_id      = 'service_id';
    protected $hostgroup_id    = 'hostgroup_id';
    protected $servicegroup_id = 'servicegroup_id';

    protected $allowCustomVars = false;
    
    protected function init()
    {
        parent::init();
        // TODO: $this->applyDbSpecificWorkarounds
        $this->prefix = $this->ds->getPrefix();

        if ($this->ds->getConnection()->getDbType() === 'oracle') {
            $this->object_id = $this->host_id = $this->service_id = $this->hostgroup_id = $this->servicegroup_id = 'id'; // REALLY?
            foreach ($this->columnMap as $table => & $columns) {
                foreach ($columns as $key => & $value) {
                    $value = preg_replace('/UNIX_TIMESTAMP/', 'localts2unixts', $value);
                    $value = preg_replace('/ COLLATE .+$/', '', $value);
                }
            }
        }
        if ($this->ds->getConnection()->getDbType() === 'pgsql') {
            foreach ($this->columnMap as $table => & $columns) {
                foreach ($columns as $key => & $value) {
                    $value = preg_replace('/ COLLATE .+$/', '', $value);
                }
            }
        }

        $this->prepareAliasIndexes();
        $this->joinBaseTables();
    }

    protected function isCustomVar($alias)
    {
        return $this->allowCustomVars && $alias[0] === '_';
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

    protected function prepareAliasIndexes()
    {
        foreach ($this->columnMap as $tbl => & $cols) {
            foreach ($cols as $alias => $col) {
                $this->idxAliasTable[$alias] = $tbl;
                $this->idxAliasColumn[$alias] = preg_replace('~\n\s*~', ' ', $col);
            }
        }
    }

    protected function getDefaultColumns()
    {
        return $this->columnMap['hostgroups'];
    }

    protected function beforeCreatingCountQuery()
    {
        $this->applyAllFilters();
    }

    protected function beforeCreatingSelectQuery()
    {
        $this->setRealColumns();
        $classParts = explode('\\', get_class($this));
        Benchmark::measure(sprintf('%s ready to run', array_pop($classParts)));
    }

    protected function applyAllFilters()
    {
        $filters = array();
        // TODO: Handle $special in a more generic way
        $special =  array('hostgroups', 'servicegroups');
        foreach ($this->filters as $f) {
            $alias = $f[0];
            $value = $f[1];
            $this->requireColumn($alias);

            if ($alias === 'hostgroups') {
                $col = 'hg.alias';
            } elseif ($alias === 'servicegroups') {
                $col = 'sg.alias';
            } elseif ($this->isCustomvar($alias)) {
                $col = $this->getCustomvarColumnName($alias);
            } elseif ($this->hasAliasName($alias)) {
                $col = $this->aliasToColumnName($alias);
            } else {
                throw new ProgrammingError(
                    'If you finished here, code has been messed up'
                );
            }
            $this->baseQuery->where($this->prepareFilterStringForColumn($col, $value));
        }
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
    
    public function setRealColumns()
    {
        $columns = $this->columns;
        $this->columns = array();
        if (empty($columns)) {
            $colums = $this->getDefaultColumns();
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
            
            $this->columns[$alias] = preg_replace('|\n|', ' ' , $name);
        }
        return $this;
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
    
    protected function aliasToColumnName($alias)
    {
        return $this->idxAliasColumn[$alias];
    }

    protected function aliasToTableName($alias)
    {
        return $this->idxAliasTable[$alias];
    }

    protected function hasJoinedVirtualTable($name)
    {
        return array_key_exists($name, $this->joinedVirtualTables);
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
            throw new ProgrammingError(sprintf(
                'Cannot join "%s", no such table found',
                $table
            ));
        }
        $this->joinedVirtualTables[$table] = true;
        return $this;
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

    protected function getCustomvarColumnName($customvar)
    {
        return $this->customVars[$customvar] . '.varvalue';
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

    protected function prepareFilterStringForColumn($column, $value)
    {
        $filter = '';
        $filters = array();
        
        $or  = array();
        $and = array();

        if (! is_array($value) && strpos($value, ',') !== false) {
            $value = preg_split('~,~', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (! is_array($value)) {
            $value = array($value);
        }

        // Go through all given values
        foreach ($value as $val) {
            if ($val === '') {
                // TODO: REALLY??
                continue;
            }
            // Value starting with minus: negation
            if ($val[0] === '-') {
                $val = substr($val, 1);
                if (strpos($val, '*') === false) {
                    $and[] = $this->db->quoteInto($column . ' != ?', $val);
                } else {
                    $and[] = $this->db->quoteInto(
                        $column . ' NOT LIKE ?',
                        str_replace('*', '%', $val)
                    );
                }
            } elseif ($val[0] === '+') { // Value starting with +: enforces AND
                // TODO: depends on correct URL handling, not given in all
                //       ZF versions
                $val = substr($val, 1);
                if (strpos($val, '*') === false) {
                    $and[] = $this->db->quoteInto($column . ' = ?', $val);
                } else {
                    $and[] = $this->db->quoteInto(
                        $column . ' LIKE ?',
                        str_replace('*', '%', $val)
                    );
                }
            } else { // All others ar ORs:
                if (strpos($val, '*') === false) {
                    $or[] = $this->db->quoteInto($column . ' = ?', $val);
                } else {
                    $or[] = $this->db->quoteInto(
                        $column . ' LIKE ?',
                        str_replace('*', '%', $val)
                    );
                }
            }
        }

        if (! empty($or)) {
            $filters[] = implode(' OR ', $or);
        }

        if (! empty($and)) {
            $filters[] = implode(' AND ', $and);
        }

        if (! empty($filters)) {
            $filter = '(' . implode(') AND (', $filters) . ')';
        }

        return $filter;
    }
}
