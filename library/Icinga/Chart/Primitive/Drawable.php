<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;

/**
 * Drawable element for creating svg out of components
 */
interface Drawable
{
    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     *
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx);
}
