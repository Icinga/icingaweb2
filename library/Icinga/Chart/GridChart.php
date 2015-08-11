<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart;

use DOMElement;
use Icinga\Chart\Chart;
use Icinga\Chart\Axis;
use Icinga\Chart\Graph\BarGraph;
use Icinga\Chart\Graph\LineGraph;
use Icinga\Chart\Graph\StackedGraph;
use Icinga\Chart\Graph\Tooltip;
use Icinga\Chart\Primitive\Canvas;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Path;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Unit\AxisUnit;

/**
 * Base class for grid based charts.
 *
 * Allows drawing of Line and Barcharts. See the graphing documentation for further details.
 *
 * Example:
 * <pre>
 * <code>
 * $this->chart = new GridChart();
 * $this->chart->setAxisLabel("X axis label", "Y axis label");
 * $this->chart->setXAxis(Axis::CalendarUnit());
 * $this->chart->drawLines(
 * array(
 *      'data'  => array(
 *          array(time()-7200, 10),array(time()-3620, 30), array(time()-1800, 15), array(time(), 92))
 *      )
 * );
 * </code>
 * </pre>
 */
class GridChart extends Chart
{
    /**
     * Internal identifier for Line Chart elements
     */
    const TYPE_LINE = "LINE";

    /**
     * Internal identifier fo Bar Chart elements
     */
    const TYPE_BAR  = "BAR";

    /**
     * Internal array containing all elements to be drawn in the order they are drawn
     *
     * @var array
     */
    private $graphs = array();

    /**
     * An associative array containing all axis of this Chart in the  "name" => Axis() form.
     *
     * Currently only the 'default' axis is really supported
     *
     * @var array
     */
    private $axis = array();

    /**
     * An associative array containing all StackedGraph objects used for cumulative graphs
     *
     * The array key is the 'stack' value given in the graph definitions
     *
     * @var array
     */
    private $stacks = array();

    /**
     * An associative array containing all Tooltips used to render the titles
     *
     * Each tooltip represents the summary for all y-values of a certain x-value
     * in the grid chart
     *
     * @var Tooltip
     */
    private $tooltips = array();

    public function __construct()
    {
        $this->title = t('Grid Chart');
        $this->description = t('Contains data in a bar or line chart.');
        parent::__construct();
    }

    /**
     * Check if the current dataset has the proper structure for this chart.
     *
     * Needs to be overwritten by extending classes. The default implementation returns false.
     *
     * @return bool True when the dataset is valid, otherwise false
     */
    public function isValidDataFormat()
    {
        foreach ($this->graphs as $values) {
            foreach ($values as $value) {
                if (!isset($value['data']) || !is_array($value['data'])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Calls Axis::addDataset for every graph added to this GridChart
     *
     * @see Axis::addDataset
     */
    private function configureAxisFromDatasets()
    {
        foreach ($this->graphs as $axis => &$graphs) {
            $axisObj = $this->axis[$axis];
            foreach ($graphs as &$graph) {
                $axisObj->addDataset($graph);
            }
        }
    }

    /**
     * Add an arbitrary number of lines to be drawn
     *
     * Refer to the graphs.md for a detailed list of allowed attributes
     *
     * @param   array $axis,... The line definitions to draw
     *
     * @return  $this            Fluid interface
     */
    public function drawLines(array $axis)
    {
        $this->draw(self::TYPE_LINE, func_get_args());
        return $this;
    }

    /**
     * Add arbitrary number of bars to be drawn
     *
     * Refer to the graphs.md for a detailed list of allowed attributes
     *
     * @param   array $axis
     * @return  $this
     */
    public function drawBars(array $axis)
    {
        $this->draw(self::TYPE_BAR, func_get_args());
        return $this;
    }

    /**
     * Generic method for adding elements to the drawing stack
     *
     * @param string $type The type of the element to draw (see TYPE_ constants in this class)
     * @param array $data The data given to the draw call
     */
    private function draw($type, $data)
    {
        $axisName = 'default';
        if (is_string($data[0])) {
            $axisName =  $data[0];
            array_shift($data);
        }
        foreach ($data as &$graph) {
            $graph['graphType'] = $type;
            if (isset($graph['stack'])) {
                if (!isset($this->stacks[$graph['stack']])) {
                    $this->stacks[$graph['stack']] = new StackedGraph();
                }
                $this->stacks[$graph['stack']]->addGraph($graph);
                $graph['stack'] = $this->stacks[$graph['stack']];
            }

            if (!isset($graph['color'])) {
                $colorType = isset($graph['palette']) ?  $graph['palette'] : Palette::NEUTRAL;
                $graph['color'] = $this->palette->getNext($colorType);
            }
            $this->graphs[$axisName][] = $graph;
            if ($this->legend) {
                $this->legend->addDataset($graph);
            }
        }
        $this->initTooltips($data);
    }


    private function initTooltips($data)
    {
        foreach ($data as &$graph) {
            foreach  ($graph['data'] as $x => $point) {
                if (!array_key_exists($x, $this->tooltips)) {
                    $this->tooltips[$x] = new Tooltip(
                        array(
                            'color' => $graph['color'],

                        )

                    );
                }
                $this->tooltips[$x]->addDataPoint($point);
            }
        }
    }

    /**
     * Set the label for the x and y axis
     *
     * @param   string $xAxisLabel  The label to use for the x axis
     * @param   string $yAxisLabel  The label to use for the y axis
     * @param   string $axisName    The name of the axis, for now 'default'
     *
     * @return  $this                Fluid interface
     */
    public function setAxisLabel($xAxisLabel, $yAxisLabel, $axisName = 'default')
    {
        $this->axis[$axisName]->setXLabel($xAxisLabel)->setYLabel($yAxisLabel);
        return $this;
    }

    /**
     * Set the AxisUnit to use for calculating the values of the x axis
     *
     * @param   AxisUnit    $unit       The unit for the x axis
     * @param   string      $axisName   The name of the axis to set the label for, currently only 'default'
     *
     * @return  $this                    Fluid interface
     */
    public function setXAxis(AxisUnit $unit, $axisName = 'default')
    {
        $this->axis[$axisName]->setUnitForXAxis($unit);
        return $this;
    }

    /**
     * Set the AxisUnit to use for calculating the values of the y axis
     *
     * @param   AxisUnit    $unit       The unit for the y axis
     * @param   string      $axisName   The name of the axis to set the label for, currently only 'default'
     *
     * @return  $this                    Fluid interface
     */
    public function setYAxis(AxisUnit $unit, $axisName = 'default')
    {
        $this->axis[$axisName]->setUnitForYAxis($unit);
        return $this;
    }

    /**
     * Pre-render setup of the axis
     *
     * @see Chart::build
     */
    protected function build()
    {
        $this->configureAxisFromDatasets();
    }

    /**
     * Initialize the renderer and overwrite it with an 2:1 ration renderer
     */
    protected function init()
    {
        $this->renderer = new SVGRenderer(100, 100);
        $this->setAxis(Axis::createLinearAxis());
    }

    /**
     * Overwrite the axis to use
     *
     * @param   Axis    $axis The new axis to use
     * @param   string  $name The name of the axis, currently only 'default'
     *
     * @return  $this          Fluid interface
     */
    public function setAxis(Axis $axis, $name = 'default')
    {
        $this->axis = array($name => $axis);
        return $this;
    }

    /**
     * Add an axis to this graph (not really supported right now)
     *
     * @param   Axis    $axis The axis object to add
     * @param   string  $name The name of the axis
     *
     * @return  $this          Fluid interface
     */
    public function addAxis(Axis $axis, $name)
    {
        $this->axis[$name] = $axis;
        return $this;
    }

    /**
     * Set minimum values for the x and y axis.
     *
     * Setting null to an axis means this will use a value determined by the dataset
     *
     * @param   int     $xMin       The minimum value for the x axis or null to use a dynamic value
     * @param   int     $yMin       The minimum value for the y axis or null to use a dynamic value
     * @param   string  $axisName   The name of the axis to set the minimum, currently only 'default'
     *
     * @return  $this                Fluid interface
     */
    public function setAxisMin($xMin = null, $yMin = null, $axisName = 'default')
    {
        $this->axis[$axisName]->setXMin($xMin)->setYMin($yMin);
        return $this;
    }

    /**
     * Set maximum values for the x and y axis.
     *
     * Setting null to an axis means this will use a value determined by the dataset
     *
     * @param   int     $xMax       The maximum value for the x axis or null to use a dynamic value
     * @param   int     $yMax       The maximum value for the y axis or null to use a dynamic value
     * @param   string  $axisName   The name of the axis to set the maximum, currently only 'default'
     *
     * @return  $this                Fluid interface
     */
    public function setAxisMax($xMax = null, $yMax = null, $axisName = 'default')
    {
        $this->axis[$axisName]->setXMax($xMax)->setYMax($yMax);
        return $this;
    }

    /**
     * Render this GridChart to SVG
     *
     * @param   RenderContext $ctx The context to use for rendering
     *
     * @return  DOMElement
     */
    public function toSvg(RenderContext $ctx)
    {
        $outerBox = new Canvas('outerGraph', new LayoutBox(0, 0, 100, 100));
        $innerBox = new Canvas('graph', new LayoutBox(0, 0, 95, 90));

        $maxPadding = array(0,0,0,0);
        foreach ($this->axis as $axis) {
            $padding = $axis->getRequiredPadding();
            for ($i=0; $i < count($padding); $i++) {
                $maxPadding[$i] = max($maxPadding[$i], $padding[$i]);
            }
            $innerBox->addElement($axis);
        }
        $this->renderGraphContent($innerBox);

        $innerBox->getLayout()->setPadding($maxPadding[0], $maxPadding[1], $maxPadding[2], $maxPadding[3]);
        $this->createContentClipBox($innerBox);

        $outerBox->addElement($innerBox);
        if ($this->legend) {
            $outerBox->addElement($this->legend);
        }
        return $outerBox->toSvg($ctx);
    }

    /**
     * Create a clip box that defines which area of the graph is drawable and adds it to the graph.
     *
     * The clipbox has the id '#clip' and can be used in the clip-mask element
     *
     * @param Canvas $innerBox The inner canvas of the graph to add the clip box to
     */
    private function createContentClipBox(Canvas $innerBox)
    {
        $clipBox = new Canvas('clip', new LayoutBox(0, 0, 100, 100));
        $clipBox->toClipPath();
        $innerBox->addElement($clipBox);
        $rect = new Rect(0.1, 0, 100, 99.9);
        $clipBox->addElement($rect);
    }

    /**
     * Render the content of the graph, i.e. the draw stack
     *
     * @param Canvas $innerBox The inner canvas of the graph to add the content to
     */
    private function renderGraphContent(Canvas $innerBox)
    {
        foreach ($this->graphs as $axisName => $graphs) {
            $axis = $this->axis[$axisName];
            $graphObj = null;
            foreach ($graphs as $dataset => $graph) {
                // determine the type and create a graph object for it
                switch ($graph['graphType']) {
                    case self::TYPE_BAR:
                        $graphObj = new BarGraph(
                            $axis->transform($graph['data']),
                            $graphs,
                            $dataset,
                            $this->tooltips
                        );
                        break;
                    case self::TYPE_LINE:
                        $graphObj = new LineGraph(
                            $axis->transform($graph['data']),
                            $graphs,
                            $dataset,
                            $this->tooltips
                        );
                        break;
                    default:
                        continue;
                }
                $el = $this->setupGraph($graphObj, $graph);
                if ($el) {
                    $innerBox->addElement($el);
                }
            }
        }
    }

    /**
     * Setup the provided Graph type
     *
     * @param   mixed $graphObject  The graph class, needs the setStyleFromConfig method
     * @param   array $graphConfig  The configration array of the graph
     *
     * @return  mixed               Either the graph to be added or null if the graph is not directly added
     *                              to the document (e.g. stacked graphs are added by
     *                              the StackedGraph Composite object)
     */
    private function setupGraph($graphObject, array $graphConfig)
    {
        $graphObject->setStyleFromConfig($graphConfig);
        // When in a stack return the StackedGraph object instead of the graphObject
        if (isset($graphConfig['stack'])) {
            $graphConfig['stack']->addToStack($graphObject);
            if (!$graphConfig['stack']->stackEmpty()) {
                return $graphConfig['stack'];
            }
            // return no object when the graph should not be rendered
            return null;
        }
        return $graphObject;
    }
}
