<?php

namespace Icinga\Less;

use Less_VisitorReplacing;
use LogicException;

/**
 * Replace compiled Less colors with CSS var() function calls
 */
class Visitor extends Less_VisitorReplacing
{
    public $isPreEvalVisitor = true;

    /**
     * Whether calling var() CSS function
     *
     * If that's the case, don't try to replace compiled Less colors with CSS var() function calls.
     *
     * @var bool|string
     */
    protected $callingVar = false;

    /**
     * Whether defining a variable
     *
     * If that's the case, don't try to replace compiled Less colors with CSS var() function calls.
     *
     * @var bool|string
     */
    protected $definingVar = false;

    public function visitCall($c)
    {
        if ($c->name === 'var') {
            if ($this->callingVar !== false) {
                throw new LogicException('Already calling var');
            }

            $this->callingVar = spl_object_hash($c);
        }

        return $c;
    }

    public function visitCallOut($c)
    {
        if ($this->callingVar !== false && $this->callingVar === spl_object_hash($c)) {
            $this->callingVar = false;
        }
    }

    public function visitDetachedRuleset($rs)
    {
        // A detached ruleset is a variable definition in the first place,
        // so just reset that we define a variable.
        $this->definingVar = false;

        return $rs;
    }

    public function visitRule($r)
    {
        if ($r->name[0] === '@' && $r->variable) {
            if ($this->definingVar !== false) {
                throw new LogicException('Already defining a variable');
            }

            $this->definingVar = spl_object_hash($r);
        }

        return $r;
    }

    public function visitRuleOut($r)
    {
        if ($this->definingVar !== false && $this->definingVar === spl_object_hash($r)) {
            $this->definingVar = false;
        }
    }

    public function visitVariable($v)
    {
        if ($this->callingVar !== false || $this->definingVar !== false) {
            return $v;
        }

        return (new ColorPropOrVariable())
            ->setVariable($v);
    }

    public function run($node)
    {
        return $this->visitObj($node);
    }
}
