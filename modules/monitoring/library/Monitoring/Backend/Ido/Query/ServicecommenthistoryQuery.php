<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service comment history records
 */
class ServicecommenthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('commenthistory' => array('sch.commenthistory_id', 'so.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('hostgroups', 'servicegroups', 'services');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'commenthistory' => array(
            'host'                  => 'so.name1 COLLATE latin1_general_ci',
            'host_name'             => 'so.name1',
            'object_id'             => 'sch.object_id',
            'object_type'           => '(\'service\')',
            'output'                => "('[' || sch.author_name || '] ' || sch.comment_data)",
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_host'          => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1',
            'state'                 => '(-1)',
            'timestamp'             => 'UNIX_TIMESTAMP(sch.comment_time)',
            'type'                  => "(CASE sch.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'dt_comment' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END)"
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
        if ($col === 'UNIX_TIMESTAMP(sch.comment_time)') {
            return 'sch.comment_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
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
            array('sch' => $this->prefix . 'commenthistory'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sch.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->joinedVirtualTables['commenthistory'] = true;
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
            'i.instance_id = sch.instance_id',
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
