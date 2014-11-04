<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Chart;

use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\Url;
use Icinga\Util\Format;
use Icinga\Application\Logger;

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
    const NUMBER_FORMAT_NONE = 'none';
    const NUMBER_FORMAT_TIME = 'time';
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
        hideEmptyLabel={hideEmptyLabel}
        values="{data}"
        tooltipFormat="{tooltipFormat}"
        sparkSliceColors="[{colors}]"
        sparkType="pie"></span>
    <noscript>
    <img class="inlinepie"
        title="{title}" src="{url}" style="position: relative; top: 10px; width: {width}px; height: {height}px; {style}"
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
    private $borderWidth = 0;

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
    private $title;

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
    private $labels = array();

    /**
     * If the tooltip for the "empty" area should be hidden
     *
     * @var bool
     */
    private $hideEmptyLabel = false;

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
    private $format = self::NUMBER_FORMAT_NONE;

    /**
     * Set if the tooltip for the empty area should be hidden
     *
     * @param   bool $hide    Whether to hide the empty area
     *
     * @return  $this
     */
    public function setHideEmptyLabel($hide = true)
    {
        $this->hideEmptyLabel = $hide;
        return $this;
    }

    /**
     * Set the data to be displayed.
     *
     * @param   $data array
     *
     * @return  $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        $this->url->setParam('data', implode(',', $data));
        return $this;
    }

    /**
     * The labels to be displayed in the pie-chart
     *
     * @param   mixed $label     The label of the displayed value, or null for no labels
     *
     * @return  $this
     */
    public function setLabel($label)
    {
        if (is_array($label)) {
            $this->url->setParam('labels', implode(',', array_keys($label)));
        } elseif ($label != null) {
            $labelArr =  array($label, $label, $label, '');
            $this->url->setParam('labels', implode(',', $labelArr));
            $label = $labelArr;
        } else {
            $this->url->removeKey('labels');
        }
        $this->labels = $label;
        return $this;
    }

    /**
     * Set the colors used by the slices of the pie chart.
     *
     * @param   array $colors
     *
     * @return  $this
     */
    public function setColors(array $colors = null)
    {
        $this->colors = $colors;
        if (isset($colors)) {
            $this->url->setParam('colors', implode(',', $colors));
        } else {
            $this->url->setParam('colors', null);
        }
        return $this;
    }

    /**
     * Set the used number format
     *
     * @param   $format   string  'bytes' or 'time'
     *
     * @return  $this
     */
    public function setNumberFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * A format string used to render the content of the piechart tooltips
     *
     * Placeholders using curly braces '{FOO}' are replace with their specific values. The format
     * String may contain HTML-Markup. The available replaceable values are:
     * <ul>
     *      <li><b>label</b>:     The description for the current value </li>
     *      <li><b>formatted</b>: A string representing the formatted value </li>
     *      <li><b>value</b>:     The raw (non-formatted) value used to render the piechart </li>
     *      <li><b>percent</b>:   The percentage of the current value </li>
     * </ul>
     * Note: Changes will only affect JavaScript sparklines and not the SVG charts used for fallback
     *
     * @param   $format
     *
     * @return  $this
     */
    public function setTooltipFormat($format)
    {
        $this->tooltipFormat = $format;
        return $this;
    }

    /**
     * Set the height
     *
     * @param   $height
     *
     * @return  $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Set the border width of the pie chart
     *
     * @param   float $width    Width in px
     *
     * @return  $this
     */
    public function setBorderWidth($width)
    {
        $this->borderWidth = $width;
        return $this;
    }

    /**
     * Set the color of the pie chart border
     *
     * @param   string $col   The color string
     *
     * @return  $this
     */
    public function setBorderColor($col)
    {
        $this->borderColor = $col;
    }

    /**
     * Set the width
     *
     * @param   $width
     *
     * @return  $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Set the styling of the created HtmlElement
     *
     * @param   string $style
     *
     * @return  $this
     */
    public function setStyle($style)
    {
        $this->style = $style;
    }

    /**
     * Set the title of the displayed Data
     *
     * @param   string $title
     *
     * @return  $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Create a new InlinePie
     *
     * @param array  $data      The data displayed by the slices
     * @param string $title     The title of this Pie
     * @param array  $colors    An array of RGB-Color values to use
     */
    public function __construct(array $data, $title, $colors = null)
    {
        $this->title = $title;
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
    private function createLabelString ()
    {
        $labels = $this->labels;
        foreach ($labels as $key => $label) {
            $labels[$key] = str_replace('|', '', $label);
        }
        return isset($this->labels) && is_array($this->labels) ? implode('|', $this->labels) : '';
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
        $template = str_replace('{url}', $this->url, $template);

        // style
        $template = str_replace('{width}', $this->width, $template);
        $template = str_replace('{height}', $this->height, $template);
        $template = str_replace('{title}', htmlspecialchars($this->title), $template);
        $template = str_replace('{style}', $this->style, $template);
        $template = str_replace('{colors}', implode(',', $this->colors), $template);
        $template = str_replace('{borderWidth}', $this->borderWidth, $template);
        $template = str_replace('{borderColor}', $this->borderColor, $template);
        $template = str_replace('{hideEmptyLabel}', $this->hideEmptyLabel ? 'true' : 'false', $template);

        // Locale-ignorant string cast. Please. Do. NOT. Remove. This. Again.
        // Problem is that implode respects locales when casting floats. This means
        // that implode(',', array(1.1, 1.2)) would read '1,1,1,2'.
        $data = array();
        foreach ($this->data as $dat) {
            $data[] = sprintf('%F', $dat);
        }

        // values
        $formatted = array();
        foreach ($this->data as $key => $value) {
            $formatted[$key] = $this->formatValue($value);
        }
        $template = str_replace('{data}', htmlspecialchars(implode(',', $data)), $template);
        $template = str_replace('{formatted}', htmlspecialchars(implode('|', $formatted)), $template);
        $template = str_replace('{labels}', htmlspecialchars($this->createLabelString()), $template);
        $template = str_replace('{tooltipFormat}', $this->tooltipFormat, $template);
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
        if ($this->format === self::NUMBER_FORMAT_NONE) {
            return (string)$value;
        } elseif ($this->format === self::NUMBER_FORMAT_BYTES) {
            return Format::bytes($value);
        } elseif ($this->format === self::NUMBER_FORMAT_TIME) {
            return Format::duration($value);
        } elseif ($this->format === self::NUMBER_FORMAT_RATIO) {
            return $value;
        } else {
            Logger::warning('Unknown format string "' . $this->format . '" for InlinePie, value not formatted.');
            return $value;
        }
    }
}
