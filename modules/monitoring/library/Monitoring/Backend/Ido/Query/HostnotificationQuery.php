<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;

/**
 * Query for host notifications
 */
class HostnotificationQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    protected $subQueryTargets = array(
        'hostgroups'    => 'hostgroup',
        'servicegroups' => 'servicegroup'
    );

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'contactnotifications' => array(
            'notification_contact_name' => 'co.name1'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci',
            'host_alias'        => 'h.alias COLLATE latin1_general_ci',
        ),
        'history' => array(
            'output'    => null,
            'state'     => 'hn.state',
            'timestamp' => 'UNIX_TIMESTAMP(hn.start_time)',
            'type'      => '
                CASE hn.notification_reason
                    WHEN 1 THEN \'notification_ack\'
                    WHEN 2 THEN \'notification_flapping\'
                    WHEN 3 THEN \'notification_flapping_end\'
                    WHEN 5 THEN \'notification_dt_start\'
                    WHEN 6 THEN \'notification_dt_end\'
                    WHEN 7 THEN \'notification_dt_end\'
                    WHEN 8 THEN \'notification_custom\'
                    ELSE \'notification_state\'
                END',
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'notifications' => array(
            'id'                        => 'hn.notification_id',
            'host'                      => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'                 => 'ho.name1',
            'notification_output'       => 'hn.output',
            'notification_reason'       => 'hn.notification_reason',
            'notification_state'        => 'hn.state',
            'notification_timestamp'    => 'UNIX_TIMESTAMP(hn.start_time)',
            'object_type'               => '(\'host\')'
        ),
        'servicegroups' => array(
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    protected function requireFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterExpression) {
            switch ($filter->getColumn()) {
                case 'output':
                    $this->requireColumn('output');
                    $filter->setColumn('hn.output');
                    return null;
                case 'timestamp':
                case 'notification_timestamp':
                    $this->requireColumn($filter->getColumn());
                    $filter->setColumn('hn.start_time');
                    $filter->setExpression($this->timestampForSql($this->valueToTimestamp($filter->getExpression())));
                    return null;
            }
        }

        return parent::requireFilterColumns($filter);
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $concattedContacts = null;
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
        $this->columnMap['history']['output'] = "('[' || $concattedContacts || '] ' || hn.output)";

        $this->select->from(
            array('hn' => $this->prefix . 'notifications'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hn.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
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
            'cn.notification_id = hn.notification_id',
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
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
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
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = ho.object_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
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
            'i.instance_id = hn.instance_id',
            array()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $group = array();

        if ($this->hasJoinedVirtualTable('history')
            || $this->hasJoinedVirtualTable('services')
            || $this->hasJoinedVirtualTable('hostgroups')
        ) {
            $group = array('hn.notification_id', 'ho.object_id');
            if ($this->hasJoinedVirtualTable('contactnotifications') && !$this->hasJoinedVirtualTable('history')) {
                $group[] = 'co.object_id';
            }
        } elseif ($this->hasJoinedVirtualTable('contactnotifications')) {
            $group = array('hn.notification_id', 'co.object_id', 'ho.object_id');
        }

        if (! empty($group)) {
            if ($this->hasJoinedVirtualTable('hosts')) {
                $group[] = 'h.host_id';
            }

            if ($this->hasJoinedVirtualTable('instances')) {
                $group[] = 'i.instance_id';
            }
        }

        return $group;
    }

    protected function joinSubQuery(IdoQuery $query, $name, $filter, $and, $negate, &$additionalFilter)
    {
        if ($name === 'hostgroup') {
            $query->joinVirtualTable('members');

            return ['hgm.host_object_id', 'ho.object_id'];
        } elseif ($name === 'servicegroup') {
            $query->joinVirtualTable('services');

            return ['s.host_object_id', 'ho.object_id'];
        }

        return parent::joinSubQuery($query, $name, $filter, $and, $negate, $additionalFilter);
    }
}
