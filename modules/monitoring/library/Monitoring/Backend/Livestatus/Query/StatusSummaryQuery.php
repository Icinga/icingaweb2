<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Livestatus\Query;

use Icinga\Protocol\Livestatus\Query;
use Icinga\Exception\ProgrammingError;

class StatusSummaryQuery extends Query
{
    protected $table = 'services';

    protected $available_columns = array(
    'service_host_name' => 'host_name',

            'services_total'                            => 'state != 9999',
            'services_problem'                          => 'state > 0',
            'services_problem_handled'                  => 'state > 0 & (scheduled_downtime_depth > 0 | acknowledged = 1 | host_state > 0)',
            'services_problem_unhandled'                => 'state > 0 & scheduled_downtime_depth = 0 & acknowledged = 0 & host_state = 0',
            'services_ok'                               => 'state = 0',
            'services_ok_not_checked'                   => 'state = 0 & accept_passive_checks = 0 & active_checks_enabled = 0',
            'services_pending'                          => 'has_been_checked = 0',
            'services_pending_not_checked'              => 'has_been_checked = 0 & accept_passive_checks = 0 & active_checks_enabled = 0',
            'services_warning'                          => 'state = 1',
            'services_warning_handled'                  => 'state = 1 & (scheduled_downtime_depth > 0 | acknowledged = 1 | host_state > 0)',
            'services_warning_unhandled'                => 'state = 1 & scheduled_downtime_depth = 0 & acknowledged = 0 & host_state = 0',
            'services_warning_passive'                  => 'state = 1 & accept_passive_checks = 1 & active_checks_enabled = 0',
            'services_warning_not_checked'              => 'state = 1 & accept_passive_checks = 0 & active_checks_enabled = 0',
            'services_critical'                         => 'state = 2',
            'services_critical_handled'                 => 'state = 2 & (scheduled_downtime_depth > 0 | acknowledged = 1 | host_state > 0)',
            'services_critical_unhandled'               => 'state = 2 & scheduled_downtime_depth = 0 & acknowledged = 0 & host_state = 0',
            'services_critical_passive'                 => 'state = 2 & accept_passive_checks = 1 & active_checks_enabled = 0',
            'services_critical_not_checked'             => 'state = 2 & accept_passive_checks = 0 & active_checks_enabled = 0',
            'services_unknown'                          => 'state = 3',
            'services_unknown_handled'                  => 'state = 3 & (scheduled_downtime_depth > 0 | acknowledged = 1 | host_state > 0)',
            'services_unknown_unhandled'                => 'state = 3 & scheduled_downtime_depth = 0 & acknowledged = 0 & host_state = 0',
            'services_unknown_passive'                  => 'state = 3 & accept_passive_checks = 1 & active_checks_enabled = 0',
            'services_unknown_not_checked'              => 'state = 3 & accept_passive_checks = 0 & active_checks_enabled = 0',
            'services_active'                           => 'active_checks_enabled = 1',
            'services_passive'                          => 'accept_passive_checks = 1 & active_checks_enabled = 0',
            'services_not_checked'                      => 'active_checks_enabled = 0 & accept_passive_checks = 0',
      );

      protected function columnsToString()
      {
          $parts = array();
          foreach ($this->columns as $col) {
if (! array_key_exists($col, $this->available_columns)) {
  throw new ProgrammingError('No such column: %s', $col);
}
              $filter = $this->filterStringToFilter($this->available_columns[$col]);

              //Filter::fromQueryString(str_replace(' ', '',  $this->available_columns[$col]));
              $parts[] = $this->renderFilter( $filter, 'Stats', 0, false);
          }
        $this->preparedHeaders = $this->columns;
          return implode("\n", $parts);
      }

      protected function renderkkFilter($filter, $type = 'Filter', $level = 0, $keylookup = true)
      {
          return parent::renderFilter($filter, 'Stats', $level, $keylookup);
      }
}
