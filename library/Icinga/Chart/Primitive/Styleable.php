<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */


namespace Icinga\Chart\Primitive;

use DOMElement;

/**
 * Base class for stylable drawables
 */
class Styleable
{

    /**
     * The stroke width to use
     *
     * @var int
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
     * @var string
     */
    public $additionalStyle = '';

    /**
     * The id of this element
     *
     * @var string
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
     * @param   string $width   The stroke with with unit
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
     * @param   string $styles  The styles to set additionally
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
     * Return the content of the style attribute as a string
     *
     * @return string A string containing styles
     */
    public function getStyle()
    {
        $base = sprintf("fill: %s; stroke: %s;stroke-width: %s;", $this->fill, $this->strokeColor, $this->strokeWidth);
        $base .= ';' . $this->additionalStyle . ';';
        return $base;
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
