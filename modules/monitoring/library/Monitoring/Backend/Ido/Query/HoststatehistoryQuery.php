<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host state history records
 */
class HoststatehistoryQuery extends IdoQuery
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
            'host'          => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'     => 'ho.name1',
            'object_type'   => '(\'host\')'
        ),
        'history' => array(
            'type'      => "(CASE WHEN hh.state_type = 1 THEN 'hard_state' ELSE 'soft_state' END)",
            'timestamp' => 'UNIX_TIMESTAMP(hh.state_time)',
            'object_id' => 'hh.object_id',
            'state'     => 'hh.state',
            'output'    => "('[ ' || hh.current_check_attempt || '/' || hh.max_check_attempts || ' ] ' || hh.output)",
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
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(hh.state_time)') {
            return 'hh.state_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } elseif (
            $col === $this->columnMap['history']['type']
            && ! is_array($expression)
            && array_key_exists($expression, $this->types)
        ) {
            return 'hh.state_type ' . $sign . ' ' . $this->types[$expression];
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
            array('hh' => $this->prefix . 'statehistory'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hh.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->joinedVirtualTables['statehistory'] = true;
        $this->joinedVirtualTables['history'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->joinLeft(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->joinLeft(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            array()
        );
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.' . $this->servicegroup_id . ' = sgm.servicegroup_id',
            array()
        )->joinLeft(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            array()
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = ho.object_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $group = array();
        if ($this->hasJoinedVirtualTable('hostgroups') || $this->hasJoinedVirtualTable('services')) {
            $group = array('hh.statehistory_id', 'ho.object_id');
            if ($this->hasJoinedVirtualTable('hosts')) {
                $group[] = 'h.host_id';
            }
        }

        return $group;
    }
}
