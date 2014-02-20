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

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\NotReadableError;

class Menu extends MenuItem
{
    /**
     * Create menu from the application's menu config file plus the config files from all enabled modules
     *
     * @return Menu
     */
    public static function fromConfig() {
        $menu = new static('menu');
        $manager =  Icinga::app()->getModuleManager();
        try {
            $menuConfigs = array(Config::app('menu'));
        } catch (NotReadableError $e) {
            Logger::exception($e);
            $menuConfigs = array();
        }
        try {

            foreach ($manager->listEnabledModules() as $moduleName) {
                $moduleMenuConfig = Config::module($moduleName, 'menu');
                if ($moduleMenuConfig) {
                    $menuConfigs[] = $moduleMenuConfig;
                }
            }
        } catch (NotReadableError $e) {
            Logger::exception($e);
        }
        return $menu->loadMenuItems($menu->flattenConfigs($menuConfigs));
    }

    /**
     * Flatten configs into a key-value array
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
        ksort($flattened);
        return $flattened;
    }

    /**
     * Load menu items from a key-value array
     */
    public function loadMenuItems(array $flattened)
    {
        foreach ($flattened as $id => $itemConfig) {
            $this->addChild($id, $itemConfig);
        }
        return $this;
    }

}
