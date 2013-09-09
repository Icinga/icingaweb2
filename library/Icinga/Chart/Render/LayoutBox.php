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


namespace Icinga\Chart\Render;


class LayoutBox
{
    private $height;
    private $width;
    private $x;
    private $y;
    private $padding = array(0,0,0,0);
    private $margin = array(0,0,0,0);


    public function __construct($x, $y,  $width = null, $height = null)
    {
        $this->height = $height ? $height : 100;
        $this->width =  $width ? $width : 100;
        $this->x =  $x;
        $this->y =  $y;
    }

    public function setUniformPadding($padding)
    {
        $this->padding = array($padding, $padding, $padding, $padding);
    }

    public function setPadding($top, $right, $bottom, $left)
    {
        $this->padding = array($top, $right, $bottom, $left);
    }

    public function setUniformMargin($margin)
    {
        $this->margin = array($margin, $margin, $margin, $margin);
    }

    public function setMargin($top, $right, $bottom, $left)
    {
        $this->margin = array($top, $right, $bottom, $left);
    }

    public function getOuterTranslate(RenderContext $ctx)
    {
        list($marginX, $marginY) = $ctx->toAbsolute($this->margin[3], $this->margin[0]);
        return sprintf('translate(%s, %s)', $marginX, $marginY);
    }

    public function getInnerTransform(RenderContext $ctx)
    {
        list($translateX, $translateY) = $ctx->toAbsolute($this->padding[3] + $this->getX(), $this->padding[0] + $this->getY());
        list($scaleX, $scaleY) = $ctx->paddingToScaleFactor($this->padding);

        $scaleX *= $this->getWidth()/100;
        $scaleY *= $this->getHeight()/100;
        return sprintf('translate(%s, %s) scale(%s, %s)',  $translateX, $translateY, $scaleX, $scaleY);
    }

    public function __toString()
    {
        return sprintf("Rectangle: x: %s  y: %s, height: %s, width: %s\n", $this->x, $this->y, $this->height, $this->width);
    }

    /**
     * @return array
     */
    public function getMargin()
    {
        return $this->margin;
    }

    /**
     * @return array
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @return \Icinga\Util\Dimension
     */
    public function getHeight()
    {
        return $this->height+($this->margin[0] + $this->margin[2]);
    }

    /**
     * @return \Icinga\Util\Dimension
     */
    public function getWidth()
    {
        return $this->width-($this->margin[1] + $this->margin[3]);
    }

    /**
     * @return \Icinga\Util\Dimension
     */
    public function getX()
    {
        return $this->x + $this->margin[1];
    }

    /**
     * @return \Icinga\Util\Dimension
     */
    public function getY()
    {
        return $this->y + $this->margin[0];
    }

    public function getRatio()
    {
        return $this->width->getValue()/$this->height->getValue();
    }

}