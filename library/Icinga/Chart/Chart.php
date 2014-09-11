<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart;

use Exception;
use Icinga\Chart\Legend;
use Icinga\Chart\Palette;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\SVGRenderer;
use Icinga\Exception\IcingaException;

/**
 * Base class for charts, extended by all other Chart classes.
 */
abstract class Chart implements Drawable
{
    protected $align = false;

    /**
     * SVG renderer that handles
     *
     * @var SVGRenderer
     */
    protected $renderer;

    /**
     * Legend to use for this chart
     *
     * @var Legend
     */
    protected $legend;

    /**
     * The style-palette for this chart
     *
     * @var Palette
     */
    protected $palette;

    /**
     * Create a new chart object and create internal objects
     *
     * If you want to extend this class use the init() method as an extension point,
     * as this will be called at the end o fthe construct call
     */
    public function __construct()
    {
        $this->legend = new Legend();
        $this->palette = new Palette();
        $this->init();
    }

    /**
     * Extension point for subclasses, called on __construct
     */
    protected function init()
    {
    }

    /**
     * Extension point for implementing rendering logic
     *
     * This method is called after data validation, but before toSvg is called
     */
    protected function build()
    {
    }

    /**
     * Check if the current dataset has the proper structure for this chart.
     *
     * Needs to be overwritten by extending classes. The default implementation returns false.
     *
     * @return bool True when the dataset is valid, otherwise false
     */
    abstract public function isValidDataFormat();


    /**
     * Disable the legend for this chart
     */
    public function disableLegend()
    {
        $this->legend = null;
    }

    /**
     *
     * Render this graph and return the created SVG
     *
     * @return  string              The SVG created by the SvgRenderer
     *
     * @throws  IcingaException     Thrown wen the dataset is not valid for this graph
     * @see     SVGRenderer::render
     */
    public function render()
    {
        if (!$this->isValidDataFormat()) {
            throw new IcingaException('Dataset for graph doesn\'t have the proper structure');
        }
        $this->build();
        if ($this->align) {
            $this->renderer->preserveAspectRatio();
            $this->renderer->setXAspectRatioAlignment(SVGRenderer::X_ASPECT_RATIO_MIN);
            $this->renderer->setYAspectRatioAlignment(SVGRenderer::Y_ASPECT_RATIO_MIN);
        }
        $this->renderer->getCanvas()->addElement($this);
        return $this->renderer->render();
    }

    /**
     * Align the chart to the top left corner instead of centering it
     *
     * @param bool $align
     */
    public function alignTopLeft ($align = true)
    {
        $this->align = $align;
    }
}
