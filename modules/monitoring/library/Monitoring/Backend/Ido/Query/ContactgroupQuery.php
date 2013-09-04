<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ContactgroupQuery extends AbstractQuery
{
    protected $columnMap = array(
        'contactgroups' => array(
            'contactgroup_name'   => 'cgo.name1 COLLATE latin1_general_ci',
            'contactgroup_alias'  => 'cg.alias',
        ),
        'contacts' => array(
            'contact_name'        => 'co.name1 COLLATE latin1_general_ci',
        ),
        'hosts' => array(
            'host_name' => 'ho.name1',
        ),
        'services' => array(
            'service_host_name'           => 'so.name1 COLLATE latin1_general_ci',
            'service_description' => 'so.name2 COLLATE latin1_general_ci',
        )
    );

    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('cg' => $this->prefix . 'contactgroups'),
            array()
        )->join(
            array('cgo' => $this->prefix . 'objects'),
            'cg.contactgroup_object_id = cgo.' . $this->object_id
          . ' AND cgo.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array('contactgroups' => true);
    }

    protected function joinContacts()
    {
        $this->baseQuery->join(
            array('cgm' => $this->prefix . 'contactgroup_members'),
            'cgm.contactgroup_id = cg.contactgroup_id',
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'cgm.contact_object_id = co.object_id AND co.is_active = 1',
            array()
        );
    }

    protected function joinHosts()
    {
        $this->baseQuery->join(
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
        $scgSub = $this->db->select()->distinct()
            ->from($this->prefix . 'service_contactgroups', array(
            'contactgroup_object_id', 'service_id'
            ));

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

        $this->baseQuery->join(
            // array('scg' => $this->prefix . 'service_contactgroups'),
            array('scg' => $scgSub),
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
