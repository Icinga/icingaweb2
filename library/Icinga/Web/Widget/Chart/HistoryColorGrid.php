<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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

    const ORIENTATION_VERTICAL = 'vertical';

    const ORIENTATION_HORIZONTAL = 'horizontal';

    public $orientation = self::ORIENTATION_VERTICAL;

    private $maxValue = 1;

    private $start = null;

    private $end = null;

    private $data = array();

    private $color;

    public function __construct($color = '#51e551') {
        $this->setColor($color);
    }

    /**
     * Set the displayed data-set
     *
     * @param $data array   The values to display.
     *                        properties for each entry:
     *                          value: The value to display
     *                          caption: The caption on mouse-over
     *                          url: The url to open on click.
     */
    public function setData(array $data)
    {
        $this->data = $data;
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
        $this->start = $this->tsToDateStr($start);
        $this->end = $this->tsToDateStr($end);
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
        if (array_key_exists($day, $this->data)) {
            $entry = $this->data[$day];
            return'<a ' .
                'style="background-color:' . $this->calculateColor($entry['value']) . ';" ' .
                'title="' . $entry['caption'] . '" ' .
                'href="'  . $entry['url'] . '"' .
            '>&nbsp;</a>';
        } else {
            return '<a ' .
                'style="background-color:' . $this->calculateColor(0) . ';" ' .
                'title="No entries for ' . $day . '" ' .
            '></a>';
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
        $html = '<table class="historycolorgrid">';
        $html .= '<tr><th></th>';
        $old = -1;
        foreach ($months as $month) {
            if ($old !== $month) {
                $old = $month;
                $txt = $this->monthName($month);
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
        $weeks = $grid['weeks'];
        $months = $grid['months'];
        $html = '<table class="historycolorgrid">';
        $html .= '<tr>';
        for ($i = 0; $i < 7; $i++) {
            $html .= '<th>' . $this->weekdayName($i) . "</th>";
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
                $txt = $this->monthName($old);
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
        $html = '<tr><td class="weekday">' . $this->weekdayName($weekday) . '</td>';
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
        $start   = strtotime($this->start);
        $year    = intval(date('Y', $start));
        $month   = intval(date('n', $start));
        $day     = intval(date('j', $start));
        $weekday = intval(date('w', $start));

        $date = $this->toDateStr($day, $month, $year);
        $weeks[0][$weekday] = $date;
        $months[0] = $month;
        while ($date !== $this->end) {
            $day++;
            $weekday++;
            if ($weekday > 6) {
                $weekday = 0;
                $weeks[] = array();
                $week++;
                $months[$week] = $month;
            }
            if ($day > cal_days_in_month(CAL_GREGORIAN, $month, $year)) {
                $month++;
                if ($month > 12) {
                    $year++;
                    $month = 1;
                }
                $day = 1;
            }
            $date = $this->toDateStr($day, $month, $year);
            $weeks[$week][$weekday] = $date;
        };
        $months[$week] = $month;
        return array(
            'weeks'  => $weeks,
            'months' => $months
        );
    }

    /**
     * Get the localized month-name for the given month
     *
     * @param integer   $month  The month-number
     *
     * @return string   The
     */
    private function monthName($month)
    {
        $dt = DateTimeFactory::create('2000-' . $month . '-01');
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
        return $sun->format('D');
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
