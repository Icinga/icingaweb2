<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;

/**
 * Query for host group summaries
 */
class HoststatussummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hoststatussummary' => array(
            'hosts_down'                    => 'SUM(state = 1)',
            'hosts_down_handled'            => 'SUM(state = 1 AND handled = 1)',
            'hosts_down_unhandled'          => 'SUM(state = 1 AND handled = 0)',
            'hosts_pending'                 => 'SUM(state = 99)',
            'hosts_total'                   => 'SUM(1)',
            'hosts_unreachable'             => 'SUM(state = 2)',
            'hosts_unreachable_handled'     => 'SUM(state = 2 AND handled = 1)',
            'hosts_unreachable_unhandled'   => 'SUM(state = 2 AND handled = 0)',
            'hosts_up'                      => 'SUM(state = 0)'
        )
    );

    /**
     * The host status sub select
     *
     * @var HostStatusQuery
     */
    protected $subSelect;

    /**
     * {@inheritdoc}
     */
    public function allowsCustomVars()
    {
        return $this->subSelect->allowsCustomVars();
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        $this->subSelect->applyFilter(clone $filter);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        // TODO(el): Allow to switch between hard and soft states
        $this->subSelect = $this->createSubQuery(
            'Hoststatus',
            array(
                'handled'       => 'host_handled',
                'state'         => 'host_state',
                'state_change'  => 'host_last_state_change'
            )
        );
        $this->select->from(
            array('hoststatussummary' => $this->subSelect->setIsSubQuery(true)),
            array()
        );
        $this->joinedVirtualTables['hoststatussummary'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->subSelect->where($condition, $value);
        return $this;
    }
}
