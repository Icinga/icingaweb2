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

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Widget\Widget;
use Zend_Config;
use Zend_View_Abstract;

/**
 * A pane, displaying different Dashboard components
 *
 */
class Pane implements Widget
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
    public function render(Zend_View_Abstract $view)
    {
        $html = PHP_EOL;
        foreach ($this->components as $component) {
            $html .= PHP_EOL.$component->render($view);
        }
        return $html;
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
     * Return the ini representation of this pane as a string
     *
     * @return string
     */
    public function toIni()
    {
        if (empty($this->components)) {
            return '';
        }
        $ini = '[' . $this->getName() . ']' . PHP_EOL.
               'title = "' . $this->getTitle() . '"' . PHP_EOL;

        foreach ($this->components as $title => $component) {
            // component header
            $ini .= '[' . $this->getName() . '.' . $title . ']' . PHP_EOL;
            // component content
            $ini .= $component->toIni() . PHP_EOL;
        }
        return $ini;
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
