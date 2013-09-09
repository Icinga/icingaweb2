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

use Icinga\Chart\Palette;
use Icinga\Chart\Primitive\Canvas;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Text;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\SVGRenderer;

class Legend implements Drawable
{
    private $internalCtr = 0;
    /**
     *
     * Content of this legend
     *
     * @var array
     */
    private $dataset = array();

    /**
     * Maximum space this legend can take in percent
     *
     * Is intelligently applied depending on the location of the legend.
     *
     * @var float
     */
    public $maxVolume = 0.05;

    /**
     * Where this legend is displayed
     *
     * Possible values are: left, right, top, bottom
     *
     * @var string
     */
    public $location = 'bottom';

    /**
     * The style-palette this legend is using
     *
     * @var Palette
     */
    public $palette;

    /**
     * Create a new legend 
     */
    public function __construct()
    {

    }

    /**
     * Set the content to be displayed by this legend
     *
     * @param   array   $data   Array of key-value pairs representing the labels and their colour
     */
    public function addDataset(array $dataset)
    {
        if (!isset($dataset['label'])) {
            $dataset['label'] = 'Dataset ' . (++$this->internalCtr);
        }
        $this->dataset[$dataset['color']] = $dataset['label'];

    }

    function toSvg(RenderContext $ctx)
    {
        $outer = new Canvas('legend', new LayoutBox(0, 95, 100, 100));
        $outer->getLayout()->setPadding(2,10,2,10);

        $step = 100/count($this->dataset);
        $top = 0;

        foreach ($this->dataset as $color => $text) {
            $colorBox = new Rect($top, 0, 2, 2);
            $colorBox->setFill($color);
            $colorBox->setStrokeWidth(2);
            $outer->addElement($colorBox);
            $textBox = new Text($top+5  , 1.8 , $text);
            $outer->addElement($textBox);
            $top += $step;
        }
        $ctx->keepRatio();
        $svg = $outer->toSvg($ctx);
        $ctx->ignoreRatio();

        return $svg;
    }


}
