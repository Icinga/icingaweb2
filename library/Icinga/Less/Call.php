<?php

namespace Icinga\Less;

use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Value;
use Less_Tree_Variable;

class Call extends Less_Tree_Call
{
    public static function fromCall(Less_Tree_Call $call)
    {
        return new static($call->name, $call->args, $call->index, $call->currentFileInfo);
    }

    public function compile($env = null)
    {
        if (! $env) {
            // Not sure how to trigger this, but if there is no $env, there is nothing we can do
            return parent::compile($env);
        }

        foreach ($this->args as $arg) {
            $name = null;
            if ($arg->value[0] instanceof Less_Tree_Variable) {
                // This is the case when defining a variable with a callable LESS rules such as fade, fadeout..
                // Example: `@foo: #fff; @foo-bar: fade(@foo, 10);`
                $name = $arg->value[0]->name;
            } elseif ($arg->value[0] instanceof ColorPropOrVariable) {
                // This is the case when defining a CSS rule using the LESS functions and passing
                // a variable as an argument to them. Example: `... { color: fade(@foo, 10%); }`
                $name = $arg->value[0]->getVariable()->name;
            }

            if ($name) {
                foreach ($env->frames as $frame) {
                    if (($v = $frame->variable($name))) {
                        // Variables from the frame stack are always of type LESS Tree Rule
                        $vr = $v->value;
                        if ($vr instanceof Less_Tree_Value) {
                            // Get the actual color prop, otherwise this may cause an invalid argument error
                            $vr = $vr->compile($env);
                        }

                        if ($vr instanceof DeferredColorProp) {
                            if (! $vr->hasReference()) {
                                // Should never happen, though just for safety's sake
                                $vr->compile($env);
                            }

                            // Get the uppermost variable of the variable references
                            while (! $vr instanceof ColorProp) {
                                $vr = $vr->getRef();
                            }
                        } elseif ($vr instanceof Less_Tree_Color) {
                            $vr = ColorProp::fromColor($vr);
                            $vr->setName($name);
                        }

                        $arg->value[0] = $vr;
                        break;
                    }
                }
            }
        }

        return parent::compile($env);
    }
}
