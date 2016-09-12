<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for contacts
 */
class ContactQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('contacts' => array('co.object_id', 'c.contact_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('contactgroups', 'hosts', 'services');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'contactgroups' => array(
            'contactgroup'          => 'cgo.name1 COLLATE latin1_general_ci',
            'contactgroup_name'     => 'cgo.name1',
            'contactgroup_alias'    => 'cg.alias COLLATE latin1_general_ci'
        ),
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
            'contact_notify_service_downtime'   => 'c.notify_service_downtime',
            'contact_notify_host_recovery'      => 'c.notify_host_recovery',
            'contact_notify_host_down'          => 'c.notify_host_down',
            'contact_notify_host_unreachable'   => 'c.notify_host_unreachable',
            'contact_notify_host_flapping'      => 'c.notify_host_flapping',
            'contact_notify_host_downtime'      => 'c.notify_host_downtime'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host'              => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'         => 'ho.name1',
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
        ),
        'timeperiods' => array(
            'contact_notify_host_timeperiod'    => 'ht.alias COLLATE latin1_general_ci',
            'contact_notify_service_timeperiod' => 'st.alias COLLATE latin1_general_ci'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('c' => $this->prefix . 'contacts'),
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'co.object_id = c.contact_object_id AND co.is_active = 1',
            array()
        );
        $this->joinedVirtualTables['contacts'] = true;
    }

    /**
     * Join contact groups
     */
    protected function joinContactgroups()
    {
        $this->select->joinLeft(
            array('cgm' => $this->prefix . 'contactgroup_members'),
            'co.object_id = cgm.contact_object_id',
            array()
        )->joinLeft(
            array('cg' => $this->prefix . 'contactgroups'),
            'cgm.contactgroup_id = cg.contactgroup_id',
            array()
        )->joinLeft(
            array('cgo' => $this->prefix . 'objects'),
            'cg.contactgroup_object_id = cgo.object_id AND cgo.is_active = 1 AND cgo.objecttype_id = 11',
            array()
        );
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('hosts');
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
        $this->select->joinLeft(
            array('hc' => $this->prefix . 'host_contacts'),
            'hc.contact_object_id = c.contact_object_id',
            array()
        )->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_id = hc.host_id',
            array()
        )->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = h.host_object_id AND ho.is_active = 1',
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
            'i.instance_id = c.instance_id',
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
            'sg.servicegroup_id = sgm.servicegroup_id',
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
            array('sc' => $this->prefix . 'service_contacts'),
            'sc.contact_object_id = c.contact_object_id',
            array()
        )->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_id = sc.service_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }

    /**
     * Join time periods
     */
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
