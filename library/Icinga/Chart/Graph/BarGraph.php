<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
     * The width of the bars.
     *
     * @var int
     */
    private $barWidth = 4;

    /**
     * The dataset to use for this bar graph
     *
     * @var array
     */
    private $dataSet;

    /**
     * Create a new BarGraph with the given dataset
     *
     * @param array $dataSet    An array of datapoints
     */
    public function __construct(array $dataSet)
    {
        $this->dataSet = $dataSet;
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
        foreach ($this->dataSet as $point) {
            $rect = new Rect($point[0] - 1, $point[1], 2, 100 - $point[1]);
            $rect->setFill($this->fill);
            $rect->setStrokeWidth($this->strokeWidth);
            $rect->setStrokeColor('black');
            $rect->setAttribute('data-icinga-graph-index', $idx++);
            $rect->setAttribute('data-icinga-graph-type', 'bar');
            $rect->setAdditionalStyle('clip-path: url(#clip);');
            /*$rect->setAnimation(
                new Animation(
                    'y',
                    $ctx->yToAbsolute(100),
                    $ctx->yToAbsolute($point[1]),
                    rand(1, 1.5)/2
                )
            );*/
            $group->appendChild($rect->toSvg($ctx));
        }
        return $group;
    }
}
