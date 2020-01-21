<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host flapping start history records
 */
class HostflappingstarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('flappinghistory' => array('hfh.flappinghistory_id', 'ho.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('hostgroups', 'services');

    protected $subQueryTargets = array(
        'hostgroups'    => 'hostgroup',
        'servicegroups' => 'servicegroup'
    );

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'flappinghistory' => array(
            'id'            => 'hfh.flappinghistory_id',
            'host'          => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'     => 'ho.name1',
            'object_id'     => 'hfh.object_id',
            'object_type'   => '(\'host\')',
            'output'        => '(hfh.percent_state_change || \'\')',
            'state'         => '(-1)',
            'timestamp'     => 'UNIX_TIMESTAMP(hfh.event_time)',
            'type'          => '(\'flapping\')'
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
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'servicegroups' => array(
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
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
        if ($col === 'UNIX_TIMESTAMP(hfh.event_time)') {
            return 'hfh.event_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
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
            array('hfh' => $this->prefix . 'flappinghistory'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hfh.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );

        $this->select->where('hfh.event_type = 1000');

        $this->joinedVirtualTables['flappinghistory'] = true;
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
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            array('i' => $this->prefix . 'instances'),
            'i.instance_id = hfh.instance_id',
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

    protected function joinSubQuery(IdoQuery $query, $name, $filter, $and, $negate, &$additionalFilter)
    {
        if ($name === 'hostgroup') {
            $query->joinVirtualTable('members');

            return ['hgm.host_object_id', 'ho.object_id'];
        } elseif ($name === 'servicegroup') {
            $query->joinVirtualTable('services');

            return ['s.host_object_id', 'ho.object_id'];
        }

        return parent::joinSubQuery($query, $name, $filter, $and, $negate, $additionalFilter);
    }
}
