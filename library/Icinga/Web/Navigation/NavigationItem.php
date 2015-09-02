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
     * Whether the item is active
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Attributes of the item's element
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Item's children
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
     * Label
     *
     * @var string|null
     */
    protected $label;

    /**
     * Parent
     *
     * @var NavigationItem|null
     */
    private $parent;

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
        if (! empty($properties)) {
            $this->setProperties($properties);
        }

        $this->children = new Navigation();
        $this->init();
    }

    /**
     * Initialize the navigation item
     */
    public function init()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->children->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->children;
    }

    /**
     * Get whether the item is active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set whether the item is active
     *
     * Bubbles active state.
     *
     * @param   bool    $active
     *
     * @return  $this
     */
    public function setActive($active = true)
    {
        $this->active = (bool) $active;
        $parent = $this;
        while (($parent = $parent->parent) !== null) {
            $parent->setActive($active);
        }
        return $this;
    }

    /**
     * Get an attribute's value of the item's element
     *
     * @param   string      $name       Name of the attribute
     * @param   mixed       $default    Default value
     *
     * @return  mixed
     */
    public function getAttribute($name, $default = null)
    {
        $name = (string) $name;
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    /**
     * Set an attribute of the item's element
     *
     * @param   string  $name   Name of the attribute
     * @param   mixed   $value  Value of the attribute
     *
     * @return  $this
     */
    public function setAttribute($name, $value)
    {
        $name = (string) $name;
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get the item's attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the item's attributes
     *
     * @param   array   $attributes
     *
     * @return  $this
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    /**
     * Add a child item to this item
     *
     * Bubbles active state.
     *
     * @param   NavigationItem|array    $child  The child to add
     *
     * @return  $this
     * @throws  InvalidArgumentException        If the child argument is invalid
     */
    public function addChild($child)
    {
        if (! $child instanceof NavigationItem) {
            if (! is_array($child)) {
                throw new InvalidArgumentException(
                    'Argument child must be either an array or an instance of NavigationItem'
                );
            }
            $child = new NavigationItem($child);
        }
        $child->parent = $this;
        $this->children->addItem($child);
        if ($child->getActive()) {
            $this->setActive();
        }
        return $this;
    }

    /**
     * Get the item's children
     *
     * @return Navigation
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get whether the item has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return ! $this->children->isEmpty();
    }

    /**
     * Set children
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
     * Get the label
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the label
     *
     * @param   string  $label
     *
     * @return  $this
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;
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
     * Set properties for the item
     *
     * @param   array   $properties
     *
     * @return  $this
     */
    public function setProperties(array $properties = array())
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
