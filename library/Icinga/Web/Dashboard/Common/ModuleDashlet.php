<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

trait ModuleDashlet
{
    /**
     * A flag to identify whether this dashlet widget originates from a module
     *
     * @var bool
     */
    private $moduleDashlet = false;

    /**
     * The name of the module this dashlet comes from
     *
     * @var string
     */
    private $module;

    /**
     * Get the name of the module which provides this dashlet
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the name of the module which provides this dashlet
     *
     * @param string $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get whether this widget originates from a module
     *
     * @return bool
     */
    public function isModuleDashlet()
    {
        return $this->moduleDashlet;
    }

    /**
     * Set whether this dashlet widget is provided by a module
     *
     * @param bool $moduleDashlet
     *
     * @return $this
     */
    public function setModuleDashlet(bool $moduleDashlet)
    {
        $this->moduleDashlet = $moduleDashlet;

        return $this;
    }
}
