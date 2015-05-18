<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ContactgroupQuery extends IdoQuery
{
    protected $columnMap = array(
        'contactgroups' => array(
            'contactgroup'          => 'cgo.name1 COLLATE latin1_general_ci',
            'contactgroup_name'     => 'cgo.name1',
            'contactgroup_alias'    => 'cg.alias COLLATE latin1_general_ci'
        ),
        'contacts' => array(
            'contact'                           => 'co.name1 COLLATE latin1_general_ci',
            'contact_name'                      => 'co.name1',
            'contact_alias'                     => 'c.alias COLLATE latin1_general_ci',
            'contact_email'                     => 'c.email_address COLLATE latin1_general_ci',
            'contact_pager'                     => 'c.pager_address',
            'contact_has_host_notfications'     => 'c.host_notifications_enabled',
            'contact_has_service_notfications'  => 'c.service_notifications_enabled',
            'contact_can_submit_commands'       => 'c.can_submit_commands',
            'contact_notify_service_recovery'   => 'c.notify_service_recovery',
            'contact_notify_service_warning'    => 'c.notify_service_warning',
            'contact_notify_service_critical'   => 'c.notify_service_critical',
            'contact_notify_service_unknown'    => 'c.notify_service_unknown',
            'contact_notify_service_flapping'   => 'c.notify_service_flapping',
            'contact_notify_service_downtime'   => 'c.notify_service_recovery',
            'contact_notify_host_recovery'      => 'c.notify_host_recovery',
            'contact_notify_host_down'          => 'c.notify_host_down',
            'contact_notify_host_unreachable'   => 'c.notify_host_unreachable',
            'contact_notify_host_flapping'      => 'c.notify_host_flapping',
            'contact_notify_host_downtime'      => 'c.notify_host_downtime',
        ),
        'hosts' => array(
            'host'      => 'ho.name1 COLLATE latin1_general_ci',
            'host_name' => 'ho.name1'
        ),
        'services' => array(
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_host'          => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    protected $useSubqueryCount = true;

    protected function joinBaseTables()
    {
        $this->select->from(
            array('cg' => $this->prefix . 'contactgroups'),
            array()
        )->join(
            array('cgo' => $this->prefix . 'objects'),
            'cg.contactgroup_object_id = cgo.' . $this->object_id . ' AND cgo.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array('contactgroups' => true);
    }

    protected function joinContacts()
    {
        $this->select->distinct()->join(
            array('cgm' => $this->prefix . 'contactgroup_members'),
            'cgm.contactgroup_id = cg.contactgroup_id',
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'cgm.contact_object_id = co.object_id AND co.is_active = 1',
            array()
        )->join(
            array('c' => $this->prefix . 'contacts'),
            'c.contact_object_id = co.object_id',
            array()
        );
    }

    protected function joinHosts()
    {
        $this->select->distinct()->join(
            array('hcg' => $this->prefix . 'host_contactgroups'),
            'hcg.contactgroup_object_id = cg.contactgroup_object_id',
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hcg.host_id = h.host_id',
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'h.host_object_id = ho.' . $this->object_id . ' AND ho.is_active = 1',
            array()
        );
    }

    protected function joinServices()
    {
//        $scgSub = $this->db->select()->distinct()->from(
//            $this->prefix . 'service_contactgroups',
//            array('contactgroup_object_id', 'service_id')
//        );

            /*
            This subselect is a workaround for a fucking stupid bug. Other tables
            may be affected too. We absolutely need uniqueness here.

            mysql> SELECT * FROM icinga_service_contactgroups WHERE
                   contactgroup_object_id = 143 AND service_id = 2079564;
            +-------------------------+-------------+------------+------------------------+
            | service_contactgroup_id | instance_id | service_id | contactgroup_object_id |
            +-------------------------+-------------+------------+------------------------+
            |                 4904240 |           1 |    2079564 |                    143 |
            |                 4904244 |           1 |    2079564 |                    143 |
            +-------------------------+-------------+------------+------------------------+
            */

        $this->select->distinct()->join(
            array('scg' => $this->prefix . 'service_contactgroups'),
            // array('scg' => $scgSub),
            'scg.contactgroup_object_id = cg.contactgroup_object_id',
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            'scg.service_id = s.service_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            's.service_object_id = so.' . $this->object_id . ' AND so.is_active = 1',
            array()
        );
    }
}
