<?php
// @codeCoverageIgnoreStart
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Chart;

use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\Url;
use Icinga\Util\Format;
use Icinga\Logger\Logger;

/**
 * A SVG-PieChart intended to be displayed as a small icon next to labels, to offer a better visualization of the
 * shown data
 *
 * NOTE: When InlinePies are shown in a dynamically loaded view, like the side-bar or in the dashboard, the SVGs will
 * be replaced with a jQuery-Sparkline to save resources @see loader.js
 *
 * @package Icinga\Web\Widget\Chart
 */
class InlinePie extends AbstractWidget
{
    const NUMBER_FORMAT_TIME  = 'time';
    const NUMBER_FORMAT_BYTES = 'bytes';
    const NUMBER_FORMAT_RATIO = 'ratio';
        
    /**
     * The template string used for rendering this widget
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'
    <span
        class="sparkline"
        sparkTitle="{title}"
        sparkWidth="{width}"
        sparkHeight="{height}"
        sparkBorderWidth="{borderWidth}"
        sparkBorderColor="{borderColor}"
        sparkTooltipChartTitle="{title}"
        style="{style}"
        labels="{labels}"
        formatted="{formatted}"
        values="{data}"
        tooltipFormat="{tooltipFormat}"
        sparkSliceColors="[{colors}]"
        sparkType="pie"></span>
    <noscript>
    <img class="inlinepie"
        title="{title}" src="{url}" style="width: {width}px; height: {height}px; {style}"
        data-icinga-colors="{colors}" data-icinga-values="{data}"
    />
    </noscript>
EOD;

    /**
     * @var Url
     */
    private $url;

    /**
     * The colors used to display the slices of this pie-chart.
     *
     * @var array
     */
    private $colors = array('#44bb77', '#ffaa44', '#ff5566', '#ddccdd');

    /**
     * The width of the rendered chart
     *
     * @var int The value in px
     */
    private $width = 28;

    /**
     * The height of the rendered chart
     *
     * @var int The value in px
     */
    private $height = 28;

    /**
     * PieChart border width
     *
     * @var float
     */
    private $borderWidth = 1.25;

    /**
     * The color of the border
     *
     * @var string
     */
    private $borderColor = '#888';

    /**
     * The title of the chart
     *
     * @var string
     */
    private $title = '';

    /**
     * The style for the HtmlElement
     *
     * @var string
     */
    private $style = '';

    /**
     * The data displayed by the pie-chart
     *
     * @var array
     */
    private $data;

    /**
     * The labels to display for each data set
     *
     * @var array
     */
    private $labels;

    /**
     * The format string used to display tooltips
     *
     * @var string
     */
    private $tooltipFormat = '<b>{{title}}</b></br> {{label}}: {{formatted}} ({{percent}}%)';
    
    /**
     * The number format used to render numeric values in tooltips
     *
     * @var array
     */
    private $format = self::NUMBER_FORMAT_BYTES;

    /**
     * Set the data to be displayed.
     *
     * @param $data array
     */
    public function setData(array $data)
    {
        $this->data = $data;
        $this->url->setParam('data', implode(',', $data));
    }

    /**
     * The labels to be displayed in the pie-chart
     *
     * @param null $labels
     *
     * @return $this
     */
    public function setLabels($labels = null)
    {
        if ($labels != null) {
            $this->url->setParam('labels', implode(',', $labels));
        } else {
            $this->url->removeKey('labels');
        }
        $this->labels = $labels;
        return $this;
    }

    /**
     * Set the colors used by the slices of the pie chart.
     *
     * @param array $colors
     */
    public function setColors(array $colors = null)
    {
        $this->colors = $colors;
        if (isset($colors)) {
            $this->url->setParam('colors', implode(',', $colors));
        } else {
            $this->url->setParam('colors', null);
        }
    }

    /**
     * Set the used number format
     *
     * @param $format   string  'bytes' or 'time'
     */
    public function setNumberFormat($format)
    {
        $this->format = $format;
    }
    
    /**
     * A format string used to render the content of the piechar tooltips
     *
     * Placeholders using curly braces '{FOO}' are replace with their specific values. Available
     * values are:
     * <ul>
     *      <li><b>label</b>:     The description for the current value </li>
     *      <li><b>formatted</b>: A string representing the formatted value </li>
     *      <li><b>value</b>:     The raw (non-formatted) value used to render the piechart </li>
     *      <li><b>percent</b>:   The percentage of the current value </li>
     * </ul>
     * Note: Changes will only affect JavaScript sparklines and not the SVG charts used for fallback
     */
    public function setTooltipFormat($format)
    {
        $this->tooltipFormat = $format;
    }

    /**
     * @param $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Set the border width of the pie chart
     *
     * @param float $width    Width in px
     */
    public function setBorderWidth($width)
    {
        $this->borderWidth = $width;
    }

    /**
     * Set the color of the pie chart border
     *
     * @param string $col   The color string
     */
    public function setBorderColor($col)
    {
        $this->borderColor = $col;
    }

    /**
     * @param $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Set the styling of the created HtmlElement
     *
     * @param $style
     */
    public function setStyle($style)
    {
        $this->style = $style;
    }

    /**
     * Set the title of the created HtmlElement
     *
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Create a new InlinePie
     *
     * @param array  $data   The data displayed by the slices
     * @param array  $colors The colors displayed by the slices
     * @param array  $labels The labels to display for each slice
     * @param string $unit   The number format
     */
    public function __construct(array $data, array $colors = null, array $labels = null, $unit = self::NUMBER_FORMAT_BYTES)
    {
        $this->url = Url::fromPath('svg/chart.php');
        if (array_key_exists('data', $data)) {
            $this->data = $data['data'];
            if (array_key_exists('labels', $data)) {
                $this->labels = $data['labels'];
            }
            if (array_key_exists('colors', $data)) {
                $this->colors = $data['colors'];
            }
        } else {
            $this->setData($data);
        }
        if (isset($colors)) {
            $this->setColors($colors);
        } else {
            $this->setColors($this->colors);
        }
    }

    /**
     * Create a serialization containing the current label array
     *
     * @return string   A serialized array of labels
     */
    private function createLabelString () {
        return isset($this->labels) && is_array($this->labels) ? implode(',', $this->labels) : '';
    }

    /**
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @return string
     */
    public function render()
    {
        $template = $this->template;
        $template = preg_replace('{{url}}', $this->url, $template);

        // style
        $template = preg_replace('{{width}}', $this->width, $template);
        $template = preg_replace('{{height}}', $this->height, $template);
        $template = preg_replace('{{title}}', $this->title, $template);
        $template = preg_replace('{{style}}', $this->style, $template);
        $template = preg_replace('{{colors}}', implode(',', $this->colors), $template);
        $template = preg_replace('{{borderWidth}}', $this->borderWidth, $template);
        $template = preg_replace('{{borderColor}}', $this->borderColor, $template);

        // values
        $data = array();
        foreach ($this->data as $dat) {
            // Locale-ignorant string cast:
            $data[] = sprintf('%F', $dat);
        }
        $formatted = array();
        foreach ($this->data as $key => $value) {
            $formatted[$key] = $this->formatValue($value);
        }
        $template = preg_replace('{{data}}', implode(',', $data), $template);
        $template = preg_replace('{{formatted}}', implode(',', $formatted), $template);
        $template = preg_replace('{{labels}}', $this->createLabelString(), $template);
        $template = preg_replace('{{tooltipFormat}}', $this->tooltipFormat, $template);
        return $template;
    }

    /**
     * Format the given value depending on the current value of numberFormat
     *
     * @param   float   $value  The value to format
     *
     * @return  string          The formatted value
     */
    private function formatValue($value)
    {
        if ($this->format === self::NUMBER_FORMAT_BYTES) {
            return Format::bytes($value);
        } else if ($this->format === self::NUMBER_FORMAT_TIME) {
            return Format::duration($value);
        } else if ($this->format === self::NUMBER_FORMAT_RATIO) {
            return $value;
        } else {
            Logger::warning('Unknown format string "' . $this->format . '" for InlinePie, value not formatted.');
            return $value;
        }
    }
}
// @codeCoverageIgnoreEnd
