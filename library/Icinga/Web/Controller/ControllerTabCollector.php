<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Controller;

use \Icinga\Application\Modules\Module;
use \Icinga\Application\Icinga;
use \Icinga\Web\Widget\Tabs;

/**
 *  Static helper class that collects tabs provided by the 'createProvidedTabs' method
 *  of controllers.
 *
 */
class ControllerTabCollector
{

    /**
     * Scan all controllers with the provided name
     * in the application and (loaded) module folders and collects their provided tabs
     *
     * @param string $controller        The name of the controllers to use for tab collection
     *
     * @return Tabs                     A @see Tabs instance containing the application tabs first
     *                                  followed by the tabs provided from the modules
     */
    public static function collectControllerTabs($controller)
    {
        require_once(Icinga::app()->getApplicationDir('/controllers/'.$controller.'.php'));

        $applicationTabs = $controller::createProvidedTabs();
        $moduleTabs = self::collectModuleTabs($controller);

        $tabs = new Tabs();
        foreach ($applicationTabs as $name => $tab) {
            $tabs->add($name, $tab);
        }

        foreach ($moduleTabs as $name => $tab) {
            // don't overwrite application tabs if the module wants to
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
     * If the module doesn't have the given controller or createProvidedTabs method in the controller
     * an empty array will be returned
     *
     * @param string $controller            The name of the controller that provides tabs via createProvidedTabs
     * @param Module $module                The module instance that provides the controller
     *
     * @return array
     */
    private static function createModuleConfigurationTabs($controller, Module $module)
    {
        $controllerDir = $module->getControllerDir();
        $name = $module->getName();

        $controllerDir = $controllerDir . '/' . $controller . '.php';
        $controllerName = ucfirst($name) . '_' . $controller;

        if (is_readable($controllerDir)) {
            require_once(realpath($controllerDir));
            if (!method_exists($controllerName, "createProvidedTabs")) {
                return array();
            }
            $tab = $controllerName::createProvidedTabs();
            if (!is_array($tab)) {
                $tab = array($name => $tab);
            }
            return $tab;
        }
        return array();
    }
}
