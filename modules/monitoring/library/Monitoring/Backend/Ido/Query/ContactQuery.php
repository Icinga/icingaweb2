<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\FilterExpression;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for contacts
 */
class ContactQuery extends IdoQuery
{
    protected $columnMap = [
        'contacts' => [
            'contact_id'                        => 'c.contact_id',
            'contact'                           => 'c.contact',
            'contact_name'                      => 'c.contact_name',
            'contact_alias'                     => 'c.contact_alias',
            'contact_email'                     => 'c.contact_email',
            'contact_pager'                     => 'c.contact_pager',
            'contact_object_id'                 => 'c.contact_object_id',
            'contact_has_host_notfications'     => 'c.contact_has_host_notfications',
            'contact_has_service_notfications'  => 'c.contact_has_service_notfications',
            'contact_can_submit_commands'       => 'c.contact_can_submit_commands',
            'contact_notify_service_recovery'   => 'c.contact_notify_service_recovery',
            'contact_notify_service_warning'    => 'c.contact_notify_service_warning',
            'contact_notify_service_critical'   => 'c.contact_notify_service_critical',
            'contact_notify_service_unknown'    => 'c.contact_notify_service_unknown',
            'contact_notify_service_flapping'   => 'c.contact_notify_service_flapping',
            'contact_notify_service_downtime'   => 'c.contact_notify_service_downtime',
            'contact_notify_host_recovery'      => 'c.contact_notify_host_recovery',
            'contact_notify_host_down'          => 'c.contact_notify_host_down',
            'contact_notify_host_unreachable'   => 'c.contact_notify_host_unreachable',
            'contact_notify_host_flapping'      => 'c.contact_notify_host_flapping',
            'contact_notify_host_downtime'      => 'c.contact_notify_host_downtime',
            'contact_notify_host_timeperiod'    => 'c.contact_notify_host_timeperiod',
            'contact_notify_service_timeperiod' => 'c.contact_notify_service_timeperiod'
        ]
    ];

    /** @var Zend_Db_Select The union */
    protected $contactQuery;

    /** @var IdoQuery[] Subqueries used for the contact query */
    protected $subQueries = [];

    public function allowsCustomVars()
    {
        foreach ($this->subQueries as $query) {
            if (! $query->allowsCustomVars()) {
                return false;
            }
        }

        return true;
    }

    public function addFilter(Filter $filter)
    {
        $strangers = array_diff(
            $filter->listFilteredColumns(),
            array_keys($this->columnMap['contacts'])
        );
        if (! empty($strangers)) {
            $this->transformToUnion();
        }

        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }

        return $this;
    }

    protected function joinBaseTables()
    {
        $this->contactQuery = $this->createSubQuery('Hostcontact', array_keys($this->columnMap['contacts']));
        $this->contactQuery->setIsSubQuery();
        $this->subQueries[] = $this->contactQuery;

        $this->select->from(
            ['c' => $this->contactQuery],
            []
        );

        $this->joinedVirtualTables['contacts'] = true;
    }

    public function order($columnOrAlias, $dir = null)
    {
        foreach ($this->subQueries as $sub) {
            $sub->requireColumn($columnOrAlias);
        }

        return parent::order($columnOrAlias, $dir);
    }

    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }

        return $this;
    }

    public function whereEx(FilterExpression $ex)
    {
        $this->requireColumn($ex->getColumn());
        foreach ($this->subQueries as $sub) {
            $sub->whereEx($ex);
        }

        return $this;
    }

    public function transformToUnion()
    {
        $this->contactQuery = $this->db->select();
        $this->select->reset();
        $this->subQueries = [];

        $this->select->distinct()->from(
            ['c' => $this->contactQuery],
            []
        );

        $hosts = $this->createSubQuery('Hostcontact', array_keys($this->columnMap['contacts']));
        $this->subQueries[] = $hosts;
        $this->contactQuery->union([$hosts], Zend_Db_Select::SQL_UNION_ALL);

        $services = $this->createSubQuery('Servicecontact', array_keys($this->columnMap['contacts']));
        $this->subQueries[] = $services;
        $this->contactQuery->union([$services], Zend_Db_Select::SQL_UNION_ALL);
    }
}
