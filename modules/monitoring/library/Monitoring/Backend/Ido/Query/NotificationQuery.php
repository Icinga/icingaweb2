<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Notification query
 */
class NotificationQuery extends IdoQuery
{
    /**
     * Column map
     *
     * @var array
     */
    protected $columnMap = array(
        'notification' => array(
            'notification_output'       => 'n.output',
            'notification_start_time'   => 'UNIX_TIMESTAMP(n.start_time)',
            'notification_state'        => 'n.state'
        ),
        'objects' => array(
            'host'                      => 'o.name1',
            'service'                   => 'o.name2'
        ),
        'contact' => array(
            'notification_contact'      => 'c_o.name1'
        ),
        'command' => array(
            'notification_command'      => 'cmd_o.name1'
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
}
