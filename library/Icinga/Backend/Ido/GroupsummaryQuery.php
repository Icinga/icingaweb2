<?php

namespace Icinga\Backend\Ido;
abstract class GroupsummaryQuery extends Query
{
    protected $name_alias;
    protected $sub_group_column;
    protected $sub_query;
    protected $sub_count_query;

    protected $available_columns = array(
        'ok'           => 'SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END)',
        'critical'     => 'SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'critical_dt'  => 'SUM(CASE WHEN state = 2 AND downtime = 1 THEN 1 ELSE 0 END)',
        'critical_ack' => 'SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
        'unknown'      => 'SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'unknown_dt'   => 'SUM(CASE WHEN state = 3 AND downtime = 1 THEN 1 ELSE 0 END)',
        'unknown_ack'  => 'SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
        'warning'      => 'SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END)',
        'warning_dt'   => 'SUM(CASE WHEN state = 1 AND downtime = 1 THEN 1 ELSE 0 END)',
        'warning_ack'  => 'SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)',
        'last_state_change' => 'UNIX_TIMESTAMP(MAX(last_state_change))',
    );

    protected $order_columns = array(
        'state' => array(
            'ASC' => array(
                'ok ASC',
                'warning_dt ASC',
                'warning_ack ASC',
                'warning ASC',
                'unknown_dt ASC',
                'unknown_ack ASC',
                'unknown ASC',
                'critical_dt ASC',
                'critical_ack ASC',
                'critical ASC',
            ),
            'DESC' => array(
                'critical DESC',
                'unknown DESC',
                'warning DESC',
                'critical_ack DESC',
                'critical_dt DESC',
                'unknown_ack DESC',
                'unknown_dt DESC',
                'warning_ack DESC',
                'warning_dt DESC',
                'ok DESC',
            ),
            'default' => 'DESC'
        )
    );

    abstract protected function addSummaryJoins($query);

    protected function init()
    {
        parent::init();
        if ($this->dbtype === 'oracle') {
            $this->columns['last_state_change'] = 'localts2unixts(MAX(last_state_change))';
        }
    }

    protected function createQuery()
    {
        $this->columns[$this->name_alias] = $this->name_alias;
        $this->order_columns['state']['ASC'][] = $this->name_alias . ' ASC';
        $this->order_columns['state']['DESC'][] = $this->name_alias . ' DESC';
        $this->order_columns['name'] = array(
            'ASC'     => array( $this->name_alias . ' ASC'),
            'DESC'    => array( $this->name_alias . ' DESC'),
            'default' => 'ASC'
        );
        $sub_query = $this->createSubQuery();
        // $sub_query->group($this->sub_group_column);
        // $sub_query->columns(array($this->name_alias => 'MAX(' . $this->sub_group_column . ')'));
        $sub_query->columns(array($this->name_alias => $this->sub_group_column ));
        $this->addSummaryJoins($sub_query);
        $query = $this->db->select()->from(
            array('sub' => $sub_query),
            array()
        );
        $query->group($this->name_alias);
        $this->sub_query = $sub_query;
        return $query;
    }


    protected function createCountQuery()
    {
        $this->sub_count_query = $this->createCountSubQuery();
        $this->sub_count_query->group($this->sub_group_column);
        $this->addSummaryJoins($this->sub_count_query);
        $count = $this->db->select()->from(
            array('cnt' => $this->sub_count_query),
            array()
        );
        return $count;
    }

    protected function createSubQuery()
    {
    
        $query = $this->db->select()
        ->from(
            array('so' => $this->prefix . 'objects'),
            array(
                // MAX seems to be useless, but is required as of the GROUP below
                // 'state'    => 'MAX(ss.current_state)',
                'state'    => 'ss.current_state',
                // 'ack'      => 'MAX(ss.problem_has_been_acknowledged)',
                'ack'      => 'ss.problem_has_been_acknowledged',
                // 'downtime' => 'MAX(CASE WHEN (dt.object_id IS NULL) THEN 0 ELSE 1 END)',
                // 'downtime' => 'MAX(CASE WHEN (scheduled_downtime_depth = 0) THEN 0 ELSE 1 END)',
                'downtime' => 'CASE WHEN (scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
                // 'last_state_change' => 'MAX(ss.last_state_change)',
                'last_state_change' => 'ss.last_state_change',
            )
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            "so.$this->object_id = ss.service_object_id",
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = ss.service_object_id',
            array()
        )/*->joinLeft(
            array('dt' => $this->prefix . 'scheduleddowntime'),
            "so.$this->object_id = dt.object_id"
          . ' AND dt.is_in_effect = 1',
            array()
        )->joinLeft(
            array('co' => $this->prefix . 'comments'),
            "so.$this->object_id = co.object_id",
            array()
        )*/
        ->where('so.is_active = 1')
        ->where('so.objecttype_id = 2')
        // Group is required as there could be multiple comments:
        // ->group('so.' . $this->object_id)
        ;
        return $query;
    }

    protected function createCountSubQuery()
    {
        return $this->db->select()
        ->from(
            array('so' => $this->prefix . 'objects'),
            array('state' => 'MAX(ss.current_state)')
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            "so.$this->object_id = ss.service_object_id",
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = ss.service_object_id',
            array()
        );
    }

    public function where($column, $value = null)
    {
        if ($column === 'problems') {
            if ($value === 'true') {
                $this->query->having('(SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 2 AND downtime = 1 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 2 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 3 AND downtime = 1 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 3 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 0 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 1 AND downtime = 1 THEN 1 ELSE 0 END) +
SUM(CASE WHEN state = 1 AND downtime = 0 AND ack = 1 THEN 1 ELSE 0 END)) > 0');
                $this->sub_count_query->where('ss.current_state > 0');
                $this->sub_query->where('ss.current_state > 0');
            }
        } elseif ($column === 'search') {
            if ($value) {
                // $this->sub_query->where($this->name_alias . ' LIKE ?', '%' . $value . '%');
                $this->sub_query->where($this->sub_group_column . ' LIKE ?', '%' . $value . '%');
                $this->sub_count_query->where($this->sub_group_column . ' LIKE ?', '%' . $value . '%');
            }
        } else {
            parent::where($column, $value);
        }
        return $this;
    }
}

