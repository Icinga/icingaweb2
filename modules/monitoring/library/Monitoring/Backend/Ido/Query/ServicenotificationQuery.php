<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
        'contactnotifications' => array(
            'notification_contact_name' => 'co.name1'
        ),
        'history' => array(
            'output'    => null,
            'state'     => 'sn.state',
            'timestamp' => 'UNIX_TIMESTAMP(sn.start_time)',
            'type'      => '(\'notify\')'
        ),
        'hostgroups' => array(
            'hostgroup_name' => 'hgo.name1'
        ),
        'hosts' => array(
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci'
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'notifications' => array(
            'host_name'                 => 'so.name1',
            'notification_output'       => 'sn.output',
            'notification_state'        => 'sn.state',
            'notification_timestamp'    => 'UNIX_TIMESTAMP(sn.start_time)',
            'object_type'               => '(\'service\')',
            'service_description'       => 'so.name2',
            'service_host_name'         => 'so.name1'
        ),
        'servicegroups' => array(
            'servicegroup_name' => 'sgo.name1'
        ),
        'services' => array(
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci'
        )
    );

    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(sn.start_time)') {
            return 'sn.start_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        switch ($this->ds->getDbType()) {
            case 'mysql':
                $concattedContacts = "GROUP_CONCAT("
                    . "DISTINCT co.name1 ORDER BY co.name1 SEPARATOR ', '"
                    . ") COLLATE latin1_general_ci";
                break;
            case 'pgsql':
                // TODO: Find a way to order the contact alias list:
                $concattedContacts = "ARRAY_TO_STRING(ARRAY_AGG(DISTINCT co.name1), ', ')";
                break;
        }
        $this->columnMap['history']['output'] = "('[' || $concattedContacts || '] ' || sn.output)";

        $this->select->from(
            array('sn' => $this->prefix . 'notifications'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sn.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->joinedVirtualTables['notifications'] = true;
    }

    /**
     * Join virtual table history
     */
    protected function joinHistory()
    {
        $this->requireVirtualTable('contactnotifications');
    }

    /**
     * Join contact notifications
     */
    protected function joinContactnotifications()
    {
        $this->select->joinLeft(
            array('cn' => $this->prefix . 'contactnotifications'),
            'cn.notification_id = sn.notification_id',
            array()
        );
        $this->select->joinLeft(
            array('co' => $this->prefix . 'objects'),
            'co.object_id = cn.contact_object_id AND co.is_active = 1 AND co.objecttype_id = 10',
            array()
        );
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
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
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = so.object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.' . $this->servicegroup_id . ' = sgm.servicegroup_id',
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
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
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
            'i.instance_id = sn.instance_id',
            array()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $group = array();

        if (
            $this->hasJoinedVirtualTable('history')
            || $this->hasJoinedVirtualTable('hostgroups')
            || $this->hasJoinedVirtualTable('servicegroups')
        ) {
            $group = array('sn.notification_id', 'so.object_id');
            if ($this->hasJoinedVirtualTable('contactnotifications') && !$this->hasJoinedVirtualTable('history')) {
                $group[] = 'co.object_id';
            }
        } elseif ($this->hasJoinedVirtualTable('contactnotifications')) {
            $group = array('sn.notification_id', 'co.object_id', 'so.object_id');
        }

        if (! empty($group)) {
            if ($this->hasJoinedVirtualTable('hosts')) {
                $group[] = 'h.host_id';
            }

            if ($this->hasJoinedVirtualTable('services')) {
                $group[] = 's.service_id';
            }

            if ($this->hasJoinedVirtualTable('instances')) {
                $group[] = 'i.instance_id';
            }
        }

        return $group;
    }
}
