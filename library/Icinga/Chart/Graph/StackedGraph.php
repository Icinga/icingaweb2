<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Graph;

use DOMElement;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Render\RenderContext;

/**
 * Graph implementation that stacks several graphs and displays them in a cumulative way
 */
class StackedGraph implements Drawable
{
    /**
     * All graphs displayed in this stackedgraph
     *
     * @var array
     */
    private $stack = array();

    /**
     * An associative array containing x points as the key and an array of y values as the value
     *
     * @var array
     */
    private $points = array();

    /**
     * Add a graph to this stack and aggregate the values on the fly
     *
     * This modifies the dataset as a side effect
     *
     * @param array $subGraph
     */
    public function addGraph(array &$subGraph)
    {
        foreach ($subGraph['data'] as &$point) {
            $x = $point[0];
            if (!isset($this->points[$x])) {
                $this->points[$x] = 0;
            }
            // store old y-value for displaying the actual (non-aggregated)
            // value in the tooltip
            $point[2] = $point[1];

            $this->points[$x] += $point[1];
            $point[1] = $this->points[$x];
        }
    }

    /**
     * Add a graph to the stack
     *
     * @param $graph
     */
    public function addToStack($graph)
    {
        $this->stack[] = $graph;
    }

    /**
     * Empty the stack
     *
     * @return bool
     */
    public function stackEmpty()
    {
        return empty($this->stack);
    }

    /**
     * Render this stack in the correct order
     *
     * @param   RenderContext $ctx  The context to use for rendering
     *
     * @return  DOMElement          The SVG representation of this graph
     */
    public function toSvg(RenderContext $ctx)
    {
        $group = $ctx->getDocument()->createElement('g');
        $renderOrder = array_reverse($this->stack);
        foreach ($renderOrder as $stackElem) {
            $group->appendChild($stackElem->toSvg($ctx));
        }
        return $group;
    }
}
