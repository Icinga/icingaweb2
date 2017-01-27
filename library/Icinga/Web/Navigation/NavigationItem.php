<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Navigation\Renderer\NavigationItemRenderer;
use Icinga\Web\Url;

/**
 * A navigation item
 */
class NavigationItem implements IteratorAggregate
{
    /**
     * Alternative markup element for items without a url
     *
     * @var string
     */
    const LINK_ALTERNATIVE = 'span';

    /**
     * The class namespace where to locate navigation type renderer classes
     */
    const RENDERER_NS = 'Web\\Navigation\\Renderer';

    /**
     * Whether this item is active
     *
     * @var bool
     */
    protected $active;

    /**
     * The CSS class used for the outer li element
     *
     * @var string
     */
    protected $cssClass;

    /**
     * This item's priority
     *
     * The priority defines when the item is rendered in relation to its parent's childs.
     *
     * @var int
     */
    protected $priority;

    /**
     * The attributes of this item's element
     *
     * @var array
     */
    protected $attributes;

    /**
     * This item's children
     *
     * @var Navigation
     */
    protected $children;

    /**
     * This item's icon
     *
     * @var string
     */
    protected $icon;

    /**
     * This item's name
     *
     * @var string
     */
    protected $name;

    /**
     * This item's label
     *
     * @var string
     */
    protected $label;

    /**
     * This item's parent
     *
     * @var NavigationItem
     */
    protected $parent;

    /**
     * This item's url
     *
     * @var Url
     */
    protected $url;

    /**
     * This item's url target
     *
     * @var string
     */
    protected $target;

    /**
     * Additional parameters for this item's url
     *
     * @var array
     */
    protected $urlParameters;

    /**
     * This item's renderer
     *
     * @var NavigationItemRenderer
     */
    protected $renderer;

    /**
     * Whether to render this item
     *
     * @var bool
     */
    protected $render;

    /**
     * Create a new NavigationItem
     *
     * @param   string  $name
     * @param   array   $properties
     */
    public function __construct($name, array $properties = null)
    {
        $this->setName($name);
        $this->children = new Navigation();

        if (! empty($properties)) {
            $this->setProperties($properties);
        }

        $this->init();
    }

    /**
     * Initialize this NavigationItem
     */
    public function init()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->getChildren();
    }

    /**
     * Return whether this item is active
     *
     * @return  bool
     */
    public function getActive()
    {
        if ($this->active === null) {
            $this->active = false;
            if ($this->getUrl() !== null && Icinga::app()->getRequest()->getUrl()->matches($this->getUrl())) {
                $this->setActive();
            } elseif ($this->hasChildren()) {
                foreach ($this->getChildren() as $item) {
                    /** @var NavigationItem $item */
                    if ($item->getActive()) {
                        // Do nothing, a true active state is automatically passed to all parents
                    }
                }
            }
        }

        return $this->active;
    }

    /**
     * Set whether this item is active
     *
     * If it's active and has a parent, the parent gets activated as well.
     *
     * @param   bool    $active
     *
     * @return  $this
     */
    public function setActive($active = true)
    {
        $this->active = (bool) $active;
        if ($this->active && $this->getParent() !== null) {
            $this->getParent()->setActive();
        }

        return $this;
    }

    /**
     * Get the CSS class used for the outer li element
     *
     * @return  string
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * Set the CSS class to use for the outer li element
     *
     * @param   string  $class
     *
     * @return  $this
     */
    public function setCssClass($class)
    {
        $this->cssClass = (string) $class;
        return $this;
    }

    /**
     * Return this item's priority
     *
     * @return  int
     */
    public function getPriority()
    {
        return $this->priority !== null ? $this->priority : 100;
    }

    /**
     * Set this item's priority
     *
     * @param   int     $priority
     *
     * @return  $this
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
        return $this;
    }

    /**
     * Return the value of the given element attribute
     *
     * @param   string  $name
     * @param   mixed   $default
     *
     * @return  mixed
     */
    public function getAttribute($name, $default = null)
    {
        $attributes = $this->getAttributes();
        return array_key_exists($name, $attributes) ? $attributes[$name] : $default;
    }

    /**
     * Set the value of the given element attribute
     *
     * @param   string  $name
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Return the attributes of this item's element
     *
     * @return  array
     */
    public function getAttributes()
    {
        return $this->attributes ?: array();
    }

    /**
     * Set the attributes of this item's element
     *
     * @param   array   $attributes
     *
     * @return  $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Add a child to this item
     *
     * If the child is active this item gets activated as well.
     *
     * @param   NavigationItem  $child
     *
     * @return  $this
     */
    public function addChild(NavigationItem $child)
    {
        $this->getChildren()->addItem($child->setParent($this));
        if ($child->getActive()) {
            $this->setActive();
        }

        return $this;
    }

    /**
     * Return this item's children
     *
     * @return  Navigation
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Return whether this item has any children
     *
     * @return  bool
     */
    public function hasChildren()
    {
        return ! $this->getChildren()->isEmpty();
    }

    /**
     * Set this item's children
     *
     * @param   array|Navigation  $children
     *
     * @return  $this
     */
    public function setChildren($children)
    {
        if (is_array($children)) {
            $children = Navigation::fromArray($children);
        } elseif (! $children instanceof Navigation) {
            throw new InvalidArgumentException('Argument $children must be of type array or Navigation');
        }

        foreach ($children as $item) {
            $item->setParent($this);
        }

        $this->children = $children;
        return $this;
    }

    /**
     * Return this item's icon
     *
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set this item's icon
     *
     * @param   string  $icon
     *
     * @return  $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Return this item's name escaped with only ASCII chars and/or digits
     *
     * @return  string
     */
    protected function getEscapedName()
    {
        return preg_replace('~[^a-zA-Z0-9]~', '_', $this->getName());
    }

    /**
     * Return a unique version of this item's name
     *
     * @return  string
     */
    public function getUniqueName()
    {
        if ($this->getParent() === null) {
            return 'navigation-' . $this->getEscapedName();
        }

        return $this->getParent()->getUniqueName() . '-' . $this->getEscapedName();
    }

    /**
     * Return this item's name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set this item's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set this item's parent
     *
     * @param   NavigationItem  $parent
     *
     * @return  $this
     */
    public function setParent(NavigationItem $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Return this item's parent
     *
     * @return  NavigationItem
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return this item's label
     *
     * @return  string
     */
    public function getLabel()
    {
        return $this->label !== null ? $this->label : $this->getName();
    }

    /**
     * Set this item's label
     *
     * @param   string  $label
     *
     * @return  $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set this item's url target
     *
     * @param   string  $target
     *
     * @return  $this
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Return this item's url target
     *
     * @return  string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Return this item's url
     *
     * @return  Url
     */
    public function getUrl()
    {
        if ($this->url === null && $this->hasChildren()) {
            $this->setUrl(Url::fromPath('#'));
        }

        return $this->url;
    }

    /**
     * Set this item's url
     *
     * @param   Url|string  $url
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    If the given url is neither of type
     */
    public function setUrl($url)
    {
        if (is_string($url)) {
            $url = Url::fromPath($url);
        } elseif (! $url instanceof Url) {
            throw new InvalidArgumentException('Argument $url must be of type string or Url');
        }

        $this->url = $url;
        return $this;
    }

    /**
     * Return the value of the given url parameter
     *
     * @param   string  $name
     * @param   mixed   $default
     *
     * @return  mixed
     */
    public function getUrlParameter($name, $default = null)
    {
        $parameters = $this->getUrlParameters();
        return isset($parameters[$name]) ? $parameters[$name] : $default;
    }

    /**
     * Set the value of the given url parameter
     *
     * @param   string  $name
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function setUrlParameter($name, $value)
    {
        $this->urlParameters[$name] = $value;
        return $this;
    }

    /**
     * Return all additional parameters for this item's url
     *
     * @return  array
     */
    public function getUrlParameters()
    {
        return $this->urlParameters ?: array();
    }

    /**
     * Set additional parameters for this item's url
     *
     * @param   array   $urlParameters
     *
     * @return  $this
     */
    public function setUrlParameters(array $urlParameters)
    {
        $this->urlParameters = $urlParameters;
        return $this;
    }

    /**
     * Set this item's properties
     *
     * Unknown properties (no matching setter) are considered as element attributes.
     *
     * @param   array   $properties
     *
     * @return  $this
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $name => $value) {
            $setter = 'set' . ucfirst($name);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } else {
                $this->setAttribute($name, $value);
            }
        }

        return $this;
    }

    /**
     * Merge this item with the given one
     *
     * @param   NavigationItem  $item
     *
     * @return  $this
     */
    public function merge(NavigationItem $item)
    {
        if ($this->conflictsWith($item)) {
            throw new ProgrammingError('Cannot merge, conflict detected.');
        }

        if ($this->priority === null) {
            $priority = $item->getPriority();
            if ($priority !== 100) {
                $this->setPriority($priority);
            }
        }

        if (! $this->getIcon()) {
            $this->setIcon($item->getIcon());
        }

        if ($this->getLabel() === $this->getName() && $item->getLabel() !== $item->getName()) {
            $this->setLabel($item->getLabel());
        }

        if ($this->target === null && ($target = $item->getTarget()) !== null) {
            $this->setTarget($target);
        }

        if ($this->renderer === null) {
            $renderer = $item->getRenderer();
            if (get_class($renderer) !== 'NavigationItemRenderer') {
                $this->setRenderer($renderer);
            }
        }

        foreach ($item->getAttributes() as $name => $value) {
            $this->setAttribute($name, $value);
        }

        foreach ($item->getUrlParameters() as $name => $value) {
            $this->setUrlParameter($name, $value);
        }

        if ($item->hasChildren()) {
            $this->getChildren()->merge($item->getChildren());
        }

        return $this;
    }

    /**
     * Return whether it's possible to merge this item with the given one
     *
     * @param   NavigationItem  $item
     *
     * @return  bool
     */
    public function conflictsWith(NavigationItem $item)
    {
        if (! $item instanceof $this) {
            return true;
        }

        if ($this->getUrl() === null || $item->getUrl() === null) {
            return false;
        }

        return !$this->getUrl()->matches($item->getUrl());
    }

    /**
     * Create and return the given renderer
     *
     * @param   string|array    $name
     *
     * @return  NavigationItemRenderer
     */
    protected function createRenderer($name)
    {
        if (is_array($name)) {
            $options = array_splice($name, 1);
            $name = $name[0];
        } else {
            $options = array();
        }

        $renderer = null;
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            $classPath = 'Icinga\\Module\\' . ucfirst($module->getName()) . '\\' . static::RENDERER_NS . '\\' . $name;
            if (class_exists($classPath)) {
                $renderer = new $classPath($options);
                break;
            }
        }

        if ($renderer === null) {
            $classPath = 'Icinga\\' . static::RENDERER_NS . '\\' . $name;
            if (class_exists($classPath)) {
                $renderer = new $classPath($options);
            }
        }

        if ($renderer === null) {
            throw new ProgrammingError(
                'Cannot find renderer "%s" for navigation item "%s"',
                $name,
                $this->getName()
            );
        } elseif (! $renderer instanceof NavigationItemRenderer) {
            throw new ProgrammingError('Class %s must inherit from NavigationItemRenderer', $classPath);
        }

        return $renderer;
    }

    /**
     * Set this item's renderer
     *
     * @param   string|array|NavigationItemRenderer     $renderer
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    If the $renderer argument is neither a string nor a NavigationItemRenderer
     */
    public function setRenderer($renderer)
    {
        if (is_string($renderer) || is_array($renderer)) {
            $renderer = $this->createRenderer($renderer);
        } elseif (! $renderer instanceof NavigationItemRenderer) {
            throw new InvalidArgumentException(
                'Argument $renderer must be of type string, array or NavigationItemRenderer'
            );
        }

        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Return this item's renderer
     *
     * @return  NavigationItemRenderer
     */
    public function getRenderer()
    {
        if ($this->renderer === null) {
            $this->setRenderer('NavigationItemRenderer');
        }

        return $this->renderer;
    }

    /**
     * Set whether this item should be rendered
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setRender($state = true)
    {
        $this->render = (bool) $state;
        return $this;
    }

    /**
     * Return whether this item should be rendered
     *
     * @return  bool
     */
    public function getRender()
    {
        if ($this->render === null) {
            return $this->getUrl() !== null;
        }

        return $this->render;
    }

    /**
     * Return whether this item should be rendered
     *
     * Alias for NavigationItem::getRender().
     *
     * @return  bool
     */
    public function shouldRender()
    {
        return $this->getRender();
    }

    /**
     * Return this item rendered to HTML
     *
     * @return  string
     */
    public function render()
    {
        try {
            return $this->getRenderer()->setItem($this)->render();
        } catch (Exception $e) {
            Logger::error(
                'Could not invoke custom navigation item renderer. %s in %s:%d with message: %s',
                get_class($e),
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            );

            $renderer = new NavigationItemRenderer();
            return $renderer->render($this);
        }
    }

    /**
     * Return this item rendered to HTML
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return IcingaException::describe($e);
        }
    }
}
