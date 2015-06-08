<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service notifications
 */
class ServicenotificationQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'notifications' => array(
            'notification_output'       => 'sn.output',
            'notification_start_time'   => 'UNIX_TIMESTAMP(sn.start_time)',
            'notification_state'        => 'sn.state',
            'notification_object_id'    => 'sn.object_id',
            'host'                      => 'so.name1 COLLATE latin1_general_ci',
            'host_name'                 => 'so.name1',
            'object_type'               => '(\'service\')',
            'service'                   => 'so.name2 COLLATE latin1_general_ci',
            'service_description'       => 'so.name2',
            'service_host'              => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'         => 'so.name1'
        ),
        'contactnotifications' => array(
            'contact'                   => 'cno.name1 COLLATE latin1_general_ci',
            'notification_contact_name' => 'cno.name1',
            'contact_object_id'         => 'cno.object_id'
        ),
        'acknowledgements' => array(
            'acknowledgement_entry_time'    => 'UNIX_TIMESTAMP(a.entry_time)',
            'acknowledgement_author_name'   => 'a.author_name',
            'acknowledgement_comment_data'  => 'a.comment_data'
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
    protected function joinBaseTables()
    {
        $this->select->from(
            array('sn' => $this->prefix . 'notifications'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sn.object_id',
            array()
        )->where(
            'so.is_active = ?',
            1
        )->where(
            'so.objecttype_id = ?',
            2
        );
        $this->joinedVirtualTables['notifications'] = true;
    }

    /**
     * Join contact notifications
     */
    protected function joinContactnotifications()
    {
        $this->select->join(
            array('cn' => $this->prefix . 'contactnotifications'),
            'cn.notification_id = sn.notification_id',
            array()
        );
        $this->select->join(
            array('cno' => $this->prefix . 'objects'),
            'cno.object_id = cn.contact_object_id',
            array()
        );
    }

    /**
     * Join acknowledgements
     */
    protected function joinAcknowledgements()
    {
        $this->select->joinLeft(
            array('a' => $this->prefix . 'acknowledgements'),
            'a.object_id = sn.object_id',
            array()
        );
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
        $this->group(array('sn.notification_id', 'so.name2', 'so.name1'));
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
        $this->group(array('sn.notification_id', 'so.name2', 'so.name1'));
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
