<?php

namespace Icinga\Less;

use Less_Tree;
use Less_Tree_Color;
use Less_Tree_Variable;

class ColorPropOrVariable extends Less_Tree
{
    public $type = 'Variable';

    /** @var Less_Tree_Variable */
    protected $variable;

    /**
     * @return Less_Tree_Variable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param Less_Tree_Variable $variable
     *
     * @return $this
     */
    public function setVariable(Less_Tree_Variable $variable)
    {
        $this->variable = $variable;

        return $this;
    }

    public function compile($env)
    {
        $v = $this->getVariable();
        $compiled = $v->compile($env);

        if ($compiled instanceof Less_Tree_Color) {
            return ColorProp::fromColor($compiled)
                ->setIndex($v->index)
                ->setOrigin(substr($v->name, 1));
        }

        return $compiled;
    }
}
