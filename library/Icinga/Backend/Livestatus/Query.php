<?php

namespace Icinga\Backend\Livestatus;
abstract class Query extends \Icinga\Backend\Query
{
    protected $connection;

    protected $query;

    protected $ordered = false;
    protected $finalized = false;

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
                'IF (ss.current_state = 3, 2, IF(ss.current_state = 2, 3, ss.current_state)) DESC',
                'ss.problem_has_been_acknowledged ASC',
                'IF(dt.object_id IS NULL, 0, 1) ASC',
                'ss.last_state_change DESC'
            ),
            'DESC' => array(
                'IF (ss.current_state = 3, 2, IF(ss.current_state = 3, 2, ss.current_state)) DESC',
                'ss.problem_has_been_acknowledged ASC',
                'IF(dt.object_id IS NULL, 0, 1) ASC',
                'ss.last_state_change DESC'
            ),
            'default' => 'ASC'
        )
    );

    abstract protected function createQuery();

    protected function init()
    {
        $this->connection  = $this->backend->getConnection();
        $this->query       = $this->createQuery();
    }

    public function where($column, $value = null)
    {
        if ($column === 'problems') {
            if ($value === 'true') {
                $this->query->where('state > 0');
            } elseif ($value === 'false') {
                $this->query->where('state = 0');
            }
            return $this;
        }
        if ($column === 'handled') {
            if ($value === 'true') {
                // TODO: Not yet
            } elseif ($value === 'false') {
                // TODO: Not yet
            }
            return $this;
        }

        // Ugly temporary hack:
        $colcheck = preg_replace('~[\s=><].+$~', '', $column);
        if (array_key_exists($colcheck, $this->available_columns)) {
            $query->where(preg_replace(
                '~' . $colcheck . '~',
                $this->available_columns[$colcheck],
                $column
            ), $value);
        } else {
            $this->query->where($column, $value);
        }

        return $this;
    }

    protected function finalize()
    {
        return $this;

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

    public function applyFilters($filters = array())
    {
        foreach ($filters as $key => $val) {
            $this->where($key, $val);
        }
        return $this;
    }

    public function order($column = '', $dir = null)
    {
        $this->ordered = true;
        return $this->applyOrder($column, $dir);
    }

    protected function applyOrder($order = '', $order_dir = null)
    {
        return $this;    

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

    public function count()
    {
        return $this->connection->count(
            $this->finalize()->query
        );
    }

    public function fetchAll()
    {
        return $this->connection->fetchAll($this->finalize()->query);
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


    // For debugging and testing only:
    public function __toString()
    {
        $this->finalize();
        return (string) $this->query;
    }

}

