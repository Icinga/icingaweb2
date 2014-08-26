<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Dashboard;

use Zend_Config;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;

/**
 * A pane, displaying different Dashboard components
 */
class Pane extends AbstractWidget
{
    /**
     * The name of this pane, as defined in the ini file
     *
     * @var string
     */
    private $name;

    /**
     * The title of this pane, as displayed in the dashboard tabs
     * @TODO: Currently the same as $name, evaluate if distinguishing is needed
     *
     * @var string
     */
    private $title;

    /**
     * An array of @see Components that are displayed in this pane
     *
     * @var array
     */
    private $components = array();

    /**
     * Create a new pane
     *
     * @param $name         The pane to create
     */
    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    /**
     * Returns the name of this pane
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the title of this pane
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Overwrite the title of this pane
     *
     * @param string $title     The new title to use for this pane
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Return true if a component with the given title exists in this pane
     *
     * @param string $title     The title of the component to check for existence
     *
     * @return bool
     */
    public function hasComponent($title)
    {
        return array_key_exists($title, $this->components);
    }

    /**
     * Return a component with the given name if existing
     *
     * @param string $title         The title of the component to return
     *
     * @return Component            The component with the given title
     * @throws ProgrammingError     If the component doesn't exist
     */
    public function getComponent($title)
    {
        if ($this->hasComponent($title)) {
            return $this->components[$title];
        }
        throw new ProgrammingError(sprintf('Trying to access invalid component: %s', $title));
    }

    /**
     * Removes the component with the given title if it exists in this pane
     *
     * @param string $title         The pane
     * @return Pane $this
     */
    public function removeComponent($title)
    {
        if ($this->hasComponent($title)) {
            unset($this->components[$title]);
        }
        return $this;
    }

    /**
     * Return all components added at this pane
     *
     * @return array
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @see Widget::render
     */
    public function render()
    {
        return implode("\n", $this->components) . "\n";
    }

    /**
     * Add a component to this pane, optionally creating it if $component is a string
     *
     * @param string|Component $component               The component object or title
     *                                                  (if a new component will be created)
     * @param string|null $url                          An Url to be used when component is a string
     *
     * @return self
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function addComponent($component, $url = null)
    {
        if ($component instanceof Component) {
            $this->components[$component->getTitle()] = $component;
        } elseif (is_string($component) && $url !== null) {
             $this->components[$component] = new Component($component, $url, $this);
        } else {
            throw new ConfigurationError('Invalid component added: ' . $component);
        }
        return $this;
    }

    /**
     * Add new components to existing components
     *
     * @param array $components
     * @return $this
     */
    public function addComponents(array $components)
    {
        /* @var $component Component */
        foreach ($components as $component) {
            if (array_key_exists($component->getTitle(), $this->components)) {
                if (preg_match('/_(\d+)$/', $component->getTitle(), $m)) {
                    $name = preg_replace('/_\d+$/', $m[1]++, $component->getTitle());
                } else {
                    $name = $component->getTitle() . '_2';
                }
                $this->components[$name] = $component;
            } else {
                $this->components[$component->getTitle()] = $component;
            }
        }

        return $this;
    }

    /**
     * Add a component to the current pane
     *
     * @param $title
     * @param $url
     * @return Component
     *
     * @see addComponent()
     */
    public function add($title, $url = null)
    {
        $this->addComponent($title, $url);

        return $this->components[$title];
    }

    /**
     * Return the this pane's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array($this->getName() => array('title' => $this->getTitle()));
        foreach ($this->components as $title => $component) {
            $array[$this->getName() . ".$title"] = $component->toArray();
        }

        return $array;
    }

    /**
     * Create a new pane with the title $title from the given configuration
     *
     * @param $title                The title for this pane
     * @param Zend_Config $config   The configuration to use for setup
     *
     * @return Pane
     */
    public static function fromIni($title, Zend_Config $config)
    {
        $pane = new Pane($title);
        if ($config->get('title', false)) {
            $pane->setTitle($config->get('title'));
        }
        return $pane;
    }
}
