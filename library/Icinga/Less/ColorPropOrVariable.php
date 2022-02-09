<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Tree;
use Less_Tree_Color;
use Less_Tree_Variable;

/**
 * Compile a Less variable to {@link ColorProp} if it is a color
 */
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

        if ($v->name[1] === '@') {
            // Evaluate variable variable as in Less_Tree_Variable:28.
            $vv = new Less_Tree_Variable(substr($v->name, 1), $v->index + 1, $v->currentFileInfo);
            // Overwrite the name so that the variable variable is not evaluated again.
            $v->name = '@' . $vv->compile($env)->value;
        }

        $compiled = $v->compile($env);

        if ($compiled instanceof ColorProp) {
            // We may already have a ColorProp, which is the case with mixin calls.
            return $compiled;
        }

        if ($compiled instanceof Less_Tree_Color) {
            return ColorProp::fromColor($compiled)
                ->setIndex($v->index)
                ->setName(substr($v->name, 1));
        }

        return $compiled;
    }
}
