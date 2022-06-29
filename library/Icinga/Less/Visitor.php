<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Parser;
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
@media (min-height: @prefer-light-color-scheme),
(prefers-color-scheme: light) and (min-height: @enable-color-preference) {
    %s
}
CSS;

    const LIGHT_MODE_NAME = 'light-mode';

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
     * @var false|string
     */
    protected $definingVariable = false;

    /** @var Less_Tree_Rule If defining a variable, determines the origin rule of the variable */
    protected $variableOrigin;

    /** @var LightMode Light mode registry */
    protected $lightMode;

    /** @var false|string Whether parsing module Less */
    protected $moduleScope = false;

    /** @var null|string CSS module selector if any */
    protected $moduleSelector;

    public function visitCall($c)
    {
        if ($c->name === 'var') {
            if ($this->callingVar !== false) {
                throw new LogicException('Already calling var');
            }

            $this->callingVar = spl_object_hash($c);
        } else {
            // We need to use our own tree call class , so that we can precompile the arguments before making
            // the actual LESS function calls. Otherwise, it will produce lots of invalid argument exceptions!
            $c = Call::fromCall($c);
        }

        return $c;
    }

    public function visitCallOut($c)
    {
        if ($this->callingVar !== false && $this->callingVar === spl_object_hash($c)) {
            $this->callingVar = false;
        }
    }

    public function visitDetachedRuleset($drs)
    {
        if ($this->variableOrigin->name === '@' . static::LIGHT_MODE_NAME) {
            $this->variableOrigin->name .= '-' . substr(sha1(uniqid(mt_rand(), true)), 0, 7);

            $this->lightMode->add($this->variableOrigin->name);

            if ($this->moduleSelector !== false) {
                $this->lightMode->setSelector($this->variableOrigin->name, $this->moduleSelector);
            }

            $drs = LightModeDefinition::fromDetachedRuleset($drs)
                ->setLightMode($this->lightMode)
                ->setName($this->variableOrigin->name);
        }

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

    public function visitRuleset($rs)
    {
        // Method is required, otherwise visitRulesetOut will not be called.
        return $rs;
    }

    public function visitRulesetOut($rs)
    {
        if ($this->moduleScope !== false
            && isset($rs->selectors)
            && spl_object_hash($rs->selectors[0]) === $this->moduleScope
        ) {
            $this->moduleSelector = null;
            $this->moduleScope = false;
        }
    }

    public function visitSelector($s)
    {
        if ($s->_oelements_len === 2 && $s->_oelements[0] === '.icinga-module') {
            $this->moduleSelector = implode('', $s->_oelements);
            $this->moduleScope = spl_object_hash($s);
        }

        return $s;
    }

    public function visitVariable($v)
    {
        if ($this->callingVar !== false || $this->definingVariable !== false) {
            return $v;
        }

        return (new ColorPropOrVariable())
            ->setVariable($v);
    }

    public function visitColor($c)
    {
        if ($this->definingVariable !== false) {
            $c->name = $this->variableOrigin->name;
        }

        return $c;
    }

    public function run($node)
    {
        $this->lightMode = new LightMode();

        $evald = $this->visitObj($node);

        // The visitor has registered all light modes in visitDetachedRuleset, but has not called them yet.
        // Now the light mode calls are prepared with the appropriate CSS selectors.
        $calls = [];
        foreach ($this->lightMode as $mode) {
            if ($this->lightMode->hasSelector($mode)) {
                $calls[] = "{$this->lightMode->getSelector($mode)} {\n$mode();\n}";
            } else {
                $calls[] = "$mode();";
            }
        }

        if (! empty($calls)) {
            // Place and parse light mode calls into a new anonymous file,
            // leaving the original Less in which the light modes were defined untouched.
            $parser = (new Less_Parser())
                ->parse(sprintf(static::LIGHT_MODE_CSS, implode("\n", $calls)));

            // Because Less variables are block scoped,
            // we can't just access the light mode definitions in the calls above.
            // The LightModeVisitor ensures that all calls have access to the environment in which the mode was defined.
            // Finally, the rules are merged so that the light mode calls are also rendered to CSS.
            $rules = new ReflectionProperty(get_class($parser), 'rules');
            $rules->setAccessible(true);
            $evald->rules = array_merge(
                $evald->rules,
                (new LightModeVisitor())
                    ->setLightMode($this->lightMode)
                    ->visitArray($rules->getValue($parser))
            );
            // The LightModeVisitor is used explicitly here instead of using it as a plugin
            // since we only need to process the newly created rules for the light mode calls.
        }

        return $evald;
    }
}
