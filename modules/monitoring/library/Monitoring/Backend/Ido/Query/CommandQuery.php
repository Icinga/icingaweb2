<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for commands
 */
class CommandQuery extends IdoQuery
{
    /**
     * @var array
     */
    protected $columnMap = array(
        'commands'  => array(
            'command_id'            => 'c.command_id',
            'command_instance_id'   => 'c.instance_id',
            'command_config_type'   => 'c.config_type',
            'command_line'          => 'c.command_line',
            'command_name'          => 'co.name1'
        ),

        'contacts'  => array(
            'contact_id'    => 'con.contact_id',
            'contact_alias' => 'con.contact_alias'
        )
    );

    /**
     * Fetch basic information about commands
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('c' => $this->prefix . 'commands'),
            array()
        )->join(
            array('co' => $this->prefix . 'objects'),
            'co.object_id = c.object_id',
            array()
        );

        $this->joinedVirtualTables = array('commands' => true);
    }

    /**
     * Join contacts
     */
    protected function joinContacts()
    {
        $this->select->join(
            array('cnc' => $this->prefix . 'contact_notificationcommands'),
            'cnc.command_object_id = co.object_id',
            array()
        )->join(
            array('con' => $this->prefix . 'contacts'),
            'con.contact_id = cnc.contact_id',
            array()
        );
    }
}
