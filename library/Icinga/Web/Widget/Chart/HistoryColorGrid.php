<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Chart;

use Icinga\Util\DateTimeFactory;
use Icinga\Util\Color;
use Icinga\Web\Widget\AbstractWidget;
use DateInterval;

/**
 * Display a colored grid that visualizes a set of values for each day
 * on a given time-frame.
 */
class HistoryColorGrid extends AbstractWidget {

    const CAL_GROW_INTO_PAST = 'past';
    const CAL_GROW_INTO_PRESENT = 'present';

    const ORIENTATION_VERTICAL = 'vertical';
    const ORIENTATION_HORIZONTAL = 'horizontal';

    public $weekFlow = self::CAL_GROW_INTO_PAST;
    public $orientation = self::ORIENTATION_VERTICAL;
    public $weekStartMonday = true;

    private $maxValue = 1;

    private $start = null;
    private $end = null;
    private $data = array();
    private $color;
    public $opacity = 1.0;

    public function __construct($color = '#51e551', $start = null, $end = null) {
        $this->setColor($color);
        if (isset($start)) {
            $this->start = $this->tsToDateStr($start);
        }
        if (isset($end)) {
            $this->end = $this->tsToDateStr($end);
        }
    }

    /**
     * Set the displayed data-set
     *
     * @param $events array The history events to display as an array of arrays:
     *                          value: The value to display
     *                          caption: The caption on mouse-over
     *                          url: The url to open on click.
     */
    public function setData(array $events)
    {
        $this->data = $events;
        $start = time();
        $end = time();
        foreach ($this->data as $entry) {
            $entry['value'] = intval($entry['value']);
        }
        foreach ($this->data as $date => $entry) {
            $time = strtotime($date);
            if ($entry['value'] > $this->maxValue) {
                $this->maxValue = $entry['value'];
            }
            if ($time > $end) {
                $end = $time;
            }
            if ($time < $start) {
                $start = $time;
            }
        }
        if (!isset($this->start)) {
            $this->start = $this->tsToDateStr($start);
        }
        if (!isset($this->end)) {
            $this->end = $this->tsToDateStr($end);
        }
    }

    /**
     * Set the used color.
     *
     * @param $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Set the used opacity
     *
     * @param $opacity
     */
    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
    }

    /**
     * Calculate the color to display for the given value.
     *
     * @param $value    integer
     *
     * @return string   The color-string to use for this entry.
     */
    private function calculateColor($value)
    {
        $saturation = $value / $this->maxValue;
        return Color::changeSaturation($this->color, $saturation);
    }

    /**
     * Render the html to display the given $day
     *
     * @param $day  string  The day to display YYYY-MM-DD
     *
     * @return string   The rendered html
     */
    private function renderDay($day)
    {
        if (array_key_exists($day, $this->data) && $this->data[$day]['value'] > 0) {
            $entry = $this->data[$day];
            return '<a ' .
                'style="background-color: ' . $this->calculateColor($entry['value']) . ';'
                    . ' opacity: ' . $this->opacity . ';" ' .
                'aria-label="' . $entry['caption'] . '" ' .
                'title="' . $entry['caption'] . '" ' .
                'href="'  . $entry['url'] . '" ' .
                'data-tooltip-delay="0"' .
            '></a>';
        } else {
            return '<span ' .
                'style="background-color: ' . $this->calculateColor(0) . '; opacity: ' . $this->opacity . ';" ' .
                'title="No entries for ' . $day . '" ' .
            '></span>';
        }
    }

    /**
     * Render the grid with an horizontal alignment.
     *
     * @param array $grid   The values returned from the createGrid function
     *
     * @return string   The rendered html
     */
    private function renderHorizontal($grid)
    {
        $weeks = $grid['weeks'];
        $months = $grid['months'];
        $years = $grid['years'];
        $html = '<table class="historycolorgrid">';
        $html .= '<tr><th></th>';
        $old = -1;
        foreach ($months as $week => $month) {
            if ($old !== $month) {
                $old = $month;
                $txt = $this->monthName($month, $years[$week]);
            } else {
                $txt = '';
            }
            $html .= '<th>' . $txt . '</th>';
        }
        $html .= '</tr>';
        for ($i = 0; $i < 7; $i++) {
            $html .= $this->renderWeekdayHorizontal($i, $weeks);
        }
        $html .= '</table>';
        return $html;
    }

     /**
     * @param $grid
     *
     * @return string
     */
    private function renderVertical($grid)
    {
        $years = $grid['years'];
        $weeks = $grid['weeks'];
        $months = $grid['months'];
        $html = '<table class="historycolorgrid">';
        $html .= '<tr>';
        for ($i = 0; $i < 7; $i++) {
            $html .= '<th>' . $this->weekdayName($this->weekStartMonday ? $i + 1 : $i) . "</th>";
        }
        $html .= '</tr>';
        $old = -1;
        foreach ($weeks as $index => $week) {
            for ($i = 0; $i < 7; $i++) {
                if (array_key_exists($i, $week)) {
                    $html .= '<td>' . $this->renderDay($week[$i]) . '</td>';
                } else {
                    $html .= '<td></td>';
                }
            }
            if ($old !== $months[$index]) {
                $old = $months[$index];
                $txt = $this->monthName($old, $years[$index]);
            } else {
                $txt = '';
            }
            $html .=  '<td class="weekday">' . $txt . '</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Render the row for the given weekday.
     *
     * @param integer   $weekday  The day to render (0-6)
     * @param array     $weeks    The weeks
     *
     * @return string   The formatted table-row
     */
    private function renderWeekdayHorizontal($weekday, &$weeks)
    {
        $html = '<tr><td class="weekday">'
            . $this->weekdayName($this->weekStartMonday ? $weekday + 1 : $weekday)
        . '</td>';
        foreach ($weeks as $week) {
            if (array_key_exists($weekday, $week)) {
                $html .= '<td>' . $this->renderDay($week[$weekday]) . '</td>';
            } else {
                $html .= '<td></td>';
            }
        }
        $html .= '</tr>';
        return $html;
    }



    /**
     * @return array
     */
    private function createGrid()
    {
        $weeks   = array(array());
        $week    = 0;
        $months  = array();
        $years   = array();
        $start   = strtotime($this->start);
        $year    = intval(date('Y', $start));
        $month   = intval(date('n', $start));
        $day     = intval(date('j', $start));
        $weekday = intval(date('w', $start));
        if ($this->weekStartMonday) {
            // 0 => monday, 6 => sunday
            $weekday = $weekday === 0 ? 6 : $weekday - 1;
        }

        $date = $this->toDateStr($day, $month, $year);
        $weeks[0][$weekday] = $date;
        $years[0] = $year;
        $months[0] = $month;
        while ($date !== $this->end) {
            $day++;
            $weekday++;
            if ($weekday > 6) {
                $weekday = 0;
                $weeks[] = array();
                // PRESENT => The last day of week determines the month
                if ($this->weekFlow === self::CAL_GROW_INTO_PRESENT) {
                    $months[$week] = $month;
                    $years[$week] = $year;
                }
                $week++;
            }
            if ($day > date('t', mktime(0, 0, 0, $month, 1, $year))) {
                $month++;
                if ($month > 12) {
                    $year++;
                    $month = 1;
                }
                $day = 1;
            }
            if ($weekday === 0) {
                // PAST => The first day of each week determines the month
                if ($this->weekFlow === self::CAL_GROW_INTO_PAST) {
                    $months[$week] = $month;
                    $years[$week] = $year;
                }
            }
            $date = $this->toDateStr($day, $month, $year);
            $weeks[$week][$weekday] = $date;
        };
        $years[$week] = $year;
        $months[$week] = $month;
        if ($this->weekFlow == self::CAL_GROW_INTO_PAST) {
            return array(
                'weeks'  => array_reverse($weeks),
                'months' => array_reverse($months),
                'years'  => array_reverse($years)
            );
        }
        return array(
            'weeks'  => $weeks,
            'months' => $months,
            'years'  => $years
        );
    }

    /**
     * Get the localized month-name for the given month
     *
     * @param integer   $month  The month-number
     *
     * @return string   The
     */
    private function monthName($month, $year)
    {
        // TODO: find a way to render years without messing up the layout
        $dt = DateTimeFactory::create($year . '-' . $month . '-01');
        return $dt->format('M');
    }

    /**
     * @param $weekday
     *
     * @return string
     */
    private function weekdayName($weekday)
    {
        $sun = DateTimeFactory::create('last Sunday');
        $interval = new DateInterval('P' .  $weekday . 'D');
        $sun->add($interval);
        return substr($sun->format('D'), 0, 2);
    }

    /**
     *
     *
     * @param $timestamp
     *
     * @return bool|string
     */
    private function tsToDateStr($timestamp)
    {
        return date('Y-m-d', $timestamp);
    }

    /**
     * @param $day
     * @param $mon
     * @param $year
     *
     * @return string
     */
    private function toDateStr($day, $mon, $year)
    {
        $day = $day > 9 ? (string)$day : '0' . (string)$day;
        $mon = $mon > 9 ? (string)$mon : '0' . (string)$mon;
        return $year . '-' . $mon . '-' . $day;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (empty($this->data)) {
            return '<div>No entries</div>';
        }
        $grid = $this->createGrid();
        if ($this->orientation === self::ORIENTATION_HORIZONTAL) {
            return $this->renderHorizontal($grid);
        }
        return $this->renderVertical($grid);
    }
}
