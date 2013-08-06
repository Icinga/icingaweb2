<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Url;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Widget\Widget;
use Zend_Config;

class Pane implements Widget
{
    protected $name;
    protected $title;
    protected $components = array();

    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function hasComponent($title)
    {
        return array_key_exists($title, $this->components);
    }

    public function getComponent($title)
    {
        if ($this->hasComponent($title)) {
            return $this->components[$title];
        }
        throw new ProgrammingError(sprintf(
            'Trying to access invalid component: %s',
            $title
        ));
    }

    public function removeComponent($title)
    {
        if ($this->hasComponent($title)) {
            unset($this->components[$title]);
        }
        return $this;
    }

    public function getComponents()
    {
        return $this->components;
    }

    public function render(\Zend_View_Abstract $view)
    {
        $html = PHP_EOL;
        foreach ($this->getComponents() as $component) {
            $html .= PHP_EOL.$component->render($view);
        }
        return $html;
    }

    public function addComponent($component, $url = null)
    {
        if ($component instanceof Component) {
            $this->components[$component->getTitle()] = $component;
        } elseif (is_string($component) && $url !== null) {
             $this->components[$component] = new Component($component, $url, $this);
        } else{
            throw new ConfigurationError('You messed up your dashboard');
        }
        return $this;
    }

    protected function quoteIni($str)
    {
        return '"' . $str . '"';
    }

    public function toIni()
    {
        if (empty($this->components))
        {
            return "";
        }
        $ini = '['.$this->getName().']'.PHP_EOL.
               'title = '.$this->quoteIni($this->getTitle()).PHP_EOL;

        foreach ($this->components as $title => $component) {
            // component header
            $ini .= '['.$this->getName().'.'.$title.']'.PHP_EOL;
            // component content
            $ini .= $component->toIni().PHP_EOL;
        }
        return $ini;
    }

    public static function fromIni($title, Zend_Config $config)
    {
        $pane = new Pane($title);
        if ($config->get('title', false)) {
            $pane->setTitle($config->get('title'));
        }
        return $pane;
    }
}
