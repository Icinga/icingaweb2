<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service notification history
 */
class NotificationhistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'history' => array(
            'object_type'   => 'n.object_type',
            'output'        => 'n.output',
            'state'         => 'n.state',
            'timestamp'     => 'n.timestamp',
            'type'          => "('notify')"
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
        $this->joinedVirtualTables['history'] = true;
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
        if (isset($columns['type'])) {
            unset($columns['type']);
        }
        $hosts = $this->createSubQuery('hostnotification', $columns);
        $this->subQueries[] = $hosts;
        $this->notificationQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_flip($this->desiredColumns);
        if (isset($columns['type'])) {
            unset($columns['type']);
        }
        $services = $this->createSubQuery('servicenotification', array_flip($columns));
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
