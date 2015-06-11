<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service state history records
 */
class ServicestatehistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * Array to map type names to type ids for query optimization
     *
     * @var array
     */
    protected $types = array(
        'soft_state' => 0,
        'hard_state' => 1
    );

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'statehistory' => array(
            'host'                  => 'so.name1 COLLATE latin1_general_ci',
            'host_name'             => 'so.name1',
            'object_type'           => '(\'service\')',
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_host'          => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        ),
        'history' => array(
            'type'      => "(CASE WHEN sh.state_type = 1 THEN 'hard_state' ELSE 'soft_state' END)",
            'timestamp' => 'UNIX_TIMESTAMP(sh.state_time)',
            'object_id' => 'sh.object_id',
            'state'     => 'sh.state',
            'output'    => "('[ ' || sh.current_check_attempt || '/' || sh.max_check_attempts || ' ] ' || sh.output)",
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host_alias'        => 'h.alias',
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci'
        )
    );

    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(sh.state_time)') {
            return 'sh.state_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } elseif (
            $col === $this->columnMap['history']['type']
            && ! is_array($expression)
            && array_key_exists($expression, $this->types)
        ) {
            return 'sh.state_type ' . $sign . ' ' . $this->types[$expression];
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('sh' => $this->prefix . 'statehistory'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sh.object_id',
            array()
        )->where(
            'so.is_active = ?',
            1
        )->where(
            'so.objecttype_id = ?',
            2
        );
        $this->joinedVirtualTables['statehistory'] = true;
        $this->joinedVirtualTables['history'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id',
            array()
        )->where(
            'hgo.is_active = ?',
            1
        )
        ->where(
            'hgo.objecttype_id = ?',
            3
        );
        $this->select->group(array('sh.statehistory_id', 'so.name1', 'so.name2'));
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = so.object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.' . $this->servicegroup_id . ' = sgm.servicegroup_id',
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        );
        $this->select->group(array('sh.statehistory_id', 'so.name1', 'so.name2'));
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }
}
