<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Url;
use Icinga\Exception\ConfigurationError;

class Pane
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
    
    public function addComponent($component, $url = null)
    {
        if ($component instanceof Component) {
            $this->components[$component->title] = $component;
        } elseif (is_string($component) && $url !== null) {
             $this->components[$component] = new Component($component, $url);
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
        $ini = sprintf(
            "[%s]\ntitle = %s\n",
            $this->getName(),
            $this->quoteIni($this->getTitle())
        ) . "\n";

        foreach ($this->components as $title => $component) {
            $ini .= sprintf(
                "[%s.%s]\n",
                $this->getName(),
                $title
            ) . $component->toIni() . "\n";
        }
        return $ini;
    }

    public function __toString()
    {
        return implode('', $this->components);
    }
}
