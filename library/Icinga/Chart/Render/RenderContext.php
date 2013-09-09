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

use Icinga\Util\Dimension;
use DOMDocument;

class RenderContext {

    private $viewBoxSize = array(1000, 1000);
    private $padding = array(0, 0);

    private $document;
    private $ratio;
    private $respectRatio = false;

    private $paddingFacX = 1;
    private $paddingFacY = 1;

    public function __construct(DOMDocument $document, $width, $height)
    {
        $this->document = $document;
        $this->ratio = $width/$height;
    }

    public function setViewBoxSize($x, $y)
    {
        $this->viewBoxSize = array($x, $y);
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return mixed
     */
    public function getNrOfUnitsX()
    {
        return intval($this->viewBoxSize[0] * $this->ratio);
    }

    public function setPadding($horizontal, $vertical) {
        $this->padding = array($horizontal, $vertical);


    }

    public function getPadding()
    {
        return array($this->paddingFacX, $this->paddingFacY);
    }

    public function keepRatio()
    {
        $this->respectRatio = true;
    }

    public function ignoreRatio()
    {
        $this->respectRatio = false;
    }

    /**
     * @return mixed
     */
    public function getNrOfUnitsY()
    {
        return $this->viewBoxSize[1];
    }

    public function toAbsolute($x, $y)
    {
        return array(
            $this->getNrOfUnitsX() / 100 * $x / ($this->respectRatio ? $this->ratio : 1),//  * $this->paddingFacX,
            $this->getNrOfUnitsY() / 100 * $y//  * $this->paddingFacY
        );
    }


    public function paddingToScaleFactor(array $padding) {
        list($horizontalPadding, $verticalPadding) = $this->toAbsolute($padding[1] + $padding[3], $padding[0] + $padding[2]);
        return array(
            ($this->getNrOfUnitsX() - $horizontalPadding) / $this->getNrOfUnitsX(),
            ($this->getNrOfUnitsY() - $verticalPadding) / $this->getNrOfUnitsY()
        );
    }
}