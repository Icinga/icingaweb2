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

namespace Icinga\Web;

use Icinga\Logger\Logger;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Exception\NotReadableError;

class Menu extends MenuItem
{
    /**
     * Create menu from the application's menu config file plus the config files from all enabled modules
     *
     * @return  self
     */
    public static function fromConfig()
    {
        $menu = new static('menu');
        $manager = Icinga::app()->getModuleManager();

        try {
            $menuConfigs = array(Config::app('menu'));
        } catch (NotReadableError $e) {
            Logger::error($e);
            $menuConfigs = array();
        }

        try {
            $modules = $manager->listEnabledModules();
        } catch (NotReadableError $e) {
            Logger::error($e);
            $modules = array();
        }

        foreach ($modules as $moduleName) {
            try {
                $moduleMenuConfig = Config::module($moduleName, 'menu');
            } catch (NotReadableError $e) {
                Logger::error($e);
                $moduleMenuConfig = array();
            }

            if (!empty($moduleMenuConfig)) {
                $menuConfigs[] = $moduleMenuConfig;
            }
        }

        return $menu->loadMenuItems($menu->flattenConfigs($menuConfigs));
    }

    /**
     * Flatten configs
     *
     * @param   array   $configs    An two dimensional array of menu configurations
     *
     * @return  array               The flattened config, as key-value array
     */
    public function flattenConfigs(array $configs)
    {
        $flattened = array();
        foreach ($configs as $menuConfig) {
            foreach ($menuConfig as $section => $itemConfig) {
                while (array_key_exists($section, $flattened)) {
                    $section .= '_dup';
                }
                $flattened[$section] = $itemConfig;
            }
        }

        return $flattened;
    }

    /**
     * Load menu items
     *
     * @param   array   $items  The items to load, as key-value array
     *
     * @return  self
     */
    public function loadMenuItems(array $items)
    {
        foreach ($items as $id => $itemConfig) {
            $this->addChild($id, $itemConfig);
        }

        return $this;
    }
}
