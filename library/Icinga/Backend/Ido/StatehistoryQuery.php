<?php

namespace Icinga\Backend\Ido;
class StatehistoryQuery extends Query
{
    protected $available_columns = array(
        // Host config
        'host_name'           => 'sho.name1',
        'service_description' => 'sho.name2',
        'object_type'         => "CASE WHEN sho.objecttype_id = 1 THEN 'host' ELSE 'service' END",
        'timestamp'       => 'UNIX_TIMESTAMP(sh.state_time)',
        'state'           => 'sh.state',
        'last_state'      => 'sh.last_state',
        'last_hard_state' => 'sh.last_hard_state',
        'attempt'         => 'sh.current_check_attempt',
        'max_attempts'    => 'sh.max_check_attempts',
        'output'          => 'sh.output', // no long_output in browse

    );
    protected $order_columns = array(
        'timestamp' => array(
            'ASC' => array(
                'state_time ASC',
             ),
             'DESC' => array(
                'state_time DESC',
             ),
             'default' => 'DESC'
        )
    );

    protected function init()
    {
        parent::init();
        if ($this->dbtype === 'oracle') {
            $this->columns['timestamp'] = 
                'localts2unixts(sh.state_time)';
        }
    }

    public function where($column, $value = null)
    {
        if ($column === 'problems') {
            if ($value === 'true') {
                foreach (array($this->query, $this->count_query) as $query) {
                    $query->where('sh.state > 0');
                }
            }
            return $this;
        }
        if ($column === 'host') {
            foreach (array($this->query, $this->count_query) as $query) {
                $query->where('sho.name1 = ?', $value);
            }
            return $this;
        }
        if ($column === 'service') {
            foreach (array($this->query, $this->count_query) as $query) {
                $query->where('sho.name2 = ?', $value);
            }
            return $this;
        }

        parent::where($column, $value);
        return $this;
    }

    protected function createQuery()
    {    
        $query = $this->db->select()->from(
            array('sh' => $this->prefix . 'statehistory'),
            array()
        // )->join(
        )->joinLeft( // LEFT is bullshit but greatly helps MySQL
        // Problem -> has to be INNER once permissions are in effect
        // Therefore this should probably be "flexible" or handled in another
        // way
            array('sho' => $this->prefix . 'objects'),
            'sho.' . $this->object_id . ' = sh.object_id AND sho.is_active = 1',
            array()
        );

        return $query;
    }
}

