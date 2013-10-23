<?php
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

namespace Icinga\Chart;

use \Exception;
use \Icinga\Chart\Legend;
use \Icinga\Chart\Palette;
use \Icinga\Chart\Primitive\Drawable;
use \Icinga\Chart\SVGRenderer;

/**
 * Base class for charts, extended by all other Chart classes.
 */
abstract class Chart implements Drawable
{

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
     * @return  string      The SVG created by the SvgRenderer
     *
     * @throws  Exception   Thrown wen the dataset is not valid for this graph
     * @see     SVGRenderer::render
     */
    public function render()
    {
        if (!$this->isValidDataFormat()) {
            throw new Exception('Dataset for graph doesn\'t have the proper structure');
        }
        $this->build();

        $this->renderer->getCanvas()->addElement($this);
        return $this->renderer->render();
    }
}
