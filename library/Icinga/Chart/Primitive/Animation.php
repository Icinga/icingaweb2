<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Chart\Render\RenderContext;

/**
 * Drawable for the SVG animate tag
 */
class Animation implements Drawable
{
    /**
     * The attribute to animate
     *
     * @var string
     */
    private $attribute;

    /**
     * The 'from' value
     *
     * @var mixed
     */
    private $from;

    /**
     * The to value
     *
     * @var mixed
     */
    private $to;

    /**
     * The begin value (in seconds)
     *
     * @var float
     */
    private $begin = 0;

    /**
     * The duration value (in seconds)
     *
     * @var float
     */
    private $duration = 0.5;

    /**
     * Create an animation object
     *
     * @param string    $attribute  The attribute to animate
     * @param string    $from       The from value for the animation
     * @param string    $to         The to value for the animation
     * @param float     $duration   The duration of the duration
     * @param float     $begin      The begin of the duration
     */
    public function __construct($attribute, $from, $to, $duration = 0.5, $begin = 0.0)
    {
        $this->attribute = $attribute;
        $this->from = $from;
        $this->to = $to;
        $this->duration = $duration;
        $this->begin = $begin;
    }

    /**
     * Create the SVG representation from this Drawable
     *
     * @param   RenderContext $ctx The context to use for rendering
     * @return  DOMElement         The SVG Element
     */
    public function toSvg(RenderContext $ctx)
    {

        $animate = $ctx->getDocument()->createElement('animate');
        $animate->setAttribute('attributeName', $this->attribute);
        $animate->setAttribute('attributeType', 'XML');
        $animate->setAttribute('from', $this->from);
        $animate->setAttribute('to', $this->to);
        $animate->setAttribute('begin', $this->begin . 's');
        $animate->setAttribute('dur', $this->duration . 's');
        $animate->setAttributE('fill', "freeze");

        return $animate;
    }
}
