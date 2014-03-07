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


namespace Icinga\Chart\Primitive;

use \DOMElement;
use \DOMText;
use \Icinga\Chart\Render\RenderContext;

/**
 *  Wrapper for the SVG text element
 */
class Text extends Styleable implements Drawable
{
    /**
     * Align the text to end at the x and y position
     */
    const ALIGN_END     = 'end';

    /**
     * Align the text to start at the x and y position
     */
    const ALIGN_START   = 'start';

    /**
     * Align the text to be centered at the x and y position
     */
    const ALIGN_MIDDLE  = 'middle';

    /**
     * Normal left to right orientation
     */
    const ORIENTATION_HORIZONTAL = "";

    /**
     * Top down orientation (rotated by 90°)
     */
    const ORIENTATION_VERTICAL = "writing-mode: tb;";

    /**
     * The x position of the Text
     *
     * @var int
     */
    private $x;

    /**
     * The y position of the Text
     *
     * @var int
     */
    private $y;

    /**
     * The text content
     *
     * @var string
     */
    private $text;

    /**
     * The size of the font
     *
     * @var string
     */
    private $fontSize = '1.5em';

    /**
     * The weight of the font
     *
     * @var string
     */
    private $fontWeight = 'normal';

    /**
     * The default fill color
     *
     * @var string
     */
    public $fill = '#000';

    /**
     * The alignment of the text
     *
     * @var string
     */
    private $alignment = self::ALIGN_START;

    /**
     * Set the font-stretch property of the text
     */
    private $fontStretch = 'semi-condensed';

    /**
     * Construct a new text drawable
     *
     * @param int $x            The x position of the text
     * @param int $y            The y position of the text
     * @param string $text      The text this component should contain
     * @param string $fontSize  The font size of the text
     */
    public function __construct($x, $y, $text, $fontSize = '1.5em')
    {
        $this->x = $x;
        $this->y = $y;
        $this->text = $text;
        $this->fontSize = $fontSize;
    }

    /**
     * Set the font size of the svg text element
     *
     * @param   string $size    The font size including a unit
     *
     * @return  self            Fluid interface
     */
    public function setFontSize($size)
    {
        $this->fontSize = $size;
        return $this;
    }

    /**
     * Set the the text alignment with one of the ALIGN_* constants
     *
     * @param   String $align   Value how to align
     *
     * @return  self            Fluid interface
     */
    public function setAlignment($align)
    {
        $this->alignment = $align;
        return $this;
    }

    /**
     * Set the weight of the current font
     *
     * @param string $weight    The weight of the string
     *
     * @return self             Fluid interface
     */
    public function setFontWeight($weight)
    {
        $this->fontWeight = $weight;
        return $this;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx  The context to use for rendering
     *
     * @return  DOMElement          The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {
        list($x, $y) = $ctx->toAbsolute($this->x, $this->y);
        $text = $ctx->getDocument()->createElement('text');
        $text->setAttribute('x', $x - 15);
        $text->setAttribute(
            'style',
            $this->getStyle()
            . ';font-size:' . $this->fontSize
            . '; font-family: Ubuntu, Calibri, Trebuchet MS, Helvetica, Verdana, sans-serif'
            . ';font-weight: ' . $this->fontWeight
            . ';font-stretch: ' . $this->fontStretch
            . '; font-style: normal;'
            .  'text-anchor: ' . $this->alignment
        );

        $text->setAttribute('y', $y);
        $text->appendChild(new DOMText($this->text));
        return $text;
    }
}
