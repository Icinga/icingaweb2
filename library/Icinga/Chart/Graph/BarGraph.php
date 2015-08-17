<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Graph;

use DOMElement;
use Icinga\Chart\Primitive\Animation;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Styleable;
use Icinga\Chart\Render\RenderContext;

/**
 * Bar graph implementation
 */
class BarGraph extends Styleable implements Drawable
{
    /**
     * The dataset order
     *
     * @var int
     */
    private $order = 0;

    /**
     * The width of the bars.
     *
     * @var int
     */
    private $barWidth = 3;

    /**
     * The dataset to use for this bar graph
     *
     * @var array
     */
    private $dataSet;

    /**
     * The tooltips
     *
     * @var
     */
    private $tooltips;

    /**
     * All graphs
     *
     * @var
     */
    private $graphs;

    /**
     * Create a new BarGraph with the given dataset
     *
     * @param array $dataSet    An array of data points
     * @param int   $order      The graph number displayed by this BarGraph
     * @param array $tooltips   The tooltips to display for each value
     */
    public function __construct(
        array $dataSet,
        array &$graphs,
        $order,
        array $tooltips = null
    ) {
        $this->order = $order;
        $this->dataSet = $dataSet;

        $this->tooltips = $tooltips;
        foreach ($this->tooltips as $value) {
            $ts[] = $value;
        }
        $this->tooltips = $ts;

        $this->graphs = $graphs;
    }

    /**
     * Apply configuration styles from the $cfg
     *
     * @param array $cfg        The configuration as given in the drawBars call
     */
    public function setStyleFromConfig(array $cfg)
    {
        foreach ($cfg as $elem => $value) {
            if ($elem === 'color') {
                $this->setFill($value);
            } elseif ($elem === 'width') {
                $this->setStrokeWidth($value);
            }
        }
    }

    /**
     * Draw a single rectangle
     *
     * @param array     $point          The
     * @param null      $index
     * @param string    $fill           The fill color to use
     * @param           $strokeWidth
     *
     * @return Rect
     */
    private function drawSingleBar($point, $index = null, $fill, $strokeWidth)
    {
        $rect = new Rect($point[0] - ($this->barWidth / 2), $point[1], $this->barWidth, 100 - $point[1]);
        $rect->setFill($fill);
        $rect->setStrokeWidth($strokeWidth);
        $rect->setStrokeColor('black');
        if (isset($index)) {
            $rect->setAttribute('data-icinga-graph-index', $index);
        }
        $rect->setAttribute('data-icinga-graph-type', 'bar');
        $rect->setAdditionalStyle('clip-path: url(#clip);');
        return $rect;
    }

    /**
     * Render this BarChart
     *
     * @param   RenderContext   $ctx    The rendering context to use for drawing
     *
     * @return  DOMElement      $dom    Element
     */
    public function toSvg(RenderContext $ctx)
    {
        $doc = $ctx->getDocument();
        $group = $doc->createElement('g');
        $idx = 0;

        if (count($this->dataSet) > 15) {
            $this->barWidth = 2;
        }
        if (count($this->dataSet) > 25) {
            $this->barWidth = 1;
        }

        foreach ($this->dataSet as $x => $point) {
            // add white background bar, to prevent other bars from altering transparency effects
            $bar = $this->drawSingleBar($point, $idx++, 'white', $this->strokeWidth, $idx)->toSvg($ctx);
            $group->appendChild($bar);

            // draw actual bar
            $bar = $this->drawSingleBar($point, null, $this->fill, $this->strokeWidth, $idx)->toSvg($ctx);
            $bar->setAttribute('class', 'chart-data');
            if (isset($this->tooltips[$x])) {
                $data = array(
                    'label' => isset($this->graphs[$this->order]['label']) ?
                            strtolower($this->graphs[$this->order]['label']) : '',
                    'color' => isset($this->graphs[$this->order]['color']) ?
                            strtolower($this->graphs[$this->order]['color']) : '#fff'
                );
                $format = isset($this->graphs[$this->order]['tooltip'])
                    ? $this->graphs[$this->order]['tooltip'] : null;
                $bar->setAttribute(
                    'title',
                    $this->tooltips[$x]->renderNoHtml($this->order, $data, $format)
                );
                $bar->setAttribute(
                    'data-title-rich',
                    $this->tooltips[$x]->render($this->order, $data, $format)
                );
            }
            $group->appendChild($bar);
        }
        return $group;
    }
}
