<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Chart;

use Exception;
use Iterator;
use Icinga\Chart\Legend;
use Icinga\Chart\Palette;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\SVGRenderer;

/**
 * Base class for charts
 */
abstract class Chart implements Drawable
{
    private $xAxis;

    private $yAxis;

    /**
     * Series to plot
     *
     * @var array
     */
    protected $dataSets = array();

    /**
     * SVG renderer
     *
     * @var SVGRenderer
     */
    protected $renderer;

    /**
     * Legend to use for this chart
     *
     * @var Legend
     */
    public $legend;

    /**
     * The style-palette for this chart
     *
     * @var Palette
     */
    public $palette;

    /**
     * Create a new chart
     *
     * See self::isValidDataFormat() for more information on how each series need to be structured
     *
     * @param   array   $dataSet, ...   unlimited number of series to plot
     *
     * @see     self::isValidDataFormat()
     */
    public function __construct()
    {
        $this->legend = new Legend();
        $this->palette = new Palette();
        $this->renderer = new SVGRenderer(2,1);
        $this->init();
    }

    protected function init()
    {

    }

    /**
     * Set up the legend for this chart
     */
    protected function setupLegend()
    {

    }

    /**
     * Set up the elements for this chart
     */
    protected function build()
    {

    }

    /**
     * Check if the current dataset has the proper structure for this chart
     *
     * Needs to be overwritten by extending classes. The default implementation returns false.
     *
     * @return  bool                    Whether the dataset is valid or not
     */
    public function isValidDataFormat()
    {
        return false;
    }

    /**
     * Disable the legend for this chart
     */
    public function disableLegend()
    {
        $this->legend = null;
    }


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
