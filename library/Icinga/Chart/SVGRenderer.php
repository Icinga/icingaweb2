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

use DOMNode;
use DOMDocument;
use DOMImplementation;
use Exception;
use Icinga\Util\Dimension;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Primitive\Canvas;

class SVGRenderer
{
    /**
     * The XML-document
     *
     * @var DOMDocument
     */
    private $document;

    /**
     * The SVG-element
     *
     * @var DOMNode
     */
    private $svg;

    /**
     * The root layer for all elements
     *
     * @var DOMNode
     */
    private $rootCanvas;

    /**
     * The position and dimension of each layer
     *
     * @var array
     */
    private $layerInfo = array();

    private $width = 100;

    private $height = 100;


    private function createRootDocument()
    {
        $implementation = new DOMImplementation();
        $docType = $implementation->createDocumentType(
            'svg',
            '-//W3C//DTD SVG 1.1//EN',
            'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
        );

        $this->document = $implementation->createDocument(null, null, $docType);
        $this->svg = $this->createOuterBox();
        $this->document->appendChild($this->svg);

    }

    private function setRootCanvas(Canvas $root) {
        $this->rootCanvas = $root;

    }

    private function createOuterBox()
    {
        $ctx = $this->createRenderContext();
        $svg = $this->document->createElement('svg');
        $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $svg->setATtribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $svg->setAttribute("width", "100%");
        $svg->setAttribute("height", "100%");
        $svg->setAttribute(
            'viewBox',
            sprintf(
                '0 0 %s %s', $ctx->getNrOfUnitsX(), $ctx->getNrOfUnitsY()
            )
        );
        return $svg;
    }

    /**
     * Initialises the XML-document, SVG-element and this figure's root layer
     */
    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
        $this->setRootCanvas(new Canvas('root', new LayoutBox(0,0)));
    }

    /**
     * Render the XML-document
     *
     * @return  string      The resulting XML structure
     */
    public function render()
    {
        $this->createRootDocument();
        $ctx = $this->createRenderContext();
        $this->svg->appendChild($this->rootCanvas->toSvg($ctx));
        $this->document->formatOutput = true;
        return $this->document->saveXML();
    }

    public function createRenderContext()
    {
        return new RenderContext($this->document, $this->width, $this->height);
    }


    public function getCanvas()
    {
        return $this->rootCanvas;
    }

    /**
     * Draw a line
     *
     * TODO: Arguments
     */
    public function line()
    {

    }

    /**
     * Draw a pie slice
     *
     * TODO: Arguments
     */
    public function slice()
    {

    }

    /**
     * Draw a bar
     *
     * TODO: Arguments
     */
    public function bar()
    {

    }
}
