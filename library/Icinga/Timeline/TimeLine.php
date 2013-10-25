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

namespace Icinga\Timeline;

use \Zend_Config;
use \DateInterval;
use \Zend_View_Interface;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Timeline\TimeEntry;

/**
 * Represents a set of events in a specific time range
 *
 * @see Form
 */
class TimeLine extends Form
{
    /**
     * The range of time represented by this timeline
     *
     * @var TimeRange
     */
    private $range;

    /**
     * The event groups this timeline will display
     *
     * @var array
     */
    private $displayData;

    /**
     * The event groups this timeline uses to calculate forecasts
     *
     * @var array
     */
    private $forecastData;

    /**
     * Whether only the timeline is shown
     *
     * @var bool
     */
    private $hideOuterElements = false;

    /**
     * The maximum diameter each circle can have
     *
     * @var int
     */
    private $circleDiameter = 250;

    /**
     * The unit of a circle's diameter
     *
     * @var string
     */
    private $diameterUnit = 'px';

    /**
     * The base that is used to calculate each circle's diameter
     *
     * @var float
     */
    private $calculationBase;

    /**
     * Set the range of time to represent
     *
     * @param   TimeRange   $range      The range of time to represent
     */
    public function setTimeRange(TimeRange $range)
    {
        $this->range = $range;
    }

    /**
     * Set the groups this timeline should display
     *
     * @param   array   $entries    The TimeEntry objects
     */
    public function setDisplayData(array $entries)
    {
        $this->displayData = $entries;
    }

    /**
     * Set the groups this timeline should use to calculate forecasts
     *
     * @param   array   $entries    The TimeEntry objects
     */
    public function setForecastData(array $entries)
    {
        $this->forecastData = $entries;
    }

    /**
     * Define that only the timeline itself should be rendered
     */
    public function showLineOnly()
    {
        $this->hideOuterElements = true;
    }

    /**
     * Set the maximum diameter each circle can have
     *
     * @param   string  $width  The diameter to set, suffixed with its unit
     * @throws  Exception       If the given diameter is invalid
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
     * Return the chosen interval
     *
     * @return  DateInterval    The chosen interval
     * @throws  Exception       If an invalid interval is given in the current request
     */
    public function getInterval()
    {
        switch ($this->getRequest()->getParam('timelineInterval', '4h'))
        {
            case '4h':
                return new DateInterval('PT4H');
            case '1d':
                return new DateInterval('P1D');
            case '1w':
                return new DateInterval('P1W');
            case '1m':
                return new DateInterval('P1M');
            case '1y':
                return new DateInterval('P1Y');
            default:
                throw new Exception('Invalid interval given in request');
        }
    }

    /**
     * Disable the CSRF token
     */
    public function init()
    {
        $this->setTokenDisabled();
    }

    /**
     * Render the form and timeline to HTML
     *
     * @param   Zend_View_Interface     $view
     * @return  string
     */
    public function render(Zend_View_Interface $view = null)
    {
        $this->buildForm();
        $this->postCreate();
        return parent::render($view);
    }

    /**
     * Add form elements
     */
    public function create()
    {
        if (!$this->hideOuterElements) {
            $this->addElement(
                'select',
                'timelineInterval',
                array(
                    'multiOptions'  => array(
                        '4h'    => t('4 Hours'),
                        '1d'    => t('One day'),
                        '1w'    => t('One week'),
                        '1m'    => t('One month'),
                        '1y'    => t('One year')
                    )
                )
            );
            $this->enableAutoSubmit(array('timelineInterval'));
            $this->setIgnoreChangeDiscarding(false);
        }
    }

    /**
     * Add timeline elements
     */
    private function postCreate()
    {
        $timeline = new Note(
            'timeline',
            array(
                'value' => '<div id="timeline">' . $this->buildTimeline() . '</div>'
            )
        );
        $this->addElement($timeline); // Form::addElement adjusts the element's decorators
        $timeline->clearDecorators();
        $timeline->addDecorator('ViewHelper');

        $legend = new Note(
            'legend',
            array(
                'value' => '<div id="timelineLegend">' . $this->buildLegend() . '</div>'
            )
        );
        $this->addElement($legend);
        $legend->clearDecorators();
        $legend->addDecorator('ViewHelper');
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
    private function buildTimeline()
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

            $timeframeUrl = $this->getRequest()->getBaseUrl() . '/monitoring/list/eventhistory?timestamp<=' .
                            $timeframe->start->getTimestamp() . '&timestamp>=' . $timeframe->end->getTimestamp();
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

        return implode('', $elements);
    }

    /**
     * Return contextless attributes of all available distinct group types
     *
     * Returns an associative array where each key refers to the name
     * and the value to the attributes of a specific group type.
     *
     * @return  array
     */
    private function getGroups()
    {
        $groups = array();
        foreach (array_merge($this->displayData, $this->forecastData) as $group) {
            if (!array_key_exists($group->getName(), $groups)) {
                $groups[$group->getName()] = array(
                    'color'     => $group->getColor(),
                    'weight'    => $group->getWeight()
                );
            }
        }

        return $groups;
    }

    /**
     * Return the circle's diameter for the given amount of events
     *
     * @param   int     $eventCount     The amount of events represented by the circle
     * @return  int
     */
    private function calculateCircleWidth($eventCount)
    {
        if (!isset($this->calculationBase)) {
            $highestValue = max(
                array_map(
                    function ($g) { return $g->getValue(); },
                    array_merge($this->displayData, $this->forecastData)
                )
            );

            $this->calculationBase = $this->getRequest()->getParam('calculationBase', 1);
            while (log($highestValue, $this->calculationBase) > 100) {
                $this->calculationBase += 0.01;
            }

            $this->addElement(
                'hidden',
                'calculationBase',
                array(
                    'value' => $this->calculationBase
                )
            );
        }

        return intval($this->circleDiameter * (log($eventCount, $this->calculationBase) / 100));
    }

    /**
     * Return an extrapolated event count for the given event group
     *
     * @param   TimeEntry   $eventGroup     The event group for which to return an extrapolated event count
     * @param   int         $offset         The amount of intervals to consider for the extrapolation
     * @return  int
     */
    private function extrapolateEventCount(TimeEntry $eventGroup, $offset)
    {
        $start = $eventGroup->getDateTime();
        $end = clone $start;

        for ($i = 0; $i < $offset; $i++) {
            $end->sub($this->range->getInterval());
        }

        $eventCount = 0;
        foreach ($this->displayData as $group) {
            if ($group->getName() === $eventGroup->getName() &&
                $group->getDateTime() <= $start && $group->getDateTime() > $end) {
                $eventCount += $group->getValue();
            }
        }

        $extrapolatedCount = (int) $eventCount / $offset;
        return $extrapolatedCount > $eventGroup->getValue() ? $extrapolatedCount : $eventGroup->getValue();
    }

    /**
     * Return a random generated CSS color hex code
     *
     * @return  string
     */
    private function getRandomCssColor()
    {
        return '#' . str_pad(dechex(rand(256,16777215)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get an appropriate datetime format string for the current interval
     *
     * @return  string
     */
    private function getIntervalFormat()
    {
        $interval = $this->range->getInterval();

        if ($interval->h == 4) {
            return $this->getDateFormat() . ' ' . $this->getTimeFormat();
        } elseif ($interval->d == 1) {
            return $this->getDateFormat();
        } elseif ($interval->d == 7) {
            return '\W\e\ek #W \of Y';
        } elseif ($interval->m == 1) {
            return 'F Y';
        } else { // $interval->y == 1
            return 'Y';
        }
    }

    /**
     * Get the application's global configuration or an empty one
     *
     * @return  Zend_Config
     */
    private function getGlobalConfiguration()
    {
        $config = $this->getConfiguration();
        $global = $config->global;

        if ($global === null) {
            $global = new Zend_Config(array());
        }

        return $global;
    }

    /**
     * Get the user's preferred time format or the application's default
     *
     * @return  string
     */
    private function getTimeFormat()
    {
        $globalConfig = $this->getGlobalConfiguration();
        $preferences = $this->getUserPreferences();
        return $preferences->get('app.timeFormat', $globalConfig->get('timeFormat', 'g:i A'));
    }

    /**
     * Get the user's preferred date format or the application's default
     *
     * @return  string
     */
    private function getDateFormat()
    {
        $globalConfig = $this->getGlobalConfiguration();
        $preferences = $this->getUserPreferences();
        return $preferences->get('app.dateFormat', $globalConfig->get('dateFormat', 'd/m/Y'));
    }
}
