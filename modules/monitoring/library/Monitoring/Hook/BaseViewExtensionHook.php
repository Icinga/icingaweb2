<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Application\ClassLoader;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Web\View;

/**
 * Base class for hooks extending views
 *
 * TODO: make this a trait one nice day
 */
abstract class BaseViewExtensionHook
{
    /**
     * The view the generated HTML will be included in
     *
     * @var View
     */
    private $view;

    /**
     * The module of the derived class
     *
     * @var Module
     */
    private $module;

    /**
     * Get {@link view}
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set {@link view}
     *
     * @param   View $view
     *
     * @return  $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get the module of the derived class
     *
     * @return Module
     */
    public function getModule()
    {
        if ($this->module === null) {
            $class = get_class($this);
            if (ClassLoader::classBelongsToModule($class)) {
                $this->module = Icinga::app()->getModuleManager()->getModule(ClassLoader::extractModuleName($class));
            }
        }

        return $this->module;
    }

    /**
     * Set the module of the derived class
     *
     * @param Module $module
     *
     * @return $this
     */
    public function setModule(Module $module)
    {
        $this->module = $module;

        return $this;
    }
}
