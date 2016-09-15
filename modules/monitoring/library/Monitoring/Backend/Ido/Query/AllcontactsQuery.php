<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;

class AllcontactsQuery extends IdoQuery
{
    protected $columnMap = array(
        'contacts' => array(
            'contact_name'        => 'c.contact_name',
            'host_object_id'      => 'c.host_object_id',
            'host_name'           => 'c.host_name',
            'service_object_id'   => 'c.service_object_id',
            'service_host_name'   => 'c.service_host_name',
            'service_description' => 'c.service_description',

            'contact_alias'  => 'c.contact_alias',
            'contact_email'  => 'c.contact_email',
            'contact_pager'  => 'c.contact_pager',
            'contact_has_host_notfications'    => 'c.contact_has_host_notfications',
            'contact_has_service_notfications' => 'c.contact_has_service_notfications',
            'contact_can_submit_commands'      => 'c.contact_can_submit_commands',
            'contact_notify_service_recovery'  => 'c.notify_service_recovery',
            'contact_notify_service_warning'   => 'c.notify_service_warning',
            'contact_notify_service_critical'  => 'c.notify_service_critical',
            'contact_notify_service_unknown'   => 'c.notify_service_unknown',
            'contact_notify_service_flapping'  => 'c.notify_service_flapping',
            'contact_notify_service_downtime'  => 'c.notify_service_downtime',
            'contact_notify_host_recovery'     => 'c.notify_host_recovery',
            'contact_notify_host_down'         => 'c.notify_host_down',
            'contact_notify_host_unreachable'  => 'c.notify_host_unreachable',
            'contact_notify_host_flapping'     => 'c.notify_host_flapping',
            'contact_notify_host_downtime'     => 'c.notify_host_downtime',


        )
    );

    protected $contacts;
    protected $contactgroups;
    protected $useSubqueryCount = true;

    public function requireColumn($alias)
    {
        $this->contacts->addColumn($alias);
        $this->contactgroups->addColumn($alias);
        return parent::requireColumn($alias);
    }

    protected function joinBaseTables()
    {
        $this->contacts = $this->createSubQuery(
            'contact',
            array('contact_name')
        );
        $this->contactgroups = $this->createSubQuery(
            'contactgroup',
            array('contact_name')
        );
        $sub = $this->db->select()->union(
            array($this->contacts, $this->contactgroups),
            Zend_Db_Select::SQL_UNION_ALL
        );

        $this->baseQuery = $this->db->select()->distinct()->from(
            array('c' => $sub),
            array()
        );

        $this->joinedVirtualTables = array('contacts' => true);
    }
}
