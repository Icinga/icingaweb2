<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;

/**
 * Query for host downtime start history records
 */
class HostdowntimestarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('downtimehistory' => array('hdh.downtimehistory_id', 'ho.object_id'));

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
        'downtimehistory' => array(
            'id'            => 'hdh.downtimehistory_id',
            'host'          => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'     => 'ho.name1',
            'object_id'     => 'hdh.object_id',
            'object_type'   => '(\'host\')',
            'output'        => "('[' || hdh.author_name || '] ' || hdh.comment_data)",
            'state'         => '(-1)',
            'timestamp'     => 'UNIX_TIMESTAMP(hdh.actual_start_time)',
            'type'          => "('dt_start')"
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
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    protected function requireFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterExpression && $filter->getColumn() === 'timestamp') {
            $this->requireColumn('timestamp');
            $filter->setColumn('hdh.actual_start_time');
            $filter->setExpression($this->timestampForSql($this->valueToTimestamp($filter->getExpression())));
            return null;
        }

        return parent::requireFilterColumns($filter);
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('hdh' => $this->prefix . 'downtimehistory'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hdh.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );

        if (func_num_args() === 0 || func_get_arg(0) === false) {
            $this->select->where(
                "hdh.actual_start_time > '1970-01-02 00:00:00'"
            );
        }

        $this->joinedVirtualTables['downtimehistory'] = true;
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
            'i.instance_id = hdh.instance_id',
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
