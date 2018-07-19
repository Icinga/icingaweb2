<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service contacts
 */
class ServicecontactQuery extends IdoQuery
{
    protected $allowCustomVars = true;

    protected $groupBase = [
        'contacts' => ['co.object_id', 'c.contact_id'],
        'timeperiods' => ['ht.timeperiod_id', 'st.timeperiod_id']
    ];

    protected $groupOrigin = ['contactgroups', 'hosts', 'services'];

    protected $subQueryTargets = [
        'hostgroups'    => 'hostgroup',
        'servicegroups' => 'servicegroup'
    ];

    protected $columnMap = [
        'contactgroups' => [
            'contactgroup'       => 'cgo.name1 COLLATE latin1_general_ci',
            'contactgroup_name'  => 'cgo.name1',
            'contactgroup_alias' => 'cg.alias COLLATE latin1_general_ci'
        ],
        'contacts' => [
            'contact_id'                       => 'c.contact_id',
            'contact'                          => 'co.name1 COLLATE latin1_general_ci',
            'contact_name'                     => 'co.name1',
            'contact_alias'                    => 'c.alias COLLATE latin1_general_ci',
            'contact_email'                    => 'c.email_address COLLATE latin1_general_ci',
            'contact_pager'                    => 'c.pager_address',
            'contact_object_id'                => 'c.contact_object_id',
            'contact_has_host_notfications'    => 'c.host_notifications_enabled',
            'contact_has_service_notfications' => 'c.service_notifications_enabled',
            'contact_can_submit_commands'      => 'c.can_submit_commands',
            'contact_notify_service_recovery'  => 'c.notify_service_recovery',
            'contact_notify_service_warning'   => 'c.notify_service_warning',
            'contact_notify_service_critical'  => 'c.notify_service_critical',
            'contact_notify_service_unknown'   => 'c.notify_service_unknown',
            'contact_notify_service_flapping'  => 'c.notify_service_flapping',
            'contact_notify_service_downtime'  => 'c.notify_service_downtime',
            'contact_notify_host_recovery'     => 'c.notify_host_recovery',
            'contact_notify_host_down'         => 'c.notify_host_down',
            'contact_notify_host_unreachable'  => 'c.notify_host_unreachable',
            'contact_notify_host_flapping'     => 'c.notify_host_flapping',
            'contact_notify_host_downtime'     => 'c.notify_host_downtime'
        ],
        'hostgroups' => [
            'hostgroup'       => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias' => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'  => 'hgo.name1'
        ],
        'hosts' => [
            'host'              => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'         => 'ho.name1',
            'host_alias'        => 'h.alias',
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci'
        ],
        'instances' => [
            'instance_name' => 'i.instance_name'
        ],
        'servicegroups' => [
            'servicegroup'       => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'  => 'sgo.name1',
            'servicegroup_alias' => 'sg.alias COLLATE latin1_general_ci'
        ],
        'services' => [
            'service'              => 'so.name2 COLLATE latin1_general_ci',
            'service_description'  => 'so.name2',
            'service_display_name' => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'    => 'so.name1'
        ],
        'timeperiods' => [
            'contact_notify_host_timeperiod'    => 'ht.alias COLLATE latin1_general_ci',
            'contact_notify_service_timeperiod' => 'st.alias COLLATE latin1_general_ci'
        ]
    ];

    protected function joinBaseTables()
    {
        $this->select->from(
            ['c' => $this->prefix . 'contacts'],
            []
        )->join(
            ['co' => $this->prefix . 'objects'],
            'co.object_id = c.contact_object_id AND co.is_active = 1',
            []
        );

        $this->select->joinLeft(
            ['sc' => $this->prefix . 'service_contacts'],
            'sc.contact_object_id = c.contact_object_id',
            []
        )->joinLeft(
            ['s' => $this->prefix . 'services'],
            's.service_id = sc.service_id',
            []
        )->joinLeft(
            ['so' => $this->prefix . 'objects'],
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            []
        );

        $this->joinedVirtualTables['contacts'] = true;
        $this->joinedVirtualTables['services'] = true;
    }

    /**
     * Join contact groups
     */
    protected function joinContactgroups()
    {
        $this->select->joinLeft(
            ['cgm' => $this->prefix . 'contactgroup_members'],
            'co.object_id = cgm.contact_object_id',
            []
        )->joinLeft(
            ['cg' => $this->prefix . 'contactgroups'],
            'cgm.contactgroup_id = cg.contactgroup_id',
            []
        )->joinLeft(
            ['cgo' => $this->prefix . 'objects'],
            'cg.contactgroup_object_id = cgo.object_id AND cgo.is_active = 1 AND cgo.objecttype_id = 11',
            []
        );
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('hosts');
        $this->select->joinLeft(
            ['hgm' => $this->prefix . 'hostgroup_members'],
            'hgm.host_object_id = ho.object_id',
            []
        )->joinLeft(
            ['hg' => $this->prefix . 'hostgroups'],
            'hg.hostgroup_id = hgm.hostgroup_id',
            []
        )->joinLeft(
            ['hgo' => $this->prefix . 'objects'],
            'hgo.object_id = hg.hostgroup_object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            []
        );
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->select->joinLeft(
            ['h' => $this->prefix . 'hosts'],
            'h.host_object_id = s.host_object_id',
            []
        )->joinLeft(
            ['ho' => $this->prefix . 'objects'],
            'ho.object_id = h.host_object_id AND ho.is_active = 1',
            []
        );
    }

    /**
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            ['i' => $this->prefix . 'instances'],
            'i.instance_id = c.instance_id',
            []
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            ['sgm' => $this->prefix . 'servicegroup_members'],
            'sgm.service_object_id = s.service_object_id',
            []
        )->joinLeft(
            ['sg' => $this->prefix . 'servicegroups'],
            'sg.servicegroup_id = sgm.servicegroup_id',
            []
        )->joinLeft(
            ['sgo' => $this->prefix . 'objects'],
            'sgo.object_id = sg.servicegroup_object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            []
        );
    }

    /**
     * Join time periods
     */
    protected function joinTimeperiods()
    {
        $this->select->joinLeft(
            ['ht' => $this->prefix . 'timeperiods'],
            'ht.timeperiod_object_id = c.host_timeperiod_object_id',
            []
        );
        $this->select->joinLeft(
            ['st' => $this->prefix . 'timeperiods'],
            'st.timeperiod_object_id = c.service_timeperiod_object_id',
            []
        );
    }

    protected function joinSubQuery(IdoQuery $query, $name, $filter, $and, $negate, &$additionalFilter)
    {
        if ($name === 'hostgroup') {
            $query->joinVirtualTable('members');

            return ['hgm.host_object_id', 's.host_object_id'];
        } elseif ($name === 'servicegroup') {
            $query->joinVirtualTable('members');

            return ['sgm.service_object_id', 'so.object_id'];
        }

        return parent::joinSubQuery($query, $name, $filter, $and, $negate, $additionalFilter);
    }
}
