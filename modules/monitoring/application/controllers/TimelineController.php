<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \DateTime;
use \Exception;
use \DateInterval;
use Icinga\Web\Hook;
use Icinga\Application\Config;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Controller\ActionController;
use Icinga\Module\Monitoring\Timeline\TimeLine;
use Icinga\Module\Monitoring\Timeline\TimeEntry;
use Icinga\Module\Monitoring\Timeline\TimeRange;
use Icinga\Module\Monitoring\Web\Widget\TimelineIntervalBox;
use Icinga\Module\Monitoring\DataView\EventHistory as EventHistoryView;

class Monitoring_TimelineController extends ActionController
{
    public function showAction()
    {
        $this->setupIntervalBox();
        $timeline = new TimeLine();
        $timeline->setConfiguration(Config::app());
        //$timeline->setAttrib('data-icinga-component', 'monitoring/timelineComponent');
        list($displayRange, $forecastRange) = $this->buildTimeRanges($this->getTimelineInterval());
        $timeline->setTimeRange($displayRange);
        $timeline->setDisplayData($this->loadData($displayRange));
        $timeline->setForecastData($this->loadData($forecastRange));
        $this->view->timeline = $timeline;
    }

    public function extendAction()
    {
        $this->setupIntervalBox();
        $timeline = new TimeLine();
        $timeline->setConfiguration(Config::app());
        list($displayRange, $forecastRange) = $this->buildTimeRanges($this->getTimelineInterval());
        $timeline->setTimeRange($displayRange);
        $timeline->setDisplayData($this->loadData($displayRange));
        $timeline->setForecastData($this->loadData($forecastRange));
        $this->view->timeline = $timeline;

        // Disable layout as this is an AJAX request
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Create a select box the user can choose the timeline interval from
     */
    private function setupIntervalBox()
    {
        $box = new TimelineIntervalBox(
            'intervalBox',
            array(
                '4h' => t('4 Hours'),
                '1d' => t('One day'),
                '1w' => t('One week'),
                '1m' => t('One month'),
                '1y' => t('One year')
            )
        );
        $box->applyRequest($this->getRequest());
        $this->view->intervalBox = $box;
    }

    /**
     * Return the chosen interval
     *
     * @return  DateInterval    The chosen interval
     */
    private function getTimelineInterval()
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                return new DateInterval('P1D');
            case '1w':
                return new DateInterval('P1W');
            case '1m':
                return new DateInterval('P1M');
            case '1y':
                return new DateInterval('P1Y');
            default:
                return new DateInterval('PT4H');
        }
    }

    /**
     * Return a new display- and forecast time range
     *
     * Assembles a time range each for display and forecast purposes based on the start- and
     * end time if given in the current request otherwise based on the current time and a
     * end time that is calculated based on the given interval.
     *
     * @param   DateInterval    $interval   The interval by which to part the time range
     * @return  TimeRange                   The resulting time range
     * @throws  Exception                   If a start time is given in the request but no end time
     */
    private function buildTimeRanges(DateInterval $interval)
    {
        $startTime = DateTime::createFromFormat('Y-m-d_G-i-s', $this->_request->getParam('start'));
        $endTime = DateTime::createFromFormat('Y-m-d_G-i-s', $this->_request->getParam('end'));

        if (!$startTime) {
            $startTime = $this->extrapolateDateTime(new DateTime(), $interval);
            $endTime = clone $startTime;
            $endTime->sub($this->getPreloadInterval($interval));
        } elseif (!$endTime) {
            throw new Exception('Missing end time in request');
        }

        $forecastStart = clone $endTime;
        $forecastStart->sub(new DateInterval('PT1S'));
        $forecastEnd = clone $forecastStart;
        $forecastEnd->sub($endTime->diff($startTime));

        return array(
            new TimeRange($startTime, $endTime, $interval),
            new TimeRange($forecastStart, $forecastEnd, $interval)
        );
    }

    /**
     * Extrapolate the given datetime based on the given interval
     *
     * @param   DateTime        $dateTime   The datetime to extrapolate
     * @param   DateInterval    $interval   The interval by which to part a time range
     * @return  DateTime                    The extrapolated datetime
     * @throws  Exception                   If the given interval is invalid
     */
    private function extrapolateDateTime(DateTime $dateTime, DateInterval $interval)
    {
        if ($interval->h == 4) {
            $hour = $dateTime->format('G');
            $end = $hour < 4 ? 4 : ($hour < 8 ? 8 : ($hour < 12 ? 12 : ($hour < 16 ? 16 : ($hour < 20 ? 20 : 24))));
            $dateTime = DateTime::createFromFormat('d/m/y G:i:s', $dateTime->format('d/m/y') . ($end - 1) . ':59:59');
        } elseif ($interval->d == 1) {
            $dateTime->setTimestamp(strtotime('tomorrow', $dateTime->getTimestamp()) - 1);
        } elseif ($interval->d == 7) {
            $dateTime->setTimestamp(strtotime('next monday', $dateTime->getTimestamp()) - 1);
        } elseif ($interval->m == 1) {
            $dateTime->setTimestamp(
                strtotime(
                    'last day of this month',
                    strtotime(
                        'tomorrow',
                        $dateTime->getTimestamp()
                    ) - 1
                )
            );
        } elseif ($interval->y == 1) {
            $dateTime->setTimestamp(strtotime('1 january next year', $dateTime->getTimestamp()) - 1);
        } else {
            throw new Exception('Invalid interval given. Valid intervals are: 4 hours, 1 day, 1 week, 1 month, 1 year');
        }

        return $dateTime;
    }

    /**
     * Return a new preload interval
     *
     * Examine the given interval and return a new one that defines how much data should be loaded
     *
     * @param   DateInterval    $interval   The interval by which to part a time range
     * @return  DateInterval                The interval to load
     * @throws  Exception                   If the given interval is invalid
     */
    private function getPreloadInterval(DateInterval $interval)
    {
        if ($interval->h == 4) {
            return DateInterval::createFromDateString('1 day -1 second');
        } elseif ($interval->d == 1) {
            return DateInterval::createFromDateString('1 week -1 second');
        } elseif ($interval->d == 7) {
            return DateInterval::createFromDateString('8 weeks -1 second');
        } elseif ($interval->m == 1) {
            return DateInterval::createFromDateString('6 months -1 second');
        } elseif ($interval->y == 1) {
            return DateInterval::createFromDateString('4 years -1 second');
        } else {
            throw new Exception('Invalid interval given. Valid intervals are: 4 hours, 1 day, 1 week, 1 month, 1 year');
        }
    }

    /**
     * Groups a set of elements based on a specific range of time
     *
     * @param   TimeRange   $range          The range of time represented by the timeline
     * @param   array       $elements       The elements to group. Each element need to have a ´time´ property
     *                                      that defines its position in the given range of time
     * @param   array       $attributes     The attributes to set on each event group. Need to contain at least
     *                                      a ´name´ and a ´detailUrl´. The detailUrl need also to contain
     *                                      placeholders for both the start- and end time of a specific timeframe
     * @return  array                       A list of event groups suitable to pass to the timeline
     * @throws  ProgrammingError            If an element is found that does not match the given range of time
     *                                      or one of the required attributes is missing
     */
    private function groupResults(TimeRange $range, array $elements, array $attributes)
    {
        $groupCounts = array();
        foreach ($elements as $element) {
            $elementTime = new DateTime();
            $elementTime->setTimestamp($element->time);
            $timeframeIdentifier = $range->findTimeframe($elementTime, true);

            if ($timeframeIdentifier === null) {
                $format = 'd/m/y G:i:s';
                throw new ProgrammingError(
                    'Event result does not match any timeframe in the given range of time: ' .
                    $elementTime->format($format) . ' not in ' . $range->getStart()->format($format) .
                    ' -> ' . $range->getEnd()->format($format)
                );
            }

            if (array_key_exists($timeframeIdentifier, $groupCounts)) {
                $groupCounts[$timeframeIdentifier] += 1;
            } else {
                $groupCounts[$timeframeIdentifier] = 1;
            }
        }

        if (!array_key_exists('name', $attributes) || !array_key_exists('detailUrl', $attributes)) {
            throw new ProgrammingError('Missing required event group attribute. Either ´name´ or ´detailUrl´');
        }

        $groups = array();
        $urlTemplate = $attributes['detailUrl'];
        foreach ($groupCounts as $timeframeIdentifier => $groupCount) {
            $timeframe = $range->getTimeframe($timeframeIdentifier);
            $attributes['dateTime'] = $timeframe->start;
            $attributes['value'] = $groupCount;
            $attributes['detailUrl'] = sprintf(
                $urlTemplate,
                $timeframe->start->getTimestamp(),
                $timeframe->end->getTimestamp()
            );
            $groups[] = TimeEntry::fromArray($attributes);
        }

        return $groups;
    }

    /**
     * Load the event groups that the timeline should display
     *
     * @param   TimeRange   $timeRange      The range of time represented by the timeline
     * @return  array
     */
    private function loadData(TimeRange $timeRange)
    {
        $entries = array_merge(
            $this->loadInitiatedDowntimes($timeRange),
            $this->loadFinishedDowntimes($timeRange),
            $this->loadAcknowledgements($timeRange),
            $this->loadNotifications($timeRange),
            $this->loadStateChanges($timeRange),
            $this->loadComments($timeRange)
        );

        foreach (Hook::all('timeline') as $timelineProvider) {
            $entries = array_merge(
                $entries,
                $timelineProvider->fetchTimeEntries($timeRange, $this->_request)
            );
        }

        return $entries;
    }

    /**
     * Aggregate all problem notifications sent out in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadNotifications(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = notify')
            ->where('state != 0')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Notifications'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=notify&state>0'
                )
            )
        );
    }

    /**
     * Aggregate all status changes occured in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadStateChanges(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = hard_state')
            ->where('state != 0')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Hard states'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=hard_state&state>0'
                )
            )
        );
    }

    /**
     * Aggregate all comments made in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadComments(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = comment')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Comments'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=comment'
                )
            )
        );
    }

    /**
     * Aggregate all acknowledgements placed in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadAcknowledgements(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = ack')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Acknowledgements'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=ack'
                )
            )
        );
    }

    /**
     * Aggregate all downtimes that were initiated in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadInitiatedDowntimes(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = dt_start')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Initiated downtimes'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=dt_start'
                )
            )
        );
    }

    /**
     * Aggregate all downtimes that were finished in the given range of time
     *
     * @param   TimeRange   $range      The range of time represented by the timeline
     * @return  array
     */
    private function loadFinishedDowntimes(TimeRange $range)
    {
        $query = EventHistoryView::fromRequest(
            $this->_request,
            array(
                'time' => 'timestamp'
            )
        )->getQuery();

        $result = $query->where('timestamp <= ' . $range->getStart()->getTimestamp())
            ->where('timestamp > ' . $range->getEnd()->getTimestamp())
            ->where('type = dt_end')
            ->fetchAll();

        return $this->groupResults(
            $range,
            $result,
            array(
                'name'      => t('Finished downtimes'),
                'detailUrl' => $this->view->baseUrl(
                    'monitoring/list/eventhistory?timestamp<=%s&timestamp>=%s&type=dt_end'
                )
            )
        );
    }
}
