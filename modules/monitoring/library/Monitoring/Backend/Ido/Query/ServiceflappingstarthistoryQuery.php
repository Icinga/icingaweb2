<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service flapping start history records
 */
class ServiceflappingstarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('flappinghistory' => array('sfh.flappinghistory_id', 'so.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('hostgroups', 'servicegroups');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'flappinghistory' => array(
            'host_name'             => 'so.name1',
            'object_id'             => 'sfh.object_id',
            'object_type'           => '(\'service\')',
            'output'                => '(sfh.percent_state_change || \'\')',
            'service_description'   => 'so.name2',
            'service_host_name'     => 'so.name1',
            'state'                 => '(-1)',
            'timestamp'             => 'UNIX_TIMESTAMP(sfh.event_time)',
            'type'                  => "('flapping')"
        ),
        'hostgroups' => array(
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
        if ($col === 'UNIX_TIMESTAMP(sfh.event_time)') {
            return 'sfh.event_time ' . $sign . ' ' . $this->timestampForSql(
                $this->valueToTimestamp($expression)
            );
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
            array('sfh' => $this->prefix . 'flappinghistory'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sfh.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );

        $this->select->where('sfh.event_type = 1000');

        $this->joinedVirtualTables['flappinghistory'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
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
        $this->requireVirtualTable('services');
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
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
            'i.instance_id = sfh.instance_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = so.object_id',
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
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }
}
