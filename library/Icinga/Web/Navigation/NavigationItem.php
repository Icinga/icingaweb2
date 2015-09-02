<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
use Icinga\Web\View;
use Icinga\Web\Url;

/**
 * A navigation item
 *
 * @see \Icinga\Web\Navigation\Navigation For a usage example.
 */
class NavigationItem implements Countable, IteratorAggregate
{
    /**
     * Alternative markup element if the navigation item has no URL
     *
     * @var string
     */
    const LINK_ALTERNATIVE = 'span';

    /**
     * Whether this item is active
     *
     * @var bool
     */
    protected $active;

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
     * Icon
     *
     * @var string|null
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
     * URL
     *
     * @var Url|null
     */
    protected $url;

    /**
     * URL parameters
     *
     * @var array
     */
    protected $urlParameters = array();

    /**
     * View
     *
     * @var View|null
     */
    protected $view;

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
    public function count()
    {
        return $this->getChildren()->count();
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
        return $this->active ?: false;
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
     * Get the icon
     *
     * @return string|null
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set the icon
     *
     * @param   string  $icon
     *
     * @return  $this
     */
    public function setIcon($icon)
    {
        $this->icon = (string) $icon;
        return $this;
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
        return $this->label ?: $this->getName();
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
     * Get the URL
     *
     * @return Url|null
     */
    public function getUrl()
    {
        if ($this->url !== null && ! $this->url instanceof Url) {
            $this->url = Url::fromPath((string) $this->url);
        }
        return $this->url;
    }

    /**
     * Set the URL
     *
     * @param   Url|string $url
     *
     * @return  $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get the URL parameters
     *
     * @return array
     */
    public function getUrlParameters()
    {
        return $this->urlParameters;
    }

    /**
     * Set the URL parameters
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
     * Get the view
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view === null) {
            $this->view = Icinga::app()->getViewRenderer()->view;
        }
        return $this->view;
    }

    /**
     * Set the view
     *
     * @param   View    $view
     *
     * @return  $this
     */
    public function setView(View $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Set this item's properties
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
            }
        }

        return $this;
    }

    /**
     * Return this item rendered to HTML
     *
     * @return  string
     */
    public function render()
    {
        $view = $this->getView();

        $label = $view->escape($this->getLabel());
        if (($icon = $this->getIcon()) !== null) {
            $label = $view->icon($icon) . $label;
        }

        if (($url = $this->getUrl()) !== null) {
            $content = sprintf(
                '<a%s href="%s">%s</a>',
                $view->propertiesToString($this->getAttributes()),
                $view->url($url, $this->getUrlParameters()),
                $label
            );
        } else {
            $content = sprintf(
                '<%1$s%2$s>%3$s</%1$s>',
                static::LINK_ALTERNATIVE,
                $view->propertiesToString($this->getAttributes()),
                $label
            );
        }

        return $content;
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
