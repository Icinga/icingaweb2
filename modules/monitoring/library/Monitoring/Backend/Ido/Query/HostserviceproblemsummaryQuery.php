<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;

class HostservicestatussummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * The HoststatusQuery
     *
     * @var HoststatusQuery
     */
    protected $hostQuery;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'statussummary' => array(
            'host_name'                 => 'so.name1',
            'unhandled_service_count'   => 'SUM(
                CASE
                    WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0
                    THEN 0
                    ELSE 1
                END
            )'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->hostQuery = $this->createSubQuery('Hoststatus', array('object_id'));
        $this->hostQuery->setIsSubQuery(); // TODO: Why is this necessary???

        $this->select->from(
            array('so' => $this->prefix . 'objects'),
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        )->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id AND ss.current_state > 0',
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = s.host_object_id',
            array()
        )->join(
            array('h' => $this->hostQuery),
            'h.object_id = s.host_object_id',
            array()
        );

        $this->select->where('so.is_active = 1');
        $this->select->group('so.name1');
        $this->select->having($this->getMappedField('unhandled_service_count') . ' > 0');
        $this->joinedVirtualTables['statussummary'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        $this->hostQuery->addFilter($filter);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        $this->hostQuery->order($columnOrAlias, $dir);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->hostQuery->where($condition, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($count = null, $offset = null)
    {
        $this->hostQuery->limit($count, $offset);
        return $this;
    }
}
