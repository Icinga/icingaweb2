<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use RecursiveIterator;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Manager;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Menu\MenuItemRenderer;

class Menu implements RecursiveIterator
{
    /**
     * The id of this menu
     *
     * @var string
     */
    protected $id;

    /**
     * The title of this menu
     *
     * Used for sorting when priority is unset or equal to other items
     *
     * @var string
     */
    protected $title;

    /**
     * The priority of this menu
     *
     * Used for sorting
     *
     * @var int
     */
    protected $priority = 100;

    /**
     * The url of this menu
     *
     * @var string
     */
    protected $url;

    /**
     * The path to the icon of this menu
     *
     * @var string
     */
    protected $icon;

    /**
     * The sub menus of this menu
     *
     * @var array
     */
    protected $subMenus = array();

    /**
     * A custom item renderer used instead of the default rendering logic
     *
     * @var MenuItemRenderer
     */
    protected $itemRenderer = null;

    /*
     * Parent menu
     *
     * @var Menu
     */
    protected $parent;

    /**
     * Permission a user is required to have granted to display the menu item
     *
     * If a permission is set, authentication is of course required.
     *
     * Note that only one required permission can be set yet.
     *
     * @var string|null
     */
    protected $permission;

    /**
     * Create a new menu
     *
     * @param   int             $id         The id of this menu
     * @param   ConfigObject    $config     The configuration for this menu
     * @param   Menu            $parent     Parent menu
     */
    public function __construct($id, ConfigObject $config = null, Menu $parent = null)
    {
        $this->id = $id;
        if ($parent !== null) {
            $this->parent = $parent;
        }
        $this->setProperties($config);
    }

    /**
     * Set all given properties
     *
     * @param   array|ConfigObject  $props Property list
     *
     * @return  $this
     *
     * @throws  ConfigurationError  If a property is invalid
     */
    public function setProperties($props = null)
    {
        if ($props !== null) {
            foreach ($props as $key => $value) {
                $method = 'set' . implode('', array_map('ucfirst', explode('_', strtolower($key))));
                if ($key === 'renderer') {
                    $value = '\\' . ltrim($value, '\\');
                    if (class_exists($value)) {
                        $value = new $value;
                    } else {
                        $class = '\Icinga\Web\Menu' . $value;
                        if (!class_exists($class)) {
                            throw new ConfigurationError(
                                sprintf('ItemRenderer with class "%s" does not exist', $class)
                            );
                        }
                        $value = new $class;
                    }
                }
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                } else {
                    throw new ConfigurationError(
                        sprintf('Menu got invalid property "%s"', $key)
                    );
                }
            }
        }
        return $this;
    }

    /**
     * Get Properties
     *
     * @return array
     */
    public function getProperties()
    {
        $props = array();
        $keys = array('url', 'icon', 'priority', 'title');
        foreach ($keys as $key) {
            $func = 'get' . ucfirst($key);
            if (null !== ($val = $this->{$func}())) {
                $props[$key] = $val;
            }
        }
        return $props;
    }

    /**
     * Whether this Menu conflicts with the given Menu object
     *
     * @param   Menu $menu
     *
     * @return  bool
     */
    public function conflictsWith(Menu $menu)
    {
        if ($menu->getUrl() === null || $this->getUrl() === null) {
            return false;
        }
        return $menu->getUrl() !== $this->getUrl();
    }

    /**
     * Create menu from the application's menu config file plus the config files from all enabled modules
     *
     * @return      static
     *
     * @deprecated  THIS IS OBSOLETE. LEFT HERE FOR FUTURE USE WITH USER-SPECIFIC MODULES
     */
    public static function fromConfig()
    {
        $menu = new static('menu');
        $manager = Icinga::app()->getModuleManager();
        $modules = $manager->listEnabledModules();
        $menuConfigs = array(Config::app('menu'));

        foreach ($modules as $moduleName) {
            $moduleMenuConfig = Config::module($moduleName, 'menu');
            if (! $moduleMenuConfig->isEmpty()) {
                $menuConfigs[] = $moduleMenuConfig;
            }
        }

        return $menu->loadSubMenus($menu->flattenConfigs($menuConfigs));
    }

    /**
     * Create menu from the application's menu config plus menu entries provided by all enabled modules
     *
     * @return static
     */
    public static function load()
    {
        /** @var $menu \Icinga\Web\Menu */
        $menu = new static('menu');
        $menu->addMainMenuItems();
        $manager = Icinga::app()->getModuleManager();
        foreach ($manager->getLoadedModules() as $module) {
            /** @var $module \Icinga\Application\Modules\Module */
            $menu->mergeSubMenus($module->getMenuItems());
        }
        return $menu->order();
    }

    /**
     * Add Applications Main Menu Items
     */
    protected function addMainMenuItems()
    {
        $auth = Manager::getInstance();

        if ($auth->isAuthenticated()) {

            $this->add(t('Dashboard'), array(
                'url'      => 'dashboard',
                'icon'     => 'dashboard',
                'priority' => 10
            ));

            $section = $this->add(t('System'), array(
                'icon'     => 'wrench',
                'priority' => 200
            ));
            $section->add(t('Configuration'), array(
                'url'           => 'config',
                'permission'    => 'config/application/*',
                'priority'      => 300
            ));
            $section->add(t('Modules'), array(
                'url'           => 'config/modules',
                'permission'    => 'config/modules',
                'priority'      => 400
            ));

            if (Logger::writesToFile()) {
                $section->add(t('Application Log'), array(
                    'url'      => 'list/applicationlog',
                    'priority' => 500
                ));
            }

            $section = $this->add($auth->getUser()->getUsername(), array(
                'icon'     => 'user',
                'priority' => 600
            ));
            $section->add(t('Preferences'), array(
                'url'      => 'preference',
                'priority' => 601
            ));

            $section->add(t('Logout'), array(
                'url'      => 'authentication/logout',
                'priority' => 700,
                'renderer' => 'ForeignMenuItemRenderer'
            ));
        }
    }

    /**
     * Set the id of this menu
     *
     * @param   string  $id     The id to set for this menu
     *
     * @return  $this
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
     * Get our ID without invalid characters
     *
     * @return string the ID
     */
    protected function getSafeHtmlId()
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', $this->getId());
    }

    /**
     * Get a unique menu item id
     *
     * @return string the ID
     */
    public function getUniqueId()
    {
        if ($this->parent === null) {
            return 'menuitem-' . $this->getSafeHtmlId();
        } else {
            return $this->parent->getUniqueId() . '-' . $this->getSafeHtmlId();
        }
    }

    /**
     * Set the title of this menu
     *
     * @param   string  $title  The title to set for this menu
     *
     * @return  $this
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
     * @return  $this
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
     * @param   Url|string  $url    The url to set for this menu
     *
     * @return  $this
     */
    public function setUrl($url)
    {
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::fromPath($url);
        }
        return $this;
    }

    /**
     * Return the url of this menu
     *
     * @return  Url
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
     * @return  $this
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
     * Get the class that renders the current menu item
     *
     * @return MenuItemRenderer
     */
    public function getRenderer()
    {
        return $this->itemRenderer;
    }

    /**
     * Set the class that renders the current menu item
     *
     * @param MenuItemRenderer $renderer
     */
    public function setRenderer(MenuItemRenderer $renderer)
    {
        $this->itemRenderer = $renderer;
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
     * @param   ConfigObject    $menuConfig     The config with which to initialize the menu
     *
     * @return  static
     */
    public function addSubMenu($id, ConfigObject $menuConfig = null)
    {
        $subMenu = new static($id, $menuConfig, $this);
        $this->subMenus[$id] = $subMenu;
        return $subMenu;
    }

    /**
     * Get the permission a user is required to have granted to display the menu item
     *
     * @return string|null
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set permission a user is required to have granted to display the menu item
     *
     * If a permission is set, authentication is of course required.
     *
     * @param   string  $permission
     *
     * @return  $this
     */
    public function setPermission($permission)
    {
        $this->permission = (string) $permission;
        return $this;
    }

    /**
     * Merge Sub Menus
     *
     * @param   array $submenus
     *
     * @return  $this
     */
    public function mergeSubMenus(array $submenus)
    {
        foreach ($submenus as $menu) {
            $this->mergeSubMenu($menu);
        }
        return $this;
    }

    /**
     * Merge Sub Menu
     *
     * @param   Menu $menu
     *
     * @return  static
     */
    public function mergeSubMenu(Menu $menu)
    {
        $name = $menu->getId();
        if (array_key_exists($name, $this->subMenus)) {
            /** @var $current Menu */
            $current = $this->subMenus[$name];
            if ($current->conflictsWith($menu)) {
                while (array_key_exists($name, $this->subMenus)) {
                    if (preg_match('/_(\d+)$/', $name, $m)) {
                        $name = preg_replace('/_\d+$/', $m[1]++, $name);
                    } else {
                        $name .= '_2';
                    }
                }
                $menu->setId($name);
                $this->subMenus[$name] = $menu;
            } else {
                $current->setProperties($menu->getProperties());
                foreach ($menu->subMenus as $child) {
                    $current->mergeSubMenu($child);
                }
            }
        } else {
            $this->subMenus[$name] = $menu;
        }

        return $this->subMenus[$name];
    }

    /**
     * Add a Menu
     *
     * @param   $name
     * @param   array $config
     *
     * @return  static
     */
    public function add($name, $config = array())
    {
        return $this->addSubMenu($name, new ConfigObject($config));
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
     * @return  static              The found sub menu
     *
     * @throws  ProgrammingError    In case there is no sub menu with the given id to be found
     */
    public function getSubMenu($id)
    {
        if (false === $this->hasSubMenu($id)) {
            throw new ProgrammingError(
                'Tried to get invalid sub menu "%s"',
                $id
            );
        }

        return $this->subMenus[$id];
    }

    /**
     * Order this menu's sub menus based on their priority
     *
     * @return  $this
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
     * @return  $this
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
     * @return static
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

    /**
     * PHP 5.3 GC should not leak, but just to be on the safe side...
     */
    public function __destruct()
    {
        $this->parent = null;
    }
}
