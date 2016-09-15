<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service notifications
 */
class NotificationQuery extends IdoQuery
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
            'instance_name'                 => 'n.instance_name',
            'notification_contact_name'     => 'n.notification_contact_name',
            'notification_output'           => 'n.notification_output',
            'notification_state'            => 'n.notification_state',
            'notification_timestamp'        => 'n.notification_timestamp'
        ),
        'hosts' => array(
            'host_display_name' => 'n.host_display_name',
            'host_name'         => 'n.host_name'
        ),
        'services' => array(
            'service_description'   => 'n.service_description',
            'service_display_name'  => 'n.service_display_name',
            'service_host_name'     => 'n.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $notificationQuery;

    /**
     * Subqueries used for the notification query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->notificationQuery = $this->db->select();
        $this->select->from(
            array('n' => $this->notificationQuery),
            array()
        );
        $this->joinedVirtualTables['notifications'] = true;
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = $this->desiredColumns;
        $columns = array_combine($columns, $columns);
        foreach ($this->columnMap['services'] as $column => $_) {
            if (isset($columns[$column])) {
                $columns[$column] = new Zend_Db_Expr('NULL');
            }
        }
        $hosts = $this->createSubQuery('hostnotification', $columns);
        $hosts->setIsSubQuery(true);
        $this->subQueries[] = $hosts;
        $this->notificationQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $services = $this->createSubQuery('servicenotification', $this->desiredColumns);
        $services->setIsSubQuery(true);
        $this->subQueries[] = $services;
        $this->notificationQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        foreach ($this->subQueries as $sub) {
            $sub->requireColumn($columnOrAlias);
        }
        return parent::order($columnOrAlias, $dir);
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }
        return $this;
    }
}
