<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ContactQuery extends IdoQuery
{
    protected $columnMap = array(
        'contacts' => array(
            'contact_id'                        => 'c.contact_id',
            'contact'                           => 'co.name1 COLLATE latin1_general_ci',
            'contact_name'                      => 'co.name1',
            'contact_alias'                     => 'c.alias COLLATE latin1_general_ci',
            'contact_email'                     => 'c.email_address COLLATE latin1_general_ci',
            'contact_pager'                     => 'c.pager_address',
            'contact_object_id'                 => 'c.contact_object_id',
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
            'contact_notify_host_downtime'      => 'c.notify_host_downtime'
        ),
        'timeperiods' => array(
            'contact_notify_host_timeperiod'    => 'ht.alias COLLATE latin1_general_ci',
            'contact_notify_service_timeperiod' => 'st.alias COLLATE latin1_general_ci'
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

    protected function joinBaseTables()
    {
        $this->select->from(
            array('c' => $this->prefix . 'contacts'),
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'c.contact_object_id = co.' . $this->object_id . ' AND co.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('contacts' => true);
    }

    protected function joinHosts()
    {
        $this->select->join(
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
        $this->select->join(
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

    protected function joinTimeperiods()
    {
        $this->select->joinLeft(
            array('ht' => $this->prefix . 'timeperiods'),
            'ht.timeperiod_object_id = c.host_timeperiod_object_id',
            array()
        );
        $this->select->joinLeft(
            array('st' => $this->prefix . 'timeperiods'),
            'st.timeperiod_object_id = c.service_timeperiod_object_id',
            array()
        );
    }
}
