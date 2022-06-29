<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Keyword;

/**
 * ColorProp renders Less colors as CSS var() function calls
 *
 * It extends {@link Less_Tree_Color} so that Less functions that take a Less_Tree_Color as an argument do not fail.
 */
class ColorProp extends Less_Tree_Color
{
    /** @var Less_Tree_Color|ColorProp Color with which we created the ColorProp */
    protected $color;

    /** @var int */
    protected $index;

    /** @var string Color variable name */
    protected $name;

    private function __construct()
    {
    }

    /**
     * @param Less_Tree_Color $color
     *
     * @return static
     */
    public static function fromColor(Less_Tree_Color $color)
    {
        $self = new static();
        $self->color = $color;

        foreach ($color as $k => $v) {
            $self->$k = $v;
        }

        return $self;
    }

    /**
     * @param ColorProp $color
     *
     * @return static
     */
    public static function fromColorProp(ColorProp $color)
    {
        $self = new static();
        $self->color = $color;

        $lessColor = $color;
        do {
            $lessColor = $lessColor->color;
        } while ($lessColor instanceof ColorProp);

        foreach ($lessColor as $k => $v) {
            $self->$k = $v;
        }

        return $self;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function genCSS($output)
    {
        $css = (new Less_Tree_Call(
            'var',
            [
                new Less_Tree_Keyword('--' . $this->getName()),
                // Use the color with which we created the ColorProp so that we don't get into genCSS() loops.
                $this->color
            ],
            $this->getIndex()
        ))->toCSS();

        $output->add($css);
    }
}
