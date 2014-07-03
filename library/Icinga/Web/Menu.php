<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Zend_Config;
use RecursiveIterator;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;

class Menu implements RecursiveIterator
{
    /**
     * The id of this menu
     *
     * @type string
     */
    protected $id;

    /**
     * The title of this menu
     *
     * Used for sorting when priority is unset or equal to other items
     *
     * @type string
     */
    protected $title;

    /**
     * The priority of this menu
     *
     * Used for sorting
     *
     * @type int
     */
    protected $priority = 100;

    /**
     * The url of this menu
     *
     * @type string
     */
    protected $url;

    /**
     * The path to the icon of this menu
     *
     * @type string
     */
    protected $icon;

    /**
     * The sub menus of this menu
     *
     * @type array
     */
    protected $subMenus = array();

    /**
     * Create a new menu
     *
     * @param   int             $id         The id of this menu
     * @param   Zend_Config     $config     The configuration for this menu
     */
    public function __construct($id, Zend_Config $config = null)
    {
        $this->id = $id;

        if ($config !== null) {
            foreach ($config as $key => $value) {
                $method = 'set' . implode('', array_map('ucfirst', explode('_', strtolower($key))));
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                }
            }
        }
    }

    /**
     * Create menu from the application's menu config file plus the config files from all enabled modules
     *
     * @return  self
     */
    public static function fromConfig()
    {
        $menu = new static('menu');
        $manager = Icinga::app()->getModuleManager();
        $modules = $manager->listEnabledModules();
        $menuConfigs = array(Config::app('menu'));

        foreach ($modules as $moduleName) {
            $moduleMenuConfig = Config::module($moduleName, 'menu');
            if (false === empty($moduleMenuConfig)) {
                $menuConfigs[] = $moduleMenuConfig;
            }
        }

        return $menu->loadSubMenus($menu->flattenConfigs($menuConfigs));
    }

    /**
     * Set the id of this menu
     *
     * @param   string  $id     The id to set for this menu
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return the id of this menu
     *
     * @return  string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the title of this menu
     *
     * @param   string  $title  The title to set for this menu
     *
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Return the title of this menu if set, otherwise its id
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title ? $this->title : $this->id;
    }

    /**
     * Set the priority of this menu
     *
     * @param   int     $priority   The priority to set for this menu
     *
     * @return  self
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
        return $this;
    }

    /**
     * Return the priority of this menu
     *
     * @return  int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the url of this menu
     *
     * @param   string  $url    The url to set for this menu
     *
     * @return  self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Return the url of this menu
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the path to the icon of this menu
     *
     * @param   string  $path   The path to the icon for this menu
     *
     * @return  self
     */
    public function setIcon($path)
    {
        $this->icon = $path;
        return $this;
    }

    /**
     * Return the path to the icon of this menu
     *
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Return whether this menu has any sub menus
     *
     * @return  bool
     */
    public function hasSubMenus()
    {
        return false === empty($this->subMenus);
    }

    /**
     * Add a sub menu to this menu
     *
     * @param   string          $id             The id of the menu to add
     * @param   Zend_Config     $itemConfig     The config with which to initialize the menu
     *
     * @return  self
     */
    public function addSubMenu($id, Zend_Config $menuConfig = null)
    {
        if (false === ($pos = strpos($id, '.'))) {
            $subMenu = new self($id, $menuConfig);
            $this->subMenus[$id] = $subMenu;
        } else {
            list($parentId, $id) = explode('.', $id, 2);

            if ($this->hasSubMenu($parentId)) {
                $parent = $this->getSubMenu($parentId);
            } else {
                $parent = $this->addSubMenu($parentId);
            }

            $subMenu = $parent->addSubMenu($id, $menuConfig);
        }

        return $subMenu;
    }

    /**
     * Return whether a sub menu with the given id exists
     *
     * @param   string  $id     The id of the sub menu
     *
     * @return  bool
     */
    public function hasSubMenu($id)
    {
        return array_key_exists($id, $this->subMenus);
    }

    /**
     * Get sub menu by its id
     *
     * @param   string      $id     The id of the sub menu
     *
     * @return  Menu                The found sub menu
     *
     * @throws  ProgrammingError    In case there is no sub menu with the given id to be found
     */
    public function getSubMenu($id)
    {
        if (false === $this->hasSubMenu($id)) {
            throw new ProgrammingError('Tried to get invalid sub menu "' . $id . '"');
        }

        return $this->subMenus[$id];
    }

    /**
     * Order this menu's sub menus based on their priority
     *
     * @return  self
     */
    public function order()
    {
        uasort($this->subMenus, array($this, 'cmpSubMenus'));
        foreach ($this->subMenus as $subMenu) {
            if ($subMenu->hasSubMenus()) {
                $subMenu->order();
            }
        }

        return $this;
    }

    /**
     * Compare sub menus based on priority and title
     *
     * @param   Menu    $a
     * @param   Menu    $b
     *
     * @return  int
     */
    protected function cmpSubMenus($a, $b)
    {
        if ($a->priority == $b->priority) {
            return $a->getTitle() > $b->getTitle() ? 1 : (
                $a->getTitle() < $b->getTitle() ? -1 : 0
            );
        }

        return $a->priority > $b->priority ? 1 : -1;
    }

    /**
     * Flatten configs
     *
     * @param   array   $configs    An two dimensional array of menu configurations
     *
     * @return  array               The flattened config, as key-value array
     */
    protected function flattenConfigs(array $configs)
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
     * Load the sub menus
     *
     * @param   array   $menus  The menus to load, as key-value array
     *
     * @return  self
     */
    protected function loadSubMenus(array $menus)
    {
        foreach ($menus as $menuId => $menuConfig) {
            $this->addSubMenu($menuId, $menuConfig);
        }

        return $this;
    }

    /**
     * Check whether the current menu node has any sub menus
     *
     * @return  bool
     */
    public function hasChildren()
    {
        $current = $this->current();
        if (false !== $current) {
            return $current->hasSubMenus();
        }

        return false;
    }

    /**
     * Return a iterator for the current menu node
     *
     * @return  RecursiveIterator
     */
    public function getChildren()
    {
        return $this->current();
    }

    /**
     * Rewind the iterator to its first menu node
     */
    public function rewind()
    {
        reset($this->subMenus);
    }

    /**
     * Return whether the iterator position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * Return the current menu node
     *
     * @return Menu
     */
    public function current()
    {
        return current($this->subMenus);
    }

    /**
     * Return the id of the current menu node
     *
     * @return string
     */
    public function key()
    {
        return key($this->subMenus);
    }

    /**
     * Move the iterator to the next menu node
     */
    public function next()
    {
        next($this->subMenus);
    }
}
