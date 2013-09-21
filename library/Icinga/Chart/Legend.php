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

use DOMElement;

use Icinga\Chart\Palette;
use Icinga\Chart\Primitive\Canvas;
use Icinga\Chart\Primitive\Drawable;
use Icinga\Chart\Primitive\Rect;
use Icinga\Chart\Primitive\Text;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;

/**
 * Drawable for creating a Graph Legend on the bottom of a graph.
 *
 * Usually used by the GridChart class internally.
 */
class Legend implements Drawable
{

    /**
     * Internal counter for unnamed label identifiers
     *
     * @var int
     */
    private $internalCtr = 0;

    /**
     *
     * Content of this legend
     *
     * @var array
     */
    private $dataset = array();


    /**
     * Set the content to be displayed by this legend
     *
     * @param array $dataset    An array of datasets in the form they are provided to the graphing implementation
     */
    public function addDataset(array $dataset)
    {
        if (!isset($dataset['label'])) {
            $dataset['label'] = 'Dataset ' . (++$this->internalCtr);
        }
        if (!isset($dataset['color'])) {
            return;
        }
        $this->dataset[$dataset['color']] = $dataset['label'];
    }

    /**
     * Render the legend to an SVG object
     *
     * @param RenderContext $ctx    The context to use for rendering this legend
     *
     * @return DOMElement           The SVG representation of this legend
     */
    public function toSvg(RenderContext $ctx)
    {
        $outer = new Canvas('legend', new LayoutBox(0, 90, 100, 100));
        $outer->getLayout()->setPadding(2, 2, 2, 2);
        $nrOfColumns = 4;

        $leftstep = 100 / $nrOfColumns;
        $topstep = 10 / $nrOfColumns + 2;

        $top = 0;
        $left = 0;
        $lastLabelEndPos = -1;
        foreach ($this->dataset as $color => $text) {
            // Make sure labels don't overlap each other
            while ($lastLabelEndPos >= $left) {
                $left += $leftstep;
            }
            // When a label is longer than the available space, use the next line
            if ($left + strlen($text) > 100) {
                $top += $topstep;
                $left = 0;
            }

            $colorBox = new Rect($left, $top, 2, 2);
            $colorBox->setFill($color)->setStrokeWidth(2)->keepRatio();
            $outer->addElement($colorBox);

            $textBox = new Text($left+5, $top+2, $text);
            $textBox->setFontSize('2em');
            $outer->addElement($textBox);

            // readjust layout
            $lastLabelEndPos = $left + strlen($text);
            $left += $leftstep;
        }
        $svg = $outer->toSvg($ctx);
        return $svg;
    }
}
