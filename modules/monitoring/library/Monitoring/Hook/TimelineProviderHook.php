<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Module\Monitoring\Timeline\TimeRange;

/**
 * Base class for TimeLine providers
 */
abstract class TimelineProviderHook
{
    /**
     * Return the names by which to group entries
     *
     * @return  array   An array with the names as keys and their attribute-lists as values
     */
    abstract public function getIdentifiers();

    /**
     * Return the visible entries supposed to be shown on the timeline
     *
     * @param   TimeRange   $range      The range of time for which to fetch entries
     *
     * @return  array                   The entries to display on the timeline
     */
    abstract public function fetchEntries(TimeRange $range);

    /**
     * Return the entries supposed to be used to calculate forecasts
     *
     * @param   TimeRange   $range      The range of time for which to fetch forecasts
     *
     * @return  array                   The entries to calculate forecasts with
     */
    abstract public function fetchForecasts(TimeRange $range);
}
