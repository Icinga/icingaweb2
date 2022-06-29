<?php

namespace Icinga\Less;

use Less_Exception_Compiler;
use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Value;
use Less_Tree_Variable;

class DeferredColorProp extends Less_Tree_Variable
{
    /** @var DeferredColorProp|ColorProp */
    protected $reference;

    protected $resolved = false;

    public function __construct($name, $variable, $index = null, $currentFileInfo = null)
    {
        parent::__construct($name, $index, $currentFileInfo);

        if ($variable instanceof Less_Tree_Variable) {
            $this->reference = self::fromVariable($variable);
        }
    }

    public function isResolved()
    {
        return $this->resolved;
    }

    public function getName()
    {
        $name = $this->name;
        if ($this->name[0] === '@') {
            $name = substr($this->name, 1);
        }

        return $name;
    }

    public function hasReference()
    {
        return $this->reference !== null;
    }

    public function getRef()
    {
        return $this->reference;
    }

    public function setReference($ref)
    {
        $this->reference = $ref;

        return $this;
    }

    public static function fromVariable(Less_Tree_Variable $variable)
    {
        $static = new static($variable->name, $variable->index, $variable->currentFileInfo);
        $static->evaluating = $variable->evaluating;
        $static->type = $variable->type;

        return $static;
    }

    public function compile($env)
    {
        if (! $this->hasReference()) {
            // This is never supposed to happen, however, we might have a deferred color prop
            // without a reference. In this case we can simply use the parent method.
            return parent::compile($env);
        }

        if ($this->isResolved()) {
            // The dependencies are already resolved, no need to traverse the frame stack over again!
            return $this;
        }

        if ($this->evaluating) {
            throw new Less_Exception_Compiler(
                "Recursive variable definition for " . $this->name,
                null,
                $this->index,
                $this->currentFileInfo
            );
        }

        $this->evaluating = true;

        foreach (array_reverse($env->frames) as $frame) {
            if (($v = $frame->variable($this->getRef()->name))) {
                $rv = $v->value;
                if ($rv instanceof Less_Tree_Value) {
                    $rv = $rv->compile($env);
                }

                // As we are at it anyway, let's cast the tree color to our color prop as well!
                if ($rv instanceof Less_Tree_Color) {
                    $rv = ColorProp::fromColor($rv);
                }

                $this->evaluating = false;
                $this->resolved = true;
                $this->setReference($rv);

                break;
            }
        }

        return $this;
    }

    public function genCSS($output)
    {
        if (! $this->hasReference()) {
            return; // Nothing to generate
        }

        $css = (new Less_Tree_Call(
            'var',
            [
                new \Less_Tree_Keyword('--' . $this->getName()),
                $this->getRef() // Each of the references will be generated recursively
            ],
            $this->index
        ))->toCSS();

        $output->add($css);
    }
}
