<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Application\ClassLoader;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;

/**
 * Base class for hooks extending the event view of monitored objects
 *
 * Extend this class if you want to extend the event view of monitored objects with custom HTML.
 */
abstract class EventDetailsExtensionHook
{
    /**
     * The module of the derived class
     *
     * @var Module
     */
    private $module;

    /**
     * Create a new hook
     *
     * @see init() For hook initialization.
     */
    final public function __construct()
    {
        $this->init();
    }
    /**
     * Overwrite this function for hook initialization, e.g. loading the hook's config
     */
    protected function init()
    {
    }


    /**
     * Shall return valid HTML to include in the detail view
     *
     * @param   object $event     The object to generate HTML for
     *
     * @return  string
     */
    abstract public function getHtmlForEvent($event);

    /**
     * Get the module of the derived class
     *
     * @return Module
     * @throws \Icinga\Exception\ProgrammingError
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
