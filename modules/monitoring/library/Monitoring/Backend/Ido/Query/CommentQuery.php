<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service comments
 */
class CommentQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'comments' => array(
            'comment_author'        => 'c.comment_author',
            'comment_author_name'   => 'c.comment_author_name',
            'comment_data'          => 'c.comment_data',
            'comment_expiration'    => 'c.comment_expiration',
            'comment_internal_id'   => 'c.comment_internal_id',
            'comment_is_persistent' => 'c.comment_is_persistent',
            'comment_name'          => 'c.comment_name',
            'comment_timestamp'     => 'c.comment_timestamp',
            'comment_type'          => 'c.comment_type',
            'instance_name'         => 'c.instance_name',
            'object_type'           => 'c.object_type'
        ),
        'hosts' => array(
            'host_display_name' => 'c.host_display_name',
            'host_name'         => 'c.host_name',
            'host_state'        => 'c.host_state'
        ),
        'services' => array(
            'service_description'   => 'c.service_description',
            'service_display_name'  => 'c.service_display_name',
            'service_host_name'     => 'c.service_host_name',
            'service_state'         => 'c.service_state'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $commentQuery;

    /**
     * Subqueries used for the comment query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

    /**
     * {@inheritdoc}
     */
    public function allowsCustomVars()
    {
        foreach ($this->subQueries as $query) {
            if (! $query->allowsCustomVars()) {
                return false;
            }
        }

        return true;
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
    protected function joinBaseTables()
    {
        if (version_compare($this->getIdoVersion(), '1.14.0', '<')) {
            $this->columnMap['comments']['comment_name'] = '(NULL)';
        }
        $this->commentQuery = $this->db->select();
        $this->select->from(
            array('c' => $this->commentQuery),
            array()
        );
        $this->joinedVirtualTables['comments'] = true;
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = array_keys($this->columnMap['comments'] + $this->columnMap['hosts']);
        foreach (array_keys($this->columnMap['services']) as $column) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        $hosts = $this->createSubQuery('hostcomment', $columns);
        $this->subQueries[] = $hosts;
        $this->commentQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys($this->columnMap['comments'] + $this->columnMap['hosts'] + $this->columnMap['services']);
        $services = $this->createSubQuery('servicecomment', $columns);
        $this->subQueries[] = $services;
        $this->commentQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
