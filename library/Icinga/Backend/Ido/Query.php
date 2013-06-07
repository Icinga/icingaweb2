<?php

namespace Icinga\Backend\Ido;
abstract class Query extends \Icinga\Backend\Query
{
    protected $db;
    protected $prefix;

    protected $query;
    protected $count_query;
    protected $count_columns;

    protected $ordered         = false;
    protected $finalized       = false;
    protected $object_id       = 'object_id';
    protected $hostgroup_id    = 'hostgroup_id';
    protected $servicegroup_id = 'servicegroup_id';

    protected $custom_cols = array();
    
    /**
     * Available sort combinations
     */
    protected $order_columns = array(
        'host' => array(
            'ASC' => array(
                'host_name ASC',
                'service_description ASC'
             ),
             'DESC' => array(
                'host_name DESC',
                'service_description ASC'
             ),
             'default' => 'ASC'
        ),
        'host_address' => array(
            'ASC' => array(
                'host_ipv4 ASC',
                'service_description ASC'
             ),
             'DESC' => array(
                'host_ipv4 ASC',
                'service_description ASC'
             ),
             'default' => 'ASC'
        ),
        'service' => array(
            'ASC' => array(
                'service_description ASC'
            ),
            'DESC' => array(
                'service_description DESC'
            ),
            'default' => 'ASC'
        ),
        'service_state_change' => array(
            'ASC' => array(
                'ss.last_state_change ASC'
            ),
            'DESC' => array(
                'ss.last_state_change DESC'
            ),
            'default' => 'DESC'
        ),
        'service_state' => array(
            'ASC' => array(
                'CASE WHEN (ss.current_state = 3) THEN 2 WHEN (ss.current_state = 2) THEN 3 ELSE ss.current_state END DESC', // TODO: distinct severity in a better way
                'ss.problem_has_been_acknowledged ASC',
                // 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END ASC',
                'service_in_downtime ASC', // TODO: Check if all dbs allow sorting by alias
                'ss.last_state_change DESC',
                'so.name1 ASC',
                'so.name2 ASC'
            ),
            'DESC' => array(
                'CASE WHEN (ss.current_state = 3) THEN 2 WHEN (ss.current_state = 2) THEN 3 ELSE ss.current_state END ASC',
                'ss.problem_has_been_acknowledged ASC',
                // 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END ASC',
                'service_in_downtime ASC',
                'ss.last_state_change DESC'
            ),
            'default' => 'ASC'
        )
    );

    abstract protected function createQuery();

    public function dump()
    {
        $this->finalize();
        return "QUERY\n=====\n"
             . $this->query
             . "\n\nCOUNT\n=====\n"
             . $this->count_query
             . "\n\n";
    }

    public function getCountQueryObject()
    {
        return $this->finalize()->count_query;
    }

    public function getQueryObject()
    {
        return $this->finalize()->query;
    }

    protected function createCountQuery()
    {
        return clone($this->query);
    }

    protected function init()
    {
        $this->db     = $this->backend->getAdapter();
        $this->dbtype = $this->backend->getDbType();
        if ($this->dbtype === 'oracle') {
            $this->object_id = $this->hostgroup_id = $this->servicegroup_id = 'id';
        }
        $this->prefix = $this->backend->getPrefix();
        $this->query  = $this->createQuery();
        $this->count_query = $this->createCountQuery();
    }

    protected function finalize()
    {
        if ($this->finalized) return $this;
        $this->finalized = true;
        $this->query->columns($this->columns);
        if ($this->count_columns === null) {
            $this->count_columns = array('cnt' => 'COUNT(*)');
        }
        if (! $this->ordered) {
            $this->order();
        }
        $this->count_query->columns($this->count_columns);
        return $this;
    }

    protected function prepareServiceStatesQuery()
    {
        $query = $this->db->select()
        ->from(
            array('hs' => $this->prefix . 'hoststatus'),
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            "so.$this->object_id = s.service_object_id AND so.is_active = 1",
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            "so.$this->object_id = ss.service_object_id",
            array()
        );
        // $this->joinServiceDowntimes($query);
        // $query->group('so.object_id');
        return $query;
    }

    protected function prepareServicesCount()
    {
        // TODO: Depends on filter, some cols could be avoided
        $query = $this->db->select()->from(
            array('hs' => $this->prefix . 'hoststatus'),
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            "so.$this->object_id = s.service_object_id AND so.is_active = 1",
            "COUNT(so.$this->object_id)"
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            "so.$this->object_id = ss.service_object_id",
            array()
        );
        // $this->joinServiceDowntimes($query);
        return $query;
    }

    protected function joinHostgroups($query = null)
    {
        if ($query === null) $query = $this->query;
        
        $query->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = h.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            "hgm.hostgroup_id = hg.$this->hostgroup_id",
            array()
        );

        return $this;
    }

    protected function joinServiceHostgroups($query)
    {
        if ($query === null) $query = $this->query;

        $query->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            "hgm.hostgroup_id = hg.$this->hostgroup_id",
            array()
        );

        return $this;
    }

    protected function joinServicegroups($query)
    {
        if ($query === null) $query = $this->query;

        $query->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            "sgm.servicegroup_id = sg.$this->servicegroup_id",
            array()
        );

        return $this;
    }

    protected function joinServiceDowntimes($query)
    {
        $query->joinLeft(
            array('dt' => $this->prefix . 'scheduleddowntime'),
            "so.$this->object_id = dt.object_id"
          . ' AND dt.is_in_effect = 1',
            array()
        );
        // NDO compat (doesn't work correctly like this):
        // $now = "'" . date('Y-m-d H:i:s') . "'";
        //   . ' AND dt.was_started = 1'
        //   . ' AND dt.scheduled_end_time > ' . $now
        //   . ' AND dt.actual_start_time < ' . $now,
        return $query;
    }

    public function where($column, $value = null)
    {
        // Ugly temporary hack:
        foreach (array($this->query, $this->count_query) as $query) {
            if ($column === 'search') {
                if ($this->dbtype === 'mysql') {
                    $query->where($this->db->quoteInto(
                        'so.name2 COLLATE latin1_general_ci LIKE ?'
                      . ' OR so.name1 COLLATE latin1_general_ci LIKE ?',
                        '%' . $value . '%',
                        '%' . $value . '%'
                    ));
                } else {
                    $query->where($this->db->quoteInto(
                        'LOWER(so.name2) LIKE ?'
                      . ' OR LOWER(so.name1) LIKE ?',
                        '%' . strtolower($value) . '%',
                        '%' . strtolower($value) . '%'
                    ));
                }
                continue;
            }
            // TODO: Check if this also works based on column:
            if ($column === 'hostgroups') {
                $this->appendHostgroupLimit($query, $value);
                continue;
            }
            if (preg_match('~^_([^_]+)_(.+)$~', $column, $m)) {
                switch($m[1]) {
                    case 'host':
                        $this->appendHostCustomVarLimit($query, $m[2], $value);
                        break;
                    case 'service':
                        $this->appendServiceCustomVarLimit($query, $m[2], $value);
                        break;
                }
                continue;
            }
            //$column = preg_replace('~^current_state~', 'ss.current_state', $column);
            if (array_key_exists($column, $this->available_columns)) {
                $column = $this->available_columns[$column];
            }
            $query->where($this->prepareFilterStringForColumn($column, $value));
        }
        
        /*->orWhere('last_state_change > ?', $new)*/
        return $this;
    }

    public function order($column = '', $dir = null)
    {
        $this->ordered = true;
        return $this->applyOrder($column, $dir);
    }

    protected function applyOrder($order = '', $order_dir = null)
    {
        if (! array_key_exists($order, $this->order_columns)) {
            $order = key($this->order_columns);
        }

        if ($order_dir === null) {
            $order_dir = $this->order_columns[$order]['default'];
        }
        foreach ($this->order_columns[$order][$order_dir] as $col) {
            $this->query->order($col);
        }
        return $this;
    }

    protected function addServiceComments($query = null)
    {
        if ($query === null) {
            $query = $this->query;
        }
        $query->joinLeft(
            array('co' => $this->prefix . 'comments'),
            "so.$this->object_id = co.object_id",
            array()
        )
        
        ->group('so.object_id')
        
        ;
        return $this;
    }

    /**
     * $column = col
     * $value  = abc,cde,cd*,!egh,!*hh*
     * -> (col IN ('abc', 'cde') OR col LIKE 'cd%') AND (col != 'egh' AND col NOT LIKE '%hh%')
     */
    protected function prepareFilterStringForColumn($column, $value)
    {
        $filter = '';
        $filters = array();
        
        $or  = array();
        $and = array();

        if (strpos($value, ',') !== false) {
            $value = preg_split('~,~', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (! is_array($value)) {
            $value = array($value);
        }

        // Go through all given values
        foreach ($value as $val) {
            // Value starting with - means negation
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
            // Starting with + enforces AND
            } elseif ($val[0] === '+') {
                $val = substr($val, 1);
                if (strpos($val, '*') === false) {
                    $and[] = $this->db->quoteInto($column . ' = ?', $val);
                } else {
                    $and[] = $this->db->quoteInto(
                        $column . ' LIKE ?',
                        str_replace('*', '%', $val)
                    );
                }
            // All others ar ORs:
            } else {
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

        if (! empty($or))  { $filters[] = implode(' OR ', $or); }
        if (! empty($and)) { $filters[] = implode(' AND ', $and); }
        if (! empty($filters)) {
            $filter = '(' . implode(') AND (', $filters) . ')';
        }

        return $filter;
    }

    protected function addCustomVarColumn($query, $alias, $name, $filter = null)
    {
        // TODO: Improve this:
        if (! preg_match('~^[a-zA-Z0-9_]+$~', $name)) {
            throw new \Exception(sprintf(
                'Got invalid custom var: "%s"',
                $name
            ));
        }
        $qobj = spl_object_hash($query);
        if (! array_key_exists($qobj, $this->custom_cols)) {
            $this->custom_cols[$qobj] = array();
        }
        
        if (array_key_exists($alias, $this->custom_cols[$qobj])) {
            if ($name !== $this->custom_cols[$qobj][$alias]) {
                throw new \Exception(sprintf(
                    'Cannot add CV alias "%s" twice with different target',
                    $alias
                ));
            }
            return $this;
        }
        $query->join(
            // TODO: Allow multiple limits with different aliases
            array($alias => $this->prefix . 'customvariablestatus'),
            's.host_object_id = ' . $alias . '.object_id'
          . ' AND ' . $alias . '.varname = '
          . $this->db->quote(strtoupper($name))
          //. ($filter === null ? '' : ' AND ' . $filter),
            ,
            array()
        );
        $this->custom_cols[$qobj][$alias] = $name;
        return $this;
    }
    
    protected function appendHostCustomVarLimit($query, $key, $value)
    {
        $alias = 'hcv_' . strtolower($key);
        $filter = $this->prepareFilterStringForColumn($alias . '.varvalue', $value);
        $this->addCustomVarColumn($query, $alias, $key);
        $query->where($filter);
        return $query;
    }

    protected function appendHostgroupLimit($query, $hostgroups)
    {
        return $query->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            "hgm.hostgroup_id = hg.$this->hostgroup_id",
            array()
       )
        ->where('hg.alias IN (?)', $hostgroups);
    }

    public function count()
    {
        return $this->db->fetchOne(
            $this->finalize()->count_query
        );
    }

    public function fetchAll()
    {
        return $this->db->fetchAll($this->finalize()->query);
    }

    public function fetchRow()
    {
        return $this->db->fetchRow($this->finalize()->query);
    }

    public function fetchOne()
    {
        return $this->db->fetchOne($this->finalize()->query);
    }

    public function fetchPairs()
    {
        return $this->db->fetchPairs($this->finalize()->query);
    }

    /**
     * Sets a limit count and offset to the query
     *
     * @param int $count  Number of rows to return
     * @param int $offset Row offset to start from
     * @return \Icinga\Backend\Query This Query object
     */
    public function limit($count = null, $offset = null)
    {
        $this->query->limit($count, $offset);
        return $this;
    }

    public function __toString()
    {
        $this->finalize();
        return (string) $this->query;
    }
}

