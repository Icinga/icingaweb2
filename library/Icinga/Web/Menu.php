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
use Icinga\Application\Modules\Manager as ModulManager;
use Icinga\Application\Icinga;

class MenuItem
{
    /**
     * MenuItem name
     *
     * @type    string
     */
    private $name;
    
    /**
     * MenuItem titel
     * 
     * @type    string
     */
    private $title;
    
    /**
     * MenuItem priority
     * 
     * @type    int
     */
    private $priority;
    
    /**
     * MenuItem url
     * 
     * @type    string
     */
    private $url;
    
    /**
     * MenuItem icon path
     * 
     * @type    string
     */
    private $icon;
    
    /**
     * MenuItem icon class
     * 
     * @type    string
     */
    private $icon_class;
    
    /**
     * MenuItem children array
     * 
     * @type    array
     */
    private $children = array();
    
    
    /**
     * Create a new MenuItem
     */
    public function __construct($name='', $title='')
    {
        ($name != '') ? $this->name = $name: $this->name = $title;
        $this->title = $title;
        $this->priority = 100;
    }

    /**
     * Set name for MenuItems
     *
     * @param   string  name
     * 
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Get name from MenuItem
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set title for MenuItems
     *
     * @param   string  title
     * 
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    
    
    /**
     * Get title from MenuItem
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title ? $this->title : $this->name;
    }
    
    /**
     * Set priority for MenuItems
     *
     * @param   int  priority
     * 
     * @return  self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Get priority from MenuItem
     *
     * @return  int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set url for MenuItems
     *
     * @param   string  url
     * 
     * @return  self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Get url from MenuItem
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * Set icon for MenuItems
     *
     * @param   string  icon
     * 
     * @return  self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * Get icon from MenuItem
     *
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set icon class for MenuItems
     *
     * @param   string  icon class
     * 
     * @return  self
     */
    public function setIconClass($iconClass)
    {
        $this->icon_class = $iconClass;
        return $this;
    }
    
    /**
     * Get icon class from MenuItem
     *
     * @return  string
     */
    public function getIconClass()
    {
        return $this->icon_class;
    }
    
    /**
     * Add a child to MenuItem
     * 
     * @param   string  identifier
     * @param   array   props
     *
     * @return  self
     */
    public function addChild($identifier, $props)
    {
        if (false === ($pos = strpos($identifier, '.'))) {
            $menuItem = new MenuItem($identifier);
            $menuItem
                    ->setTitle($props->title)
                    ->setPriority($props->priority)
                    ->setUrl($props->url)
                    ->setIcon($props->icon)
                    ->setIconClass($props->icon_class);
            $this->children[$identifier] = $menuItem;
        } else {
            $parent = substr($identifier, 0, $pos);
            $identifier = substr($identifier, $pos + 1);
            $this->getChild($parent)->addChild($identifier, $props);
        }
        return $this;
    }

    /**
     * Check if MenuItem has Childs
     *
     * @return  bool
     */
    public function hasChildren()
    {
        return ! empty($this->children);
    }
    
    /**
     * Get children from MenuItem
     *
     * return all children proper sortet of the current MenuItem
     * 
     * @return  array
     */
    public function getChildren()
    {
        usort($this->children, array($this, 'cmpChildren'));
        return $this->children;
    }
    
    /**
     * Get child from MenuItem
     *
     * @param   string      identifier
     * 
     * @return  MenuItem
     */
    public function getChild($identifier)
    {
        return $this->children[$identifier];
    }
    
    
    /**
     * Compare children
     * 
     * compare two children against each other based on priority and name
     *
     * @return  array
     */
    protected function cmpChildren($a, $b)
    {   
        if ($a->priority == $b->priority) {
            return ($a->getTitle() > $b->getTitle()) ? +1 : -1;
        }
        return ($a->priority > $b->priority) ? +1 : -1;
    }
}


class Menu extends MenuItem
{
    /**
     * ZendConfig   cfg
     *
     * @type    Config
     */
    private $cfg;
    
    /**
     * Config
     *
     * @type    config
     */
    private $config;
    
    /**
     * Load MenuItems from all Configs
     *
     * @return  Menu
     */
    public static function fromConfig(){
        $menu = new Menu();
        $manager =  Icinga::app()->getModuleManager(); 
        $menu->cfg[] = Config::app('menu');
        foreach ($manager->listEnabledModules() as $moduleName) 
        { 
            $menu->cfg[] = Config::module($moduleName, 'menu');
        } 
        $menu->mergeConfigs();
        $menu->loadMenuItems();
        return $menu;
    }
    
    /**
     * Merge all configs 
     * 
     * merge all configs and set suffix if duplicate entry exist
     *
     */
    private function mergeConfigs()
    {
        $this->config = array();
        foreach ($this->cfg as $config){
            foreach ($config as $identifier => $keys){
                while (array_key_exists($identifier, $this->config)) {
                    $identifier .= '_dup';
                }
                $this->config[$identifier] = $keys;
            }
        }
        ksort($this->config);
    }
    
    /**
     * Load menu items
     * 
     * load MenuItems based on the merged config
     */
    private function loadMenuItems()
    {
        foreach ($this->config as $id => $props) {
            $this->addChild($id, $props);
        }
    }
    
}
