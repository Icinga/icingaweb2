<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ServicegroupQuery extends IdoQuery
{
    protected $groupBase = array(
        'servicegroups' => array('sgo.object_id'),
        'servicestatus' => array('ss.servicestatus_id', 'hs.hoststatus_id')
    );

    protected $groupOrigin = array('members');

    protected $allowCustomVars = true;

    protected $subQueryTargets = array(
        'hostgroups'    => 'hostgroup',
        'servicegroups' => 'servicegroup'
    );

    protected $columnMap = array(
        'contacts' => [
            'service_contact' => 'sco.name1'
        ],
        'contactgroups' => [
            'service_contactgroup' => 'scgo.name1'
        ],
        'hostcontacts' => [
            'host_contact' => 'hco.name1'
        ],
        'hostcontactgroups' => [
            'host_contactgroup' => 'hcgo.name1'
        ],
        'hostgroups' => array(
            'hostgroup_name' => 'hgo.name1'
        ),
        'hosts' => array(
            'h.host_object_id' => 's.host_object_id'
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'members' => array(
            'host_name'             => 'so.name1',
            'service_description'   => 'so.name2'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1'
        ),
        'servicestatus' => array(
            'service_handled'   => 'CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1 ELSE 0 END',
            'service_severity'  => '
                CASE WHEN ss.current_state = 0
                THEN
                    CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL
                         THEN 16
                         ELSE 0
                    END
                    +
                    CASE WHEN ss.problem_has_been_acknowledged = 1
                         THEN 2
                         ELSE
                            CASE WHEN ss.scheduled_downtime_depth > 0
                                THEN 1
                                ELSE 4
                            END
                    END
                ELSE
                    CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 16
                         WHEN ss.current_state = 1 THEN 32
                         WHEN ss.current_state = 2 THEN 128
                         WHEN ss.current_state = 3 THEN 64
                         ELSE 256
                    END
                    +
                    CASE WHEN hs.current_state > 0
                         THEN 1024
                         ELSE
                             CASE WHEN ss.problem_has_been_acknowledged = 1
                                  THEN 512
                                  ELSE
                                     CASE WHEN ss.scheduled_downtime_depth > 0
                                         THEN 256
                                         ELSE 2048
                                     END
                             END
                         END
                END',
            'service_state'     => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('sgo' => $this->prefix . 'objects'),
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.servicegroup_object_id = sgo.object_id AND sgo.objecttype_id = 4 AND sgo.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('servicegroups' => true);
    }

    /**
     * Join contacts
     */
    protected function joinContacts()
    {
        $this->requireVirtualTable('services');

        $this->select->joinLeft(
            ['sc' => 'icinga_service_contacts'],
            'sc.service_id = s.service_id',
            []
        )->joinLeft(
            ['sco' => 'icinga_objects'],
            'sco.object_id = sc.contact_object_id AND sco.is_active = 1 AND sco.objecttype_id = 10',
            []
        );
    }

    /**
     * Join contact groups
     */
    protected function joinContactgroups()
    {
        $this->requireVirtualTable('services');

        $this->select->joinLeft(
            ['scg' => 'icinga_service_contactgroups'],
            'scg.service_id = s.service_id',
            []
        )->joinLeft(
            ['scgo' => 'icinga_objects'],
            'scgo.object_id = scg.contactgroup_object_id AND scgo.is_active = 1 AND scgo.objecttype_id = 10',
            []
        );
    }

    /**
     * Join host contacts
     */
    protected function joinHostcontacts()
    {
        $this->requireVirtualTable('services');

        $this->select->joinLeft(
            ['h' => 'icinga_hosts'],
            'h.host_object_id = s.host_object_id',
            []
        )->joinLeft(
            ['hc' => 'icinga_host_contacts'],
            'hc.host_id = h.host_id',
            []
        )->joinLeft(
            ['hco' => 'icinga_objects'],
            'hco.object_id = hc.contact_object_id AND hco.is_active = 1 AND hco.objecttype_id = 10',
            []
        );
    }

    /**
     * Join host contact groups
     */
    protected function joinHostcontactgroups()
    {
        $this->requireVirtualTable('services');

        $this->select->joinLeft(
            ['h' => 'icinga_hosts'],
            'h.host_object_id = s.host_object_id',
            []
        )->joinLeft(
            ['hcg' => 'icinga_host_contactgroups'],
            'hcg.host_id = h.host_id',
            []
        )->joinLeft(
            ['hcgo' => 'icinga_objects'],
            'hcgo.object_id = hcg.contactgroup_object_id AND hcgo.is_active = 1 AND hcgo.objecttype_id = 11',
            []
        );
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
            'hgo.object_id = hg.hostgroup_object_id AND hgo.objecttype_id = 3 AND hgo.is_active = 1',
            array()
        );
    }

    /**
     * Join hosts
     *
     * This is required to make filters work which filter by host custom variables.
     */
    protected function joinHosts()
    {
        $this->requireVirtualTable('services');

        // Host custom var filters work w/o any host related table. If a host table join is necessary here some day,
        // please adjust `joinHostcontact*()` where we explicitly do this already
    }

    /**
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            array('i' => $this->prefix . 'instances'),
            'i.instance_id = sg.instance_id',
            array()
        );
    }

    /**
     * Join service objects
     */
    protected function joinMembers()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.servicegroup_id = sg.servicegroup_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sgm.service_object_id AND so.objecttype_id = 2 AND so.is_active = 1',
            array()
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('members');
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }

    /**
     * Join service status
     */
    protected function joinServicestatus()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = s.host_object_id',
            array()
        );
        $this->select->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id',
            array()
        );
    }

    protected function joinSubQuery(IdoQuery $query, $name, $filter, $and, $negate, &$additionalFilter)
    {
        if ($name === 'hostgroup') {
            $this->requireVirtualTable('members');

            $query->joinVirtualTable('services');

            return ['so.object_id', 'so.object_id'];
        } elseif ($name === 'servicegroup') {
            // Propagate that the "parent" query has to be filtered as well
            $additionalFilter = clone $filter;

            $this->requireVirtualTable('members');

            $query->joinVirtualTable('members');

            return ['sgm.service_object_id', 'so.object_id'];
        }

        return parent::joinSubQuery($query, $name, $filter, $and, $negate, $additionalFilter);
    }
}
