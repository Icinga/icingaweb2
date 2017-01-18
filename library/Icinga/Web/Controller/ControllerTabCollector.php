<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Icinga\Application\Modules\Module;
use Icinga\Application\Icinga;
use Icinga\Web\Widget\Tabs;

/**
 *  Static helper class that collects tabs provided by the 'createProvidedTabs' method of controllers
 */
class ControllerTabCollector
{
    /**
     * Scan all controllers with given name in the application and (loaded) module folders and collects their provided
     * tabs
     *
     * @param   string  $controllerName The name of the controllers to use for tab collection
     *
     * @return  Tabs                    A {@link Tabs} instance containing the application tabs first followed by the
     *                                  tabs provided from the modules
     */
    public static function collectControllerTabs($controllerName)
    {
        $controller = '\Icinga\\' . Dispatcher::CONTROLLER_NAMESPACE . '\\' . $controllerName;
        $applicationTabs = $controller::createProvidedTabs();
        $moduleTabs = self::collectModuleTabs($controllerName);

        $tabs = new Tabs();
        foreach ($applicationTabs as $name => $tab) {
            $tabs->add($name, $tab);
        }

        foreach ($moduleTabs as $name => $tab) {
            // Don't overwrite application tabs if the module wants to
            if ($tabs->has($name)) {
                continue;
            }
            $tabs->add($name, $tab);
        }
        return $tabs;
    }

    /**
     * Collect module tabs for all modules containing the given controller
     *
     * @param string $controller        The controller name to use for tab collection
     *
     * @return array                    An array of Tabs objects or arrays containing Tab descriptions
     */
    private static function collectModuleTabs($controller)
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $modules = $moduleManager->listEnabledModules();
        $tabs = array();
        foreach ($modules as $module) {
            $tabs += self::createModuleConfigurationTabs($controller, $moduleManager->getModule($module));
        }

        return $tabs;
    }

    /**
     * Collects the tabs from the createProvidedTabs() method in the configuration controller
     *
     * If the module doesn't have the given controller or createProvidedTabs method in the controller an empty array
     * will be returned
     *
     * @param   string  $controllerName The name of the controller that provides tabs via createProvidedTabs
     * @param   Module  $module         The module instance that provides the controller
     *
     * @return  array
     */
    private static function createModuleConfigurationTabs($controllerName, Module $module)
    {
        // TODO(el): Only works for controllers w/o namepsace: https://dev.icinga.com/issues/4149
        $controllerDir = $module->getControllerDir();
        $name = $module->getName();

        $controllerDir = $controllerDir . '/' . $controllerName . '.php';
        $controllerName = ucfirst($name) . '_' . $controllerName;

        if (is_readable($controllerDir)) {
            require_once(realpath($controllerDir));
            if (! method_exists($controllerName, 'createProvidedTabs')) {
                return array();
            }
            $tab = $controllerName::createProvidedTabs();
            if (! is_array($tab)) {
                $tab = array($name => $tab);
            }
            return $tab;
        }
        return array();
    }
}
