<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */


namespace Icinga\Chart\Primitive;

use DOMElement;
use Icinga\Util\Csp;
use ipl\Web\Style;

/**
 * Base class for stylable drawables
 */
class Styleable
{

    /**
     * The stroke width to use
     *
     * @var int|float
     */
    public $strokeWidth = 0;

    /**
     * The stroke color to use
     *
     * @var string
     */
    public $strokeColor = '#000';

    /**
     * The fill color to use
     *
     * @var string
     */
    public $fill = 'none';

    /**
     * Additional styles to be appended to the style attribute
     *
     * @var array<string, string>
     */
    public $additionalStyle = [];

    /**
     * The id of this element
     *
     * @var ?string
     */
    public $id = null;

    /**
     * Additional attributes to be set
     *
     * @var array
     */
    public $attributes = array();

    /**
     * Set the stroke width for this drawable
     *
     * @param   int|float $width   The stroke with unit
     *
     * @return  $this            Fluid interface
     */
    public function setStrokeWidth($width)
    {
        $this->strokeWidth = $width;
        return $this;
    }

    /**
     * Set the color for the stroke or none for no stroke
     *
     * @param   string $color   The color to set for the stroke
     *
     * @return  $this            Fluid interface
     */
    public function setStrokeColor($color)
    {
        $this->strokeColor = $color ? $color : 'none';
        return $this;
    }

    /**
     * Set additional styles for this drawable
     *
     * @param   array<string, string> $styles  The styles to set additionally
     *
     * @return  $this            Fluid interface
     */
    public function setAdditionalStyle($styles)
    {
        $this->additionalStyle = $styles;
        return $this;
    }

    /**
     * Set the fill for this styleable
     *
     * @param   string $color   The color to use for filling or null to use no fill
     *
     * @return  $this            Fluid interface
     */
    public function setFill($color = null)
    {
        $this->fill = $color ? $color : 'none';
        return $this;
    }

    /**
     * Set the id for this element
     *
     * @param   string $id  The id to set for this element
     *
     * @return  $this        Fluid interface
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Return the ruleset used for styling the DOMNode
     *
     * @return Style A ruleset containing styles
     */
    public function getStyle()
    {
        $styles = $this->additionalStyle;
        $styles['fill'] = $this->fill;
        $styles['stroke'] = $this->strokeColor;
        $styles['stroke-width'] = (string) $this->strokeWidth;

        return (new Style())
            ->setNonce(Csp::getStyleNonce())
            ->add("#$this->id", $styles);
    }

    /**
     *  Add an additional attribute to this element
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Apply attribute to a DOMElement
     *
     * @param DOMElement $el Element to apply attributes
     */
    protected function applyAttributes(DOMElement $el)
    {
        foreach ($this->attributes as $name => $value) {
            $el->setAttribute($name, $value);
        }
    }
}
