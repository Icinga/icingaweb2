<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\String;

/**
 * Container for navigation items
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
     * This navigation's items
     *
     * @var NavigationItem[]
     */
    protected $items = array();

    /**
     * This navigation's layout
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
        $this->order();
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
        if (! empty(static::$types) && isset(static::$types[$itemType])) {
            return new static::$types[$itemType]($name, $properties);
        }

        $item = null;
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            $classPath = 'Icinga\\Module\\' . $module->getName() . '\\' . static::NAVIGATION_NS . '\\' . $itemType;
            if (class_exists($classPath)) {
                $item = new $classPath($name, $properties);
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
            static::$types[$itemType] = 'Icinga\\Web\\Navigation\\NavigationItem';
        } elseif (! $item instanceof NavigationItem) {
            throw new ProgrammingError('Class %s must inherit from NavigationItem', $classPath);
        } else {
            static::$types[$itemType] = $classPath;
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

        $this->items[$item->getName()] = $item;
        return true;
    }

    /**
     * Return the item with the given name
     *
     * @param   string  $name
     * @param   mixed   $default
     *
     * @return  NavigationItem|mixed
     */
    public function getItem($name, $default = null)
    {
        return isset($this->items[$name]) ? $this->items[$name] : $default;
    }

    /**
     * Return this navigation's items
     *
     * @return  array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Return whether this navigation is empty
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Return this navigation's layout
     *
     * @return  int
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set this navigation's layout
     *
     * @param   int     $layout
     *
     * @return  $this
     */
    public function setLayout($layout)
    {
        $this->layout = (int) $layout;
        return $this;
    }

    /**
     * Create and return the renderer for this navigation
     *
     * @return  RecursiveNavigationRenderer
     */
    public function getRenderer()
    {
        return new RecursiveNavigationRenderer($this);
    }

    /**
     * Order this navigation's items
     *
     * @return  $this
     */
    public function order()
    {
        uasort($this->items, array($this, 'compareItems'));
        foreach ($this->items as $item) {
            if ($item->hasChildren()) {
                $item->getChildren()->order();
            }
        }

        return $this;
    }

    /**
     * Return whether the first item is less than, more than or equal to the second one
     *
     * @param   NavigationItem  $a
     * @param   NavigationItem  $b
     *
     * @return  int
     */
    protected function compareItems(NavigationItem $a, NavigationItem $b)
    {
        if ($a->getPriority() === $b->getPriority()) {
            return strcasecmp($a->getLabel(), $b->getLabel());
        }

        return $a->getPriority() > $b->getPriority() ? 1 : -1;
    }

    /**
     * Merge this navigation with the given one
     *
     * Any duplicate items of this navigation will be overwritten by the given navigation's items.
     *
     * @param   Navigation  $navigation
     *
     * @return  $this
     */
    public function merge(Navigation $navigation)
    {
        foreach ($navigation as $item) {
            /** @var $item NavigationItem */
            if (($existingItem = $this->getItem($item->getName())) !== null) {
                if ($existingItem->conflictsWith($item)) {
                    $name = $item->getName();
                    do {
                        if (preg_match('~_(\d+)$~', $name, $matches)) {
                            $name = preg_replace('~_\d+$~', $matches[1] + 1, $name);
                        } else {
                            $name .= '_2';
                        }
                    } while ($this->getItem($name) !== null);

                    $this->addItem($item->setName($name));
                } else {
                    $existingItem->merge($item);
                }
            } else {
                $this->addItem($item);
            }
        }

        return $this;
    }

    /**
     * Create and return a new set of navigation items for the given configuration
     *
     * @param   Config  $config
     *
     * @return  Navigation
     *
     * @throws  ConfigurationError  In case a referenced parent does not exist
     */
    public static function fromConfig(Config $config)
    {
        $flattened = $topLevel = array();
        foreach ($config as $sectionName => $sectionConfig) {
            $parentName = $sectionConfig->parent;
            unset($sectionConfig->parent);

            if (! $parentName) {
                $topLevel[$sectionName] = $sectionConfig->toArray();
                $flattened[$sectionName] = & $topLevel[$sectionName];
            } elseif (isset($flattened[$parentName])) {
                $flattened[$parentName]['children'][$sectionName] = $sectionConfig->toArray();
                $flattened[$sectionName] = & $flattened[$parentName]['children'][$sectionName];
            } else {
                throw new ConfigurationError(
                    t(
                        'Failed to add navigation item "%s". Parent "%s" not found. Make'
                        . ' sure that the parent is defined prior to its child(s).'
                    ),
                    $sectionName,
                    $parentName
                );
            }
        }

        return static::fromArray($topLevel);
    }

    /**
     * Create and return a new set of navigation items for the given array
     *
     * @param   array   $array
     *
     * @return  Navigation
     */
    public static function fromArray(array $array)
    {
        $navigation = new static();
        foreach ($array as $name => $properties) {
            $navigation->addItem($name, $properties);
        }

        return $navigation;
    }
}

