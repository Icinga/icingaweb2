<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;
use Icinga\Data\Filter\Filter;

/**
 * Query for service status summary
 *
 * TODO(el): Allow to switch between hard and soft states
 */
class ServicestatussummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'servicestatussummary' => array(
            'services_critical'                             => 'SUM(CASE WHEN state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN state = 2 AND handled = 1 THEN 1 ELSE 0 END)',
//            'services_critical_handled_last_state_change'   => 'MAX(CASE WHEN state = 2 AND handled = 1 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN state = 2 AND handled = 0 THEN 1 ELSE 0 END)',
//            'services_critical_unhandled_last_state_change' => 'MAX(CASE WHEN state = 2 AND handled = 0 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_ok'                                   => 'SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END)',
//            'services_ok_last_state_change'                 => 'MAX(CASE WHEN state = 0 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_pending'                              => 'SUM(CASE WHEN state = 99 THEN 1 ELSE 0 END)',
//            'services_pending_last_state_change'            => 'MAX(CASE WHEN state = 99 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_total'                                => 'SUM(1)',
            'services_unknown'                              => 'SUM(CASE WHEN state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                      => 'SUM(CASE WHEN state = 3 AND handled = 1 THEN 1 ELSE 0 END)',
//            'services_unknown_handled_last_state_change'    => 'MAX(CASE WHEN state = 3 AND handled = 1 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_unknown_unhandled'                    => 'SUM(CASE WHEN state = 3 AND handled = 0 THEN 1 ELSE 0 END)',
//            'services_unknown_unhandled_last_state_change'  => 'MAX(CASE WHEN state = 3 AND handled = 0 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_warning'                              => 'SUM(CASE WHEN state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                      => 'SUM(CASE WHEN state = 1 AND handled = 1 THEN 1 ELSE 0 END)',
//            'services_warning_handled_last_state_change'    => 'MAX(CASE WHEN state = 1 AND handled = 1 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)',
            'services_warning_unhandled'                    => 'SUM(CASE WHEN state = 1 AND handled = 0 THEN 1 ELSE 0 END)',
//            'services_warning_unhandled_last_state_change'  => 'MAX(CASE WHEN state = 1 AND handled = 0 THEN UNIX_TIMESTAMP(last_state_change) ELSE NULL END)'
        )
    );

    /**
     * The service status sub select
     *
     * @var ServiceStatusQuery
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
            'servicestatus',
            array(
                'handled'       => 'service_handled',
                'state'         => 'service_state',
                'state_change'  => 'service_last_state_change'
            )
        );
        $this->select->from(
            array('servicestatussummary' => $this->subSelect->setIsSubQuery(true)),
            array()
        );
        $this->joinedVirtualTables['servicestatussummary'] = true;
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
