<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;

/**
 * Wrapper for raw elements to be added as Drawable's
 */
class RawElement implements Drawable
{

    /**
     * The DOMElement wrapped by this Drawable
     *
     * @var DOMElement
     */
    private $domEl;

    /**
     * Create this RawElement
     *
     * @param DOMElement $el    The element to wrap here
     */
    public function __construct(DOMElement $el)
    {
        $this->domEl = $el;
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
        return $this->domEl;
    }
}
