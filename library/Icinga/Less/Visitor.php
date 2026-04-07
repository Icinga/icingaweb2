<?php

// SPDX-FileCopyrightText: 2021 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Less;

use Less_Parser;
use Less_Tree;
use Less_Tree_DetachedRuleset;
use Less_Tree_Expression;
use Less_Tree_Rule;
use Less_Tree_Value;
use Less_Tree_Variable;
use Less_VisitorReplacing;
use LogicException;
use ReflectionProperty;

/**
 * Replace compiled Less colors with CSS var() function calls and inject light mode calls
 *
 * Color replacing basically works by replacing every visited Less variable with {@link ColorPropOrVariable},
 * which is later compiled to {@link ColorProp} if it is a color.
 *
 * Light mode calls are generated from light mode definitions.
 */
class Visitor extends Less_VisitorReplacing
{
    const LIGHT_MODE_CSS = <<<'CSS'
@media (min-height: @prefer-light-color-scheme), print,
(prefers-color-scheme: light) and (min-height: @enable-color-preference) {
    %s
}
CSS;

    const LIGHT_MODE_NAME = 'light-mode';

    public $isPreEvalVisitor = true;

    /**
     * Whether defining a variable
     *
     * If that's the case, don't try to replace compiled Less colors with CSS var() function calls.
     *
     * @var false|string
     */
    protected $definingVariable = false;

    /** @var Less_Tree_Rule If defining a variable, determines the origin rule of the variable */
    protected $variableOrigin;


    public function visitCall($c)
    {
        if ($c->name !== 'var') {
            // We need to use our own tree call class , so that we can precompile the arguments before making
            // the actual LESS function calls. Otherwise, it will produce lots of invalid argument exceptions!
            $c = Call::fromCall($c);
        }

        return $c;
    }

    public function visitDetachedRuleset($drs)
    {
        // Since a detached ruleset is a variable definition in the first place,
        // just reset that we define a variable.
        $this->definingVariable = false;

        return $drs;
    }

    public function visitMixinCall($c)
    {
        // Less_Tree_Mixin_Call::accept() does not visit arguments, but we have to replace them if necessary.
        foreach ($c->arguments as $a) {
            $a['value'] = $this->visitObj($a['value']);
        }

        return $c;
    }

    public function visitMixinDefinition($m)
    {
        // Less_Tree_Mixin_Definition::accept() does not visit params, but we have to replace them if necessary.
        foreach ($m->params as $p) {
            if (! isset($p['value'])) {
                continue;
            }

            $p['value'] = $this->visitObj($p['value']);
        }

        return $m;
    }

    public function visitRule($r)
    {
        if ($r->name === '@' . static::LIGHT_MODE_NAME
            && $r->value instanceof Less_Tree_DetachedRuleset
        ) {
            $name = uniqid(static::LIGHT_MODE_NAME);

            $r->name = "@{$name}";

            $parser = (new Less_Parser())->parse(sprintf(static::LIGHT_MODE_CSS, "@{$name}();"));
            $rules = (new ReflectionProperty(Less_Parser::class, 'rules'))->getValue($parser);

            return [$r, ...$rules];
        }

        if ($r->name[0] === '@' && $r->variable) {
            if ($this->definingVariable !== false) {
                throw new LogicException('Already defining a variable');
            }

            $this->definingVariable = spl_object_hash($r);
            $this->variableOrigin = $r;

            if ($r->value instanceof Less_Tree_Value) {
                if ($r->value->value[0] instanceof Less_Tree_Expression) {
                    if ($r->value->value[0]->value[0] instanceof Less_Tree_Variable) {
                        // Transform the variable definition rule into our own class
                        $r->value->value[0]->value[0] = new DeferredColorProp($r->name, $r->value->value[0]->value[0]);
                    }
                }
            }
        }

        return $r;
    }

    public function visitRuleOut($r)
    {
        if ($this->definingVariable !== false && $this->definingVariable === spl_object_hash($r)) {
            $this->definingVariable = false;
            $this->variableOrigin = null;
        }
    }

    public function visitVariable($v)
    {
        if ($this->definingVariable !== false) {
            return $v;
        }

        return (new ColorPropOrVariable())
            ->setVariable($v);
    }

    public function run($node)
    {
        $this->visitObj($node);
    }
}
