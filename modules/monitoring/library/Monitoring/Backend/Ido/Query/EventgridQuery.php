<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

abstract class EventgridQuery extends StatehistoryQuery
{
    /**
     * The columns additionally provided by this query
     *
     * @var array
     */
    protected $additionalColumns = array(
        'day'                   => 'DATE(FROM_UNIXTIME(sth.timestamp))',
        'cnt_up'                => "SUM(sth.state = 0)",
        'cnt_down_hard'         => "SUM(sth.state = 1 AND sth.type = 'hard_state')",
        'cnt_down'              => "SUM(sth.state = 1)",
        'cnt_unreachable_hard'  => "SUM(sth.state = 2 AND sth.type = 'hard_state')",
        'cnt_unreachable'       => "SUM(sth.state = 2)",
        'cnt_unknown_hard'      => "SUM(sth.state = 3 AND sth.type = 'hard_state')",
        'cnt_unknown'           => "SUM(sth.state = 3)",
        'cnt_unknown_hard'      => "SUM(sth.state = 3 AND sth.type = 'hard_state')",
        'cnt_critical'          => "SUM(sth.state = 2)",
        'cnt_critical_hard'     => "SUM(sth.state = 2 AND sth.type = 'hard_state')",
        'cnt_warning'           => "SUM(sth.state = 1)",
        'cnt_warning_hard'      => "SUM(sth.state = 1 AND sth.type = 'hard_state')",
        'cnt_ok'                => "SUM(sth.state = 0)"
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables();
        $this->requireVirtualTable('history');
        $this->columnMap['statehistory'] += $this->additionalColumns;
        $this->select->group(array('DATE(FROM_UNIXTIME(sth.timestamp))'));
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        if (array_key_exists($columnOrAlias, $this->additionalColumns)) {
            $subQueries = $this->subQueries;
            $this->subQueries = array();
            parent::order($columnOrAlias, $dir);
            $this->subQueries = $subQueries;
        } else {
            parent::order($columnOrAlias, $dir);
        }

        return $this;
    }
}
