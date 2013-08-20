<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ContactQuery extends AbstractQuery
{
    protected $columnMap = array(
        'contacts' => array(
            'contact_name'   => 'co.name1 COLLATE latin1_general_ci',
            'contact_alias'  => 'c.alias',
            'contact_email'  => 'c.email_address',
            'contact_pager'  => 'c.pager_address',
        ),
        'hosts' => array(
            'host_name' => 'ho.name1 COLLATE latin1_general_ci',
        ),
        'services' => array(
            'service_host_name'   => 'so.name1 COLLATE latin1_general_ci',
            'service_description' => 'so.name2 COLLATE latin1_general_ci',
        )
    );

    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('c' => $this->prefix . 'contacts'),
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'c.contact_object_id = co.' . $this->object_id
          . ' AND co.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array('contacts' => true);
    }

    protected function joinHosts()
    {
        $this->baseQuery->join(
            array('hc' => $this->prefix . 'host_contacts'),
            'hc.contact_object_id = c.contact_object_id',
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hc.host_id = h.host_id',
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'h.host_object_id = ho.' . $this->object_id . ' AND ho.is_active = 1',
            array()
        );
    }

    protected function joinServices()
    {
        $this->baseQuery->join(
            array('sc' => $this->prefix . 'service_contacts'),
            'sc.contact_object_id = c.contact_object_id',
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            'sc.service_id = s.service_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            's.service_object_id = so.' . $this->object_id . ' AND so.is_active = 1',
            array()
        );
    }

}
