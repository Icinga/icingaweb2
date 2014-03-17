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

class PieChart extends AbstractWidget
{
     /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'

    <div data-icinga-component="app/piechart">
        <img class='inlinepie' src="{url}" width={width} height={height}> </img>
    </div>
EOD;

    /**
     * @var Url
     */
    private $url;

    /**
     * The width of the rendered chart
     *
     * @var int The value in percent
     */
    private $width = 25;

    /**
     * The height of the rendered chart
     *
     * @var int The value in perecent
     */
    private $height = 25;

    public function setData($data)
    {
        $this->url->setParam('data', implode(',', $data));
    }

    public function setLabels($labels = null)
    {
        $this->url->setParam('labels', implode(',', $labels));
    }

    public function setColors($colors = null)
    {
        $this->url->setParam('colors', implode(',', $colors));
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function __construct(array $data)
    {
        $this->url = Url::fromPath('svg/piechart.svg');
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
        return $template;
    }
}
