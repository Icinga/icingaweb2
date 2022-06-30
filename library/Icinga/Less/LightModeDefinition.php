<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Less;

use Less_Environment;
use Less_Exception_Compiler;
use Less_Tree_DetachedRuleset;
use Less_Tree_Ruleset;

/**
 * Register the environment in which the light mode is defined
 */
class LightModeDefinition extends Less_Tree_DetachedRuleset
{
    use LightModeTrait;

    /** @var string */
    protected $name;

    /**
     * @param Less_Tree_DetachedRuleset $drs
     *
     * @return static
     */
    public static function fromDetachedRuleset(Less_Tree_DetachedRuleset $drs)
    {
        return new static($drs->ruleset, $drs->frames);
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

    /**
     * @param Less_Environment $env
     *
     * @return Less_Tree_DetachedRuleset
     */
    public function compile($env)
    {
        $drs = parent::compile($env);

        /** @var $frame Less_Tree_Ruleset */
        foreach ($env->frames as $frame) {
            if ($frame->variable($this->getName())) {
                if (! empty($frame->first_oelements) && ! isset($frame->first_oelements['.icinga-module'])) {
                    throw new Less_Exception_Compiler('Light mode definition not allowed in selectors');
                }

                break;
            }
        }

        $this->getLightMode()->setEnv($this->getName(), $env->copyEvalEnv($env->frames));

        return $drs;
    }
}
