<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Chart;

use Icinga\Chart\PieChart;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use Icinga\Util\StringHelper;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\Url;
use Icinga\Util\Format;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use stdClass;

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

    public static $colorsHostStates = array(
        '#44bb77', // up
        '#ff99aa', // down
        '#cc77ff', // unreachable
        '#77aaff'  // pending
    );

    public static $colorsHostStatesHandledUnhandled = array(
        '#44bb77', // up
        '#44bb77',
        '#ff99aa', // down
        '#ff5566',
        '#cc77ff', // unreachable
        '#aa44ff',
        '#77aaff', // pending
        '#77aaff'
    );

    public static $colorsServiceStates = array(
        '#44bb77', // Ok
        '#ffaa44', // Warning
        '#ff99aa', // Critical
        '#aa44ff', // Unknown
        '#77aaff'  // Pending
    );

    public static $colorsServiceStatesHandleUnhandled = array(
        '#44bb77', // Ok
        '#44bb77',
        '#ffaa44', // Warning
        '#ffcc66',
        '#ff99aa', // Critical
        '#ff5566',
        '#cc77ff', // Unknown
        '#aa44ff',
        '#77aaff', // Pending
        '#77aaff'
    );

    /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template = '<div class="inline-pie {class}">{svg}</div>';

    /**
     * The colors used to display the slices of this pie-chart.
     *
     * @var array
     */
    private $colors = array('#049BAF', '#ffaa44', '#ff5566', '#ddccdd');

    /**
     * The title of the chart
     *
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $size = 16;

    /**
     * The data displayed by the pie-chart
     *
     * @var array
     */
    private $data;

    /**
     * @var
     */
    private $class = '';

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

        return $this;
    }

    /**
     * Set the size of the inline pie
     *
     * @param int $size     Sets both, the height and width
     *
     * @return $this
     */
    public function setSize($size = null)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Do not display the NoScript fallback html
     *
     * @deprecated noop
     */
    public function disableNoScript()
    {
    }

    /**
     * Set the class to define the
     *
     * @param  $class
     *
     * @return $this
     */
    public function setSparklineClass($class)
    {
        $this->class = $class;

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

        return $this;
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
        $this->title = $this->view()->escape($title);

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
        $this->setTitle($title);

        if (array_key_exists('data', $data)) {
            $this->data = $data['data'];
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
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @return string
     */
    public function render()
    {
        $pie = new PieChart();
        $pie->alignTopLeft();
        $pie->disableLegend();
        $pie->drawPie([
            'data'      => $this->data,
            'colors'    => $this->colors
        ]);

        if ($this->view()->layout()->getLayout() === 'pdf') {
            try {
                $png = $pie->toPng($this->size, $this->size);
                return '<img class="inlinepie" src="data:image/png;base64,' . base64_encode($png) . '" />';
            } catch (IcingaException $_) {
                return '';
            }
        }

        $pie->title = $this->title;
        $pie->description = $this->title;

        $template = $this->template;
        $template = str_replace('{class}', $this->class, $template);
        $template = str_replace('{svg}', $pie->render(), $template);

        return $template;
    }

    public static function createFromStateSummary(stdClass $states, $title, array $colors)
    {
        $handledUnhandledStates = [];
        foreach ($states as $key => $value) {
            if (StringHelper::endsWith($key, '_handled') || StringHelper::endsWith($key, '_unhandled')) {
                $handledUnhandledStates[$key] = $value;
            }
        }

        $chart = new self(array_values($handledUnhandledStates), $title, $colors);

        return $chart
            ->setSize(50)
            ->setTitle('')
            ->setSparklineClass('sparkline-multi');
    }
}
