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

use \DateInterval;
use \Zend_View_Interface;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;

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
     * Return the chosen interval
     *
     * @return  DateInterval    The chosen interval
     * @throws  Exception       If an invalid interval is given in the current request
     */
    public function getInterval()
    {
        switch ($this->getRequest()->getPost('timelineInterval', '4h'))
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
        $circleStyle = 'width:100%;height:90px;border-radius:50%;box-shadow:4px 4px 8px grey;border:2px solid;';
        $labelStyle = 'font-size:12px;margin-top:10px;text-align:center;';
        $titleStyle = 'margin-left:25px;';

        $elements = array();
        foreach ($this->getGroups() as $groupName => $groupInfo) {
            $groupColor = $groupInfo['color'] ? $groupInfo['color'] :
                ('#' . str_pad(dechex(rand(256,16777215)), 6, '0', STR_PAD_LEFT)); // TODO: This should be kind of cached!
            $elements[] = '' .
                '<div style="' . $circleStyle . 'background-color: ' . $groupColor . '"></div>' .
                '<p style="' . $labelStyle . '">' . $groupName . '</p>';
        }

        $legend = '' .
            '<h2 style="' . $titleStyle . '">' . t('Shown event groups') . '</h2>' .
            '<div class="row">' .
            '  <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12">' .
            implode(
                '',
                array_map(
                    function ($e) { return '<div class="col-sm-6 col-xs-3 col-md-2 col-lg-1">' . $e . '</div>'; },
                    $elements
                )
            ) .
            '  </div>' .
            '</div>';

        return $legend;
    }

    /**
     * Build the timeline
     */
    private function buildTimeline()
    {
        return '';
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
        foreach ($this->displayData as $group) {
            if (!array_key_exists($group->getName(), $groups)) {
                $groups[$group->getName()] = array(
                    'color'     => $group->getColor(),
                    'weight'    => $group->getWeight()
                );
            }
        }

        return $groups;
    }
}
