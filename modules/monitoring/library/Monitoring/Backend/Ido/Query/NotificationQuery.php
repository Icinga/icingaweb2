<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class NotificationQuery extends IdoQuery
{
    protected $columnMap = array(
        'notification' => array(
            'notification_output'           => 'n.output',
            'notification_start_time'       => 'UNIX_TIMESTAMP(n.start_time)',
            'notification_state'            => 'n.state',
            'notification_object_id'        => 'n.object_id'
        ),
        'objects' => array(
            'host'                          => 'o.name1 COLLATE latin1_general_ci',
            'host_name'                     => 'o.name1',
            'service'                       => 'o.name2 COLLATE latin1_general_ci',
            'service_description'           => 'o.name2'
        ),
        'contact' => array(
            'contact'                       => 'c_o.name1 COLLATE latin1_general_ci',
            'notification_contact_name'     => 'c_o.name1',
            'contact_object_id'             => 'c_o.object_id'
        ),
        'command' => array(
            'notification_command'          => 'cmd_o.name1'
        ),
        'acknowledgement' => array(
            'acknowledgement_entry_time'    => 'UNIX_TIMESTAMP(a.entry_time)',
            'acknowledgement_author_name'   => 'a.author_name',
            'acknowledgement_comment_data'  => 'a.comment_data'
        ),
        'hosts' => array(
            'host_display_name'             => 'CASE WHEN sh.display_name IS NOT NULL THEN sh.display_name ELSE h.display_name END'
        ),
        'services' => array(
            'service_display_name'          => 's.display_name'
        )
    );

    /**
     * Fetch basic information about notifications
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('n' => $this->prefix . 'notifications'),
            array()
        );
        $this->joinedVirtualTables = array('notification' => true);
    }

    /**
     * Fetch description of each affected host/service
     */
    protected function joinObjects()
    {
        $this->select->join(
            array('o' => $this->prefix . 'objects'),
            'n.object_id = o.object_id AND o.is_active = 1 AND o.objecttype_id IN (1, 2)',
            array()
        );
    }

    /**
     * Fetch name of involved contacts and/or contact groups
     */
    protected function joinContact()
    {
        $this->select->join(
            array('c' => $this->prefix . 'contactnotifications'),
            'n.notification_id = c.notification_id',
            array()
        );
        $this->select->join(
            array('c_o' => $this->prefix . 'objects'),
            'c.contact_object_id = c_o.object_id',
            array()
        );
    }

    /**
     * Fetch name of the command which was used to send out a notification
     */
    protected function joinCommand()
    {
        $this->select->join(
            array('cmd_c' => $this->prefix . 'contactnotifications'),
            'n.notification_id = cmd_c.notification_id',
            array()
        );
        $this->select->joinLeft(
            array('cmd_m' => $this->prefix . 'contactnotificationmethods'),
            'cmd_c.contactnotification_id = cmd_m.contactnotification_id',
            array()
        );
        $this->select->joinLeft(
            array('cmd_o' => $this->prefix . 'objects'),
            'cmd_m.command_object_id = cmd_o.object_id',
            array()
        );
    }

    protected function joinAcknowledgement()
    {
        $this->select->joinLeft(
            array('a' => $this->prefix . 'acknowledgements'),
            'n.object_id = a.object_id',
            array()
        );
    }

    protected function joinHosts()
    {
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = o.object_id',
            array()
        );
        return $this;
    }

    protected function joinServices()
    {
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = o.object_id',
            array()
        );
        $this->select->joinLeft(
            array('sh' => $this->prefix . 'hosts'),
            'sh.host_object_id = s.host_object_id',
            array()
        );
        return $this;
    }
}
