<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Timeline;

use DateTime;
use Exception;
use ArrayIterator;
use Icinga\Exception\IcingaException;
use IteratorAggregate;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Hook;
use Icinga\Web\Session\SessionNamespace;
use Icinga\Module\Monitoring\DataView\DataView;

/**
 * Represents a set of events in a specific range of time
 */
class TimeLine implements IteratorAggregate
{
    /**
     * The resultset returned by the dataview
     *
     * @var array
     */
    private $resultset;

    /**
     * The groups this timeline uses for display purposes
     *
     * @var array
     */
    private $displayGroups;

    /**
     * The session to use
     *
     * @var SessionNamespace
     */
    protected $session;

    /**
     * The base that is used to calculate each circle's diameter
     *
     * @var float
     */
    protected $calculationBase;

    /**
     * The dataview to fetch entries from
     *
     * @var DataView
     */
    protected $dataview;

    /**
     * The names by which to group entries
     *
     * @var array
     */
    protected $identifiers;

    /**
     * The range of time for which to display entries
     *
     * @var TimeRange
     */
    protected $displayRange;

    /**
     * The range of time for which to calculate forecasts
     *
     * @var TimeRange
     */
    protected $forecastRange;

    /**
     * The maximum diameter each circle can have
     *
     * @var float
     */
    protected $circleDiameter = 100.0;

    /**
     * The minimum diameter each circle can have
     *
     * @var float
     */
    protected $minCircleDiameter = 1.0;

    /**
     * The unit of a circle's diameter
     *
     * @var string
     */
    protected $diameterUnit = 'px';

    /**
     * Return a iterator for this timeline
     *
     * @return  ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Create a new timeline
     *
     * The given dataview must provide the following columns:
     * - name   A string identifying an entry (Corresponds to the keys of "$identifiers")
     * - time   A unix timestamp that defines where to place an entry on the timeline
     *
     * @param   DataView    $dataview       The dataview to fetch entries from
     * @param   array       $identifiers    The names by which to group entries
     */
    public function __construct(DataView $dataview, array $identifiers)
    {
        $this->dataview = $dataview;
        $this->identifiers = $identifiers;
    }

    /**
     * Set the session to use
     *
     * @param   SessionNamespace    $session    The session to use
     */
    public function setSession(SessionNamespace $session)
    {
        $this->session = $session;
    }

    /**
     * Set the range of time for which to display elements
     *
     * @param   TimeRange   $range      The range of time for which to display elements
     */
    public function setDisplayRange(TimeRange $range)
    {
        $this->displayRange = $range;
    }

    /**
     * Set the range of time for which to calculate forecasts
     *
     * @param   TimeRange   $range      The range of time for which to calculate forecasts
     */
    public function setForecastRange(TimeRange $range)
    {
        $this->forecastRange = $range;
    }

    /**
     * Set the maximum diameter each circle can have
     *
     * @param   string      $width      The diameter to set, suffixed with its unit
     *
     * @throws  Exception               If the given diameter is invalid
     */
    public function setMaximumCircleWidth($width)
    {
        $matches = array();
        if (preg_match('#([\d|\.]+)([a-z]+|%)#', $width, $matches)) {
            $this->circleDiameter = floatval($matches[1]);
            $this->diameterUnit = $matches[2];
        } else {
            throw new IcingaException(
                'Width "%s" is not a valid width',
                $width
            );
        }
    }

    /**
     * Set the minimum diameter each circle can have
     *
     * @param   string      $width      The diameter to set, suffixed with its unit
     *
     * @throws  Exception               If the given diameter is invalid or its unit differs from the maximum
     */
    public function setMinimumCircleWidth($width)
    {
        $matches = array();
        if (preg_match('#([\d|\.]+)([a-z]+|%)#', $width, $matches)) {
            if ($matches[2] === $this->diameterUnit) {
                $this->minCircleDiameter = floatval($matches[1]);
            } else {
                throw new IcingaException(
                    'Unit needs to be in "%s"',
                    $this->diameterUnit
                );
            }
        } else {
            throw new IcingaException(
                'Width "%s" is not a valid width',
                $width
            );
        }
    }

    /**
     * Return all known group types (identifiers) with their respective labels and classess as array
     *
     * @return  array
     */
    public function getGroupInfo()
    {
        $groupInfo = array();
        foreach ($this->identifiers as $name => $attributes) {
            $groupInfo[$name]['class'] = $attributes['class'];
            $groupInfo[$name]['label'] = $attributes['label'];
        }

        return $groupInfo;
    }

    /**
     * Return the circle's diameter for the given event group
     *
     * @param   TimeEntry   $group          The group for which to return a circle width
     * @param   int         $precision      Amount of decimal places to preserve
     *
     * @return  string
     */
    public function calculateCircleWidth(TimeEntry $group, $precision = 0)
    {
        $base = $this->getCalculationBase(true);
        $factor = log($group->getValue() * $group->getWeight(), $base) / 100;
        $width = $this->circleDiameter * $factor;
        return sprintf(
            '%.' . $precision . 'F%s',
            $width > $this->minCircleDiameter ? $width : $this->minCircleDiameter,
            $this->diameterUnit
        );
    }

    /**
     * Return an extrapolated circle width for the given event group
     *
     * @param   TimeEntry   $group          The event group for which to return an extrapolated circle width
     * @param   int         $precision      Amount of decimal places to preserve
     *
     * @return  string
     */
    public function getExtrapolatedCircleWidth(TimeEntry $group, $precision = 0)
    {
        $eventCount = 0;
        foreach ($this->displayGroups as $groups) {
            if (array_key_exists($group->getName(), $groups)) {
                $eventCount += $groups[$group->getName()]->getValue();
            }
        }

        $extrapolatedCount = (int) $eventCount / count($this->displayGroups);
        if ($extrapolatedCount < $group->getValue()) {
            return $this->calculateCircleWidth($group, $precision);
        }

        return $this->calculateCircleWidth(
            TimeEntry::fromArray(
                array(
                    'value'     => $extrapolatedCount,
                    'weight'    => $group->getWeight()
                )
            ),
            $precision
        );
    }

    /**
     * Return the base that should be used to calculate circle widths
     *
     * @param   bool    $create     Whether to generate a new base if none is known yet
     *
     * @return  float|null
     */
    public function getCalculationBase($create)
    {
        if ($this->calculationBase === null) {
            $calculationBase = $this->session !== null ? $this->session->get('calculationBase') : null;

            if ($create) {
                $new = $this->generateCalculationBase();
                if ($new > $calculationBase) {
                    $this->calculationBase = $new;

                    if ($this->session !== null) {
                        $this->session->calculationBase = $new;
                    }
                } else {
                    $this->calculationBase = $calculationBase;
                }
            } else {
                return $calculationBase;
            }
        }

        return $this->calculationBase;
    }

    /**
     * Generate a new base to calculate circle widths with
     *
     * @return  float
     */
    protected function generateCalculationBase()
    {
        $allEntries = $this->groupEntries(
            array_merge(
                $this->fetchEntries(),
                $this->fetchForecasts()
            ),
            new TimeRange(
                $this->displayRange->getStart(),
                $this->forecastRange->getEnd(),
                $this->displayRange->getInterval()
            )
        );

        $highestValue = 0;
        foreach ($allEntries as $groups) {
            foreach ($groups as $group) {
                if ($group->getValue() * $group->getWeight() > $highestValue) {
                    $highestValue = $group->getValue() * $group->getWeight();
                }
            }
        }

        return pow($highestValue, 1 / 100); // 100 == 100%
    }

    /**
     * Fetch all entries and forecasts by using the dataview associated with this timeline
     *
     * @return  array       The dataview's result
     */
    private function fetchResults()
    {
        $hookResults = array();
        foreach (Hook::all('timeline') as $timelineProvider) {
            $hookResults = array_merge(
                $hookResults,
                $timelineProvider->fetchEntries($this->displayRange),
                $timelineProvider->fetchForecasts($this->forecastRange)
            );

            foreach ($timelineProvider->getIdentifiers() as $identifier => $attributes) {
                if (!array_key_exists($identifier, $this->identifiers)) {
                    $this->identifiers[$identifier] = $attributes;
                }
            }
        }

        $query = $this->dataview;
        $filter = Filter::matchAll(
            Filter::where('type', array_keys($this->identifiers)),
            Filter::expression('timestamp', '<=', $this->displayRange->getStart()->getTimestamp()),
            Filter::expression('timestamp', '>', $this->displayRange->getEnd()->getTimestamp())
        );
        $query->applyFilter($filter);
        return array_merge($query->getQuery()->fetchAll(), $hookResults);
    }

    /**
     * Fetch all entries
     *
     * @return  array       The entries to display on the timeline
     */
    protected function fetchEntries()
    {
        if ($this->resultset === null) {
            $this->resultset = $this->fetchResults();
        }

        $range = $this->displayRange;
        return array_filter(
            $this->resultset,
            function ($e) use ($range) { return $range->validateTime($e->time); }
        );
    }

    /**
     * Fetch all forecasts
     *
     * @return  array       The entries to calculate forecasts with
     */
    protected function fetchForecasts()
    {
        if ($this->resultset === null) {
            $this->resultset = $this->fetchResults();
        }

        $range = $this->forecastRange;
        return array_filter(
            $this->resultset,
            function ($e) use ($range) { return $range->validateTime($e->time); }
        );
    }

    /**
     * Return the given entries grouped together
     *
     * @param   array       $entries        The entries to group
     * @param   TimeRange   $timeRange      The range of time to group by
     *
     * @return  array                 displayGroups      The grouped entries
     */
    protected function groupEntries(array $entries, TimeRange $timeRange)
    {
        $counts = array();
        foreach ($entries as $entry) {
            $entryTime = new DateTime();
            $entryTime->setTimestamp($entry->time);
            $timestamp = $timeRange->findTimeframe($entryTime, true);

            if ($timestamp !== null) {
                if (array_key_exists($entry->name, $counts)) {
                    if (array_key_exists($timestamp, $counts[$entry->name])) {
                        $counts[$entry->name][$timestamp] += 1;
                    } else {
                        $counts[$entry->name][$timestamp] = 1;
                    }
                } else {
                    $counts[$entry->name][$timestamp] = 1;
                }
            }
        }

        $groups = array();
        foreach ($counts as $name => $data) {
            foreach ($data as $timestamp => $count) {
                $dateTime = new DateTime();
                $dateTime->setTimestamp($timestamp);
                $groups[$timestamp][$name] = TimeEntry::fromArray(
                    array_merge(
                        $this->identifiers[$name],
                        array(
                            'name'      => $name,
                            'value'     => $count,
                            'dateTime'  => $dateTime
                        )
                    )
                );
            }
        }

        return $groups;
    }

    /**
     * Return the contents of this timeline as array
     *
     * @return  array
     */
    protected function toArray()
    {
        $this->displayGroups = $this->groupEntries($this->fetchEntries(), $this->displayRange);

        $array = array();
        foreach ($this->displayRange as $timestamp => $timeframe) {
            $array[] = array(
                $timeframe,
                array_key_exists($timestamp, $this->displayGroups) ? $this->displayGroups[$timestamp] : array()
            );
        }

        return $array;
    }
}
