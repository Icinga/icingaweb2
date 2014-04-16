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

namespace Icinga\Web\Widget\Chart;

use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\Url;

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
    /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'
    <div class="inlinepie">
    <img
        title="{title}" src="{url}" style="width: {width}px; height: {height}px; {style}"
        data-icinga-colors="{colors}" data-icinga-values="{data}"
    ></img>
    </div>
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
    private $width = 31;

    /**
     * The height of the rendered chart
     *
     * @var int The value in px
     */
    private $height = 31;

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
     * @var
     */
    private $data;

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
        $this->url->setParam('labels', implode(',', $labels));
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
     * @param array $data   The data displayed by the slices
     * @param array $colors The colors displayed by the slices
     */
    public function __construct(array $data, array $colors = null)
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
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @return string
     */
    public function render()
    {
        $template = $this->template;
        $template = preg_replace('{{url}}', $this->url, $template);
        $template = preg_replace('{{width}}', $this->width, $template);
        $template = preg_replace('{{height}}', $this->height, $template);
        $template = preg_replace('{{title}}', $this->title, $template);
        $template = preg_replace('{{style}}', $this->style, $template);
        $template = preg_replace('{{data}}', implode(',', $this->data), $template);
        $template = preg_replace('{{colors}}', implode(',', $this->colors), $template);
        return $template;
    }
}

