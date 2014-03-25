<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Timeline;

use \DateTime;
use \Exception;
use \ArrayIterator;
use \IteratorAggregate;
use Icinga\Web\Hook;
use Icinga\Web\Session;
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
        if (preg_match('#([\d]+)([a-z]+|%)#', $width, $matches)) {
            $this->circleDiameter = intval($matches[1]);
            $this->diameterUnit = $matches[2];
        } else {
            throw new Exception('Width "' . $width . '" is not a valid width');
        }
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
        return sprintf('%.' . $precision . 'F%s', $this->circleDiameter * $factor, $this->diameterUnit);
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
            if ($this->session !== null) {
                // TODO: Do not use this if the interval has changed or the user did a reload
                $this->calculationBase = $this->session->get('calculationBase');
            }

            if ($create) {
                $new = $this->generateCalculationBase();
                if ($new > $this->calculationBase) {
                    $this->calculationBase = $new;

                    if ($this->session !== null) {
                        $this->session->calculationBase = $new;
                        Session::getSession()->write(); // TODO: Should it be possible to call write() on the namespace?
                    }
                }
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

        $query = $this->dataview->getQuery();
        $queryColumns = $query->getColumns();
        $query->where(
            $query->isValidFilterTarget('name') ? 'name' : $queryColumns['name'],
            array_keys($this->identifiers)
        )->where('raw_timestamp <= ?', $this->displayRange->getStart()->getTimestamp())
            ->where('raw_timestamp > ?', $this->forecastRange->getEnd()->getTimestamp());

        return array_merge($query->fetchAll(), $hookResults);
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

    /**
     * Build the legend
     */
    private function buildLegend()
    {
        // TODO: Put this in some sort of dedicated stylesheet
        $circleStyle = 'width:75px;height:75px;border-radius:50%;box-shadow:4px 4px 8px grey;border:2px solid;margin:auto;';
        $labelStyle = 'font-size:12px;margin-top:10px;text-align:center;';
        $titleStyle = 'margin-left:25px;';

        $elements = array();
        foreach ($this->getGroups() as $groupName => $groupInfo) {
            $groupColor = $groupInfo['color'] !== null ? $groupInfo['color'] : $this->getRandomCssColor();
            $elements[] = '' .
                '<div style="' . $circleStyle . 'background-color: ' . $groupColor . '"></div>' .
                '<p style="' . $labelStyle . '">' . $groupName . '</p>';
        }

        $legend = '' .
            '<h2 style="' . $titleStyle . '">' . t('Shown event groups') . '</h2>' .
            '<div class="row">' .
            implode(
                '',
                array_map(
                    function ($e) { return '<div class="col-sm-6 col-xs-3 col-md-2 col-lg-1">' . $e . '</div>'; },
                    $elements
                )
            ) .
            '</div>';

        return $legend;
    }

    /**
     * Build the timeline
     */
    public function buildTimeline()
    {
        $timelineGroups = array();
        foreach ($this->displayData as $group) {
            $timestamp = $group->getDateTime()->getTimestamp();

            if (!array_key_exists($timestamp, $timelineGroups)) {
                $timelineGroups[$timestamp] = array();
            }

            $timelineGroups[$timestamp][] = $group;
        }

        $elements = array();
        foreach ($this->range as $timestamp => $timeframe) {
            $elementGroups = array();
            $biggestWidth = 0;

            if (array_key_exists($timestamp, $timelineGroups)) {
                foreach ($timelineGroups[$timestamp] as $group) {
                    $circleWidth = $this->calculateCircleWidth(
                        empty($elements) ? $this->extrapolateEventCount($group, 4) : $group->getValue()
                    );
                    $groupColor = $group->getColor() !== null ? $group->getColor() : $this->getRandomCssColor();
                    $elementGroups[] = sprintf(
                        '<div class="col-sm-12 col-xs-12 col-md-6 col-lg-3" style="width:%4$s%2$s;margin:10px 10px;float:left;">' .
                        '  <a href="%1$s" data-icinga-target="detail">' .
                        '    <div style="width:%4$s%2$s;height:%4$s%2$s;border-radius:50%%;' . // TODO: Put this in some sort of dedicated stylesheet
                                        'box-shadow:4px 4px 8px grey;border:2px solid black;' .
                                        'margin:auto;background-color:%5$s;text-align:center;' .
                                        'padding-top:25%%;color:black;">' .
                        '      %3$s' .
                        '    </div>' .
                        '  </a>' .
                        '</div>',
                        $group->getDetailUrl(),
                        $this->diameterUnit,
                        $group->getValue(),
                        $circleWidth,
                        $groupColor
                    );

                    if ($circleWidth > $biggestWidth) {
                        $biggestWidth = $circleWidth;
                    }
                }
            }

            $timeframeUrl = '';/*$this->getRequest()->getBaseUrl() . '/monitoring/list/eventhistory?timestamp<=' .
                            $timeframe->start->getTimestamp() . '&timestamp>=' . $timeframe->end->getTimestamp();*/
            $elements[] = sprintf(
                '<div class="row" style="height:%3$s%2$s;">%1$s</div>',
                implode('', $elementGroups),
                $this->diameterUnit,
                $biggestWidth
            );
            $elements[] = '<br style="clear:all;" />';
            $elements[] = '<div><a href="' . $timeframeUrl . '" data-icinga-target="detail">' .
                          $timeframe->end->format($this->getIntervalFormat()) . '</a>' .
                          '<hr style="margin-top:0;"></div>';
        }

        $elements[] = '<span id="TimelineEnd"></span>';
        return implode('', $elements);
    }
}
