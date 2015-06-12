<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service downtime start history records
 */
class ServicedowntimestarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'downtimehistory' => array(
            'host'                  => 'so.name1 COLLATE latin1_general_ci',
            'host_name'             => 'so.name1',
            'object_type'           => '(\'service\')',
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_host'          => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        ),
        'history' => array(
            'type'      => "('dt_start')",
            'timestamp' => 'UNIX_TIMESTAMP(sdh.actual_start_time)',
            'object_id' => 'sdh.object_id',
            'state'     => '(NULL)',
            'output'    => "('[' || sdh.author_name || '] ' || sdh.comment_data)",
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
        if ($col === 'UNIX_TIMESTAMP(sdh.actual_start_time)') {
            return 'sdh.actual_start_time ' . $sign . ' ' . $this->timestampForSql(
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
            array('sdh' => $this->prefix . 'downtimehistory'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sdh.object_id',
            array()
        )->where(
            'so.is_active = ?',
            1
        )->where(
            'so.objecttype_id = ?',
            2
        );

        if (@func_get_arg(0) === false) {
            $this->select->where(
                "sdh.actual_start_time > '1970-01-02 00:00:00'"
            );
        }

        $this->joinedVirtualTables['downtimehistory'] = true;
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
        $this->select->group(array('sdh.downtimehistory_id', 'so.name1', 'so.name2'));
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
        $this->select->group(array('sdh.downtimehistory_id', 'so.name1', 'so.name2'));
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
