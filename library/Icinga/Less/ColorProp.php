<?php

namespace Icinga\Less;

use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Keyword;

class ColorProp extends Less_Tree_Color
{
    /** @var Less_Tree_Color */
    protected $color;

    /** @var int */
    protected $index;

    /** @var string */
    protected $origin;

    public function __construct()
    {
    }

    /**
     * @param Less_Tree_Color $color
     *
     * @return self
     */
    public static function fromColor(Less_Tree_Color $color)
    {
        $self = new self();
        $self->color = $color;

        foreach ($color as $k => $v) {
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
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     *
     * @return $this
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    public function compile()
    {
        return $this;
    }

    public function genCSS($output)
    {
        $css = (new Less_Tree_Call(
            'var',
            [
                new Less_Tree_Keyword('--' . $this->getOrigin()),
                $this->color
            ],
            $this->getIndex()
        ))->toCSS();

        $output->add($css);
    }
}
