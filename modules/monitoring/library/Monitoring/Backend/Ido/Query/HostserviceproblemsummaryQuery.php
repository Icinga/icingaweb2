<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class HostserviceproblemsummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * The HoststatusQuery in use
     *
     * @var HoststatusQuery
     */
    protected $hostStatusQuery;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'services' => array(
            'host_name'                 => 'so.name1',
            'service_description'       => 'so.name2'
        ),
        'hostgroups' => array(
            'hostgroup_name'            => 'hgo.name1'
        ),
        'servicegroups' => array(
            'servicegroup_name'         => 'sgo.name1'
        ),
        'problemsummary' => array(
            'unhandled_service_count'   => 'SUM(
                CASE
                    WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0
                    THEN 0
                    ELSE 1
                END
            )'
        )
    );

    /**
     * Set the HoststatusQuery to use
     *
     * @param   HoststatusQuery     $query
     *
     * @return  $this
     */
    public function setHoststatusQuery(HoststatusQuery $query)
    {
        $this->hostStatusQuery = clone $query;
        $this->hostStatusQuery
            ->clearOrder()
            ->setIsSubQuery()
            ->columns(array('object_id'));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('so' => $this->prefix . 'objects'),
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id AND so.is_active = 1',
            array()
        );
        $this->select->group('so.name1');
        $this->joinedVirtualTables['services'] = true;
    }

    /**
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            array('i' => $this->prefix . 'instances'),
            'i.instance_id = so.instance_id',
            array()
        );
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
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
            'sgm.servicegroup_id = sg.servicegroup_id',
            array()
        )->joinLeft(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            array()
        );
    }

    /**
     * Join the statussummary
     */
    protected function joinProblemsummary()
    {
        $this->select->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id AND ss.current_state > 0',
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = s.host_object_id',
            array()
        )->join(
            array('h' => $this->hostStatusQuery),
            'h.object_id = s.host_object_id',
            array()
        );

        $this->select->having($this->getMappedField('unhandled_service_count') . ' > 0');
    }
}
