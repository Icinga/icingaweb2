<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class EventgridQuery extends StatehistoryQuery
{
    /**
     * The columns additionally provided by this query
     *
     * @var array
     */
    protected $additionalColumns = array(
        'day'                   => 'DATE(FROM_UNIXTIME(sth.timestamp))',
        'cnt_up'                => "SUM(CASE WHEN sth.object_type = 'host' AND sth.state = 0 THEN 1 ELSE 0 END)",
        'cnt_down_hard'         => "SUM(CASE WHEN sth.object_type = 'host' AND sth.state = 1 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_down'              => "SUM(CASE WHEN sth.object_type = 'host' AND sth.state = 1 THEN 1 ELSE 0 END)",
        'cnt_unreachable_hard'  => "SUM(CASE WHEN sth.object_type = 'host' AND sth.state = 2 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_unreachable'       => "SUM(CASE WHEN sth.object_type = 'host' AND sth.state = 2 THEN 1 ELSE 0 END)",
        'cnt_unknown_hard'      => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 3 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_unknown'           => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 3 THEN 1 ELSE 0 END)",
        'cnt_unknown_hard'      => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 3 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_critical'          => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 2 THEN 1 ELSE 0 END)",
        'cnt_critical_hard'     => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 2 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_warning'           => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 1 THEN 1 ELSE 0 END)",
        'cnt_warning_hard'      => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 1 AND sth.type = 'hard_state' THEN 1 ELSE 0 END)",
        'cnt_ok'                => "SUM(CASE WHEN sth.object_type = 'service' AND sth.state = 0 THEN 1 ELSE 0 END)"
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
