<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Environment;
use Less_Tree_Ruleset;
use Less_Tree_RulesetCall;

/**
 * Use the environment where the light mode was defined to evaluate the call
 */
class LightModeCall extends Less_Tree_RulesetCall
{
    use LightModeTrait;

    /**
     * @param Less_Tree_RulesetCall $c
     *
     * @return static
     */
    public static function fromRulesetCall(Less_Tree_RulesetCall $c)
    {
        return new static($c->variable);
    }

    /**
     * @param Less_Environment $env
     *
     * @return Less_Tree_Ruleset
     */
    public function compile($env)
    {
        return parent::compile(
            $env->copyEvalEnv(array_merge($env->frames, $this->getLightMode()->getEnv($this->variable)->frames))
        );
    }
}
