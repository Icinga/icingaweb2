<?php

namespace Icinga\Less;

use Less_Environment;
use Less_Tree_DetachedRuleset;

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

        $this->getLightMode()->setEnv($this->getName(), $env->copyEvalEnv($env->frames));

        return $drs;
    }
}
