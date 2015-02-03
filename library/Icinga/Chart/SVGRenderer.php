<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Chart;

use DOMNode;
use DOMElement;
use DOMDocument;
use DOMImplementation;
use Icinga\Chart\Render\LayoutBox;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Primitive\Canvas;

/**
 * SVG Renderer component.
 *
 * Creates the basic DOM tree of the SVG to use
 */
class SVGRenderer
{
    const X_ASPECT_RATIO_MIN = 'xMin';

    const X_ASPECT_RATIO_MID = 'xMid';

    const X_ASPECT_RATIO_MAX = 'xMax';

    const Y_ASPECT_RATIO_MIN = 'YMin';

    const Y_ASPECT_RATIO_MID = 'YMid';

    const Y_ASPECT_RATIO_MAX = 'YMax';

    const ASPECT_RATIO_PAD = 'meet';

    const ASPECT_RATIO_CUTOFF = 'slice';

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
     * @var Canvas
     */
    private $rootCanvas;

    /**
     * The width of this renderer
     *
     * @var int
     */
    private $width = 100;

    /**
     * The height of this renderer
     *
     * @var int
     */
    private $height = 100;

    /**
     * Whether the aspect ratio is preversed
     *
     * @var bool
     */
    private $preserveAspectRatio = false;

    /**
     * Horizontal alignment of SVG element
     *
     * @var string
     */
    private $xAspectRatio = self::X_ASPECT_RATIO_MID;

    /**
     * Vertical alignment of SVG element
     *
     * @var string
     */
    private $yAspectRatio = self::Y_ASPECT_RATIO_MID;

    /**
     * Define whether aspect differences should be handled using padding (default) or cutoff
     *
     * @var string
     */
    private $xFillMode = "meet";


    /**
     * Create the root document and the SVG root node
     */
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

    /**
     * Create the outer SVG box  containing the root svg element and namespace and return it
     *
     * @return DOMElement The SVG root node
     */
    private function createOuterBox()
    {
        $ctx = $this->createRenderContext();
        $svg = $this->document->createElement('svg');
        $svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $svg->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $svg->setAttribute('width', '100%');
        $svg->setAttribute('height', '100%');
        $svg->setAttribute(
            'viewBox',
            sprintf(
                '0 0 %s %s',
                $ctx->getNrOfUnitsX(),
                $ctx->getNrOfUnitsY()
            )
        );
        if ($this->preserveAspectRatio) {
            $svg->setAttribute(
                'preserveAspectRatio',
                sprintf (
                    '%s%s %s',
                    $this->xAspectRatio,
                    $this->yAspectRatio,
                    $this->xFillMode
                )
            );
        }
        return $svg;
    }

    /**
     * Initialises the XML-document, SVG-element and this figure's root canvas
     *
     * @param int $width    The width ratio
     * @param int $height   The height ratio
     */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->rootCanvas = new Canvas('root', new LayoutBox(0, 0));
    }

    /**
     * Render the SVG-document
     *
     * @return string The resulting XML structure
     */
    public function render()
    {
        $this->createRootDocument();
        $ctx = $this->createRenderContext();
        $this->svg->appendChild($this->rootCanvas->toSvg($ctx));
        $this->document->formatOutput = true;
        return $this->document->saveXML();
    }

    /**
     * Create a render context that will be used for rendering elements
     *
     * @return RenderContext The created RenderContext instance
     */
    public function createRenderContext()
    {
        return new RenderContext($this->document, $this->width, $this->height);
    }

    /**
     * Return the root canvas of this rendered
     *
     * @return Canvas The canvas that will be the uppermost element in this figure
     */
    public function getCanvas()
    {
        return $this->rootCanvas;
    }

    /**
     * Preserve the aspect ratio of the rendered object
     *
     * Do not deform the content of the SVG when the aspect ratio of the viewBox
     * differs from the aspect ratio of the SVG element, but add padding or cutoff
     * instead
     *
     * @param bool $preserve    Whether the aspect ratio should be preserved
     */
    public function preserveAspectRatio($preserve = true)
    {
        $this->preserveAspectRatio = $preserve;
    }

    /**
     * Change the horizontal alignment of the SVG element
     *
     * Change the horizontal alignment of the svg, when preserveAspectRatio is used and
     * padding is present. Defaults to
     */
    public function setXAspectRatioAlignment($alignment)
    {
        $this->xAspectRatio = $alignment;
    }

    /**
     * Change the vertical alignment of the SVG element
     *
     * Change the vertical alignment of the svg, when preserveAspectRatio is used and
     * padding is present.
     */
    public function setYAspectRatioAlignment($alignment)
    {
        $this->yAspectRatio = $alignment;
    }
}
