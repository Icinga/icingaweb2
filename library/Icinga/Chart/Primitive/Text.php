<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */


namespace Icinga\Chart\Primitive;

use DOMElement;
use DOMText;
use Icinga\Chart\Render\RenderContext;
use Icinga\Chart\Format;

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
     * @return  $this            Fluid interface
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
     * @return  $this            Fluid interface
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
     * @return $this             Fluid interface
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
        $text->setAttribute('x', Format::formatSVGNumber($x - 15));
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

        $text->setAttribute('y', Format::formatSVGNumber($y));
        $text->appendChild(new DOMText($this->text));
        return $text;
    }
}
