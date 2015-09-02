<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Icinga\Authentication\Auth;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\String;

/**
 * Container for navigation items
 *
 * Usage example:
 * <code>
 * <?php
 *
 * namespace Icinga\Example;
 *
 * use Icinga\Web\Navigation\DropdownItem;
 * use Icinga\Web\Navigation\Navigation;
 * use Icinga\Web\Navigation\NavigationItem;
 *
 * $navigation = new Navigation();
 * $navigation->setLayout(Navigation::LAYOUT_TABS);
 * $home = new NavigationItem();
 * $home
 *     ->setIcon('home')
 *     ->setLabel('Home');
 *     ->setUrl('/home');
 * $logout = new NavigationItem();
 * $logout
 *     ->setIcon('logout')
 *     ->setLabel('Logout')
 *     ->setUrl('/logout');
 * $dropdown = new DropdownItem();
 * $dropdown
 *     ->setIcon('user')
 *     ->setLabel('Preferences');
 * $preferences = new NavigationItem();
 * $preferences
 *     ->setIcon('preferences');
 *     ->setLabel('preferences')
 *     ->setUrl('/preferences');
 * $dropdown->addChild($preferences);
 * $navigation
 *     ->addItem($home)
 *     ->addItem($logout);
 *     ->addItem($dropdown);
 * echo $navigation
 *     ->getRenderer()
 *     ->setCssClass('example-nav')
 *     ->render();
 */
class Navigation implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The class namespace where to locate navigation type classes
     *
     * @var string
     */
    const NAVIGATION_NS = 'Web\\Navigation';

    /**
     * Flag for dropdown layout
     *
     * @var int
     */
    const LAYOUT_DROPDOWN = 1;

    /**
     * Flag for tabs layout
     *
     * @var int
     */
    const LAYOUT_TABS = 2;

    /**
     * Known navigation types
     *
     * @var array
     */
    protected static $types;

    /**
     * Navigation items
     *
     * @var NavigationItem[]
     */
    protected $items = array();

    /**
     * Navigation layout
     *
     * @var int
     */
    protected $layout;

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Create and return a new navigation item for the given configuration
     *
     * @param   string              $name
     * @param   array|ConfigObject  $properties
     *
     * @return  NavigationItem
     *
     * @throws  InvalidArgumentException    If the $properties argument is neither an array nor a ConfigObject
     */
    public function createItem($name, $properties)
    {
        if ($properties instanceof ConfigObject) {
            $properties = $properties->toArray();
        } elseif (! is_array($properties)) {
            throw new InvalidArgumentException('Argument $properties must be of type array or ConfigObject');
        }

        $itemType = isset($properties['type']) ? String::cname($properties['type'], '-') : 'NavigationItem';
        if (! empty($this->types) && isset($this->types[$itemType])) {
            return new $this->types[$itemType]($name, $properties);
        }

        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            $classPath = 'Icinga\\Module\\' . $module->getName() . '\\' . static::NAVIGATION_NS . '\\' . $itemType;
            if (class_exists($classPath)) {
                $item = $classPath($name, $properties);
                break;
            }
        }

        if ($item === null) {
            $classPath = 'Icinga\\' . static::NAVIGATION_NS . '\\' . $itemType;
            if (class_exists($classPath)) {
                $item = new $classPath($name, $properties);
            }
        }

        if ($item === null) {
            Logger::debug(
                'Failed to find custom navigation item class %s for item %s. Using base class NavigationItem now',
                $itemType,
                $name
            );

            $item = new NavigationItem($name, $properties);
            $this->types[$itemType] = 'Icinga\\Web\\Navigation\\NavigationItem';
        } elseif (! $item instanceof NavigationItem) {
            throw new ProgrammingError('Class %s must inherit from NavigationItem', $classPath);
        } else {
            $this->types[$itemType] = $classPath;
        }

        return $item;
    }

    /**
     * Add a navigation item
     *
     * If you do not pass an instance of NavigationItem, this will only add the item
     * if it does not require a permission or the current user has the permission.
     *
     * @param   string|NavigationItem   $name       The name of the item or an instance of NavigationItem
     * @param   array                   $properties The properties of the item to add (Ignored if $name is not a string)
     *
     * @return  bool                                Whether the item was added or not
     *
     * @throws  InvalidArgumentException            In case $name is neither a string nor an instance of NavigationItem
     */
    public function addItem($name, array $properties = array())
    {
        if (is_string($name)) {
            if (isset($properties['permission'])) {
                if (! Auth::getInstance()->hasPermission($properties['permission'])) {
                    return false;
                }

                unset($properties['permission']);
            }

            $item = $this->createItem($name, $properties);
        } elseif (! $name instanceof NavigationItem) {
            throw new InvalidArgumentException('Argument $name must be of type string or NavigationItem');
        } else {
            $item = $name;
        }

        $this->items[] = $item;
        return true;
    }

    /**
     * Get the item with the given ID
     *
     * @param   mixed   $id
     * @param   mixed   $default
     *
     * @return  NavigationItem|mixed
     */
    public function getItem($id, $default = null)
    {
        if (isset($this->items[$id])) {
            return $this->items[$id];
        }
        return $default;
    }

    /**
     * Get the items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get whether the navigation has items
     *
     * @return bool
     */
    public function hasItems()
    {
        return ! empty($this->items);
    }

    /**
     * Get whether the navigation is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get the layout
     *
     * @return int
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the layout
     *
     * @param   int $layout
     *
     * @return  $this
     */
    public function setLayout($layout)
    {
        $this->layout = (int) $layout;
        return $this;
    }

    /**
     * Get the navigation renderer
     *
     * @return RecursiveNavigationRenderer
     */
    public function getRenderer()
    {
        return new RecursiveNavigationRenderer($this);
    }
}

