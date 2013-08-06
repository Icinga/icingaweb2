<?php

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Config\Config;
use Icinga\Web\Widget\Widget;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Url;
use Zend_Config as ZfConfig;

class Dashboard implements Widget
{
    /**
     * @var Config
     */
    protected $config;
    protected $configfile;
    protected $panes = array();
    protected $tabs;

    protected $properties = array(
        'url'      => null,
        'tabParam' => 'pane'
    );

    protected function init()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest()->getUrlWithout($this->tabParam);
        }
    }

    public function activate($name)
    {
        $this->tabs()->activate($name);
    }
    
    public function tabs()
    {
        if ($this->tabs === null) {
            $this->tabs = new Tabs();
            foreach ($this->panes as $key => $pane) {
                $this->tabs->add($key, array(
                    'title'     => $pane->getTitle(),
                    'url'       => clone($this->url),
                    'urlParams' => array($this->tabParam => $key)
                ));
            }
        }

        return $this->tabs;
    }

    public function isWritable()
    {
        return is_writable($this->configfile);
    }

    public function store()
    {
        if (! @file_put_contents($this->configfile, $this->toIni())) {
            return false;
        } else {
            return $this;
        }
    }

    public function readConfig(ZfConfig $config)
    {
        $this->configfile = Icinga::app('dashboard')->getApplicationDir("dashboard");
        $this->config = $config;
        $this->panes = array();
        $this->loadConfigPanes();
        return $this;
    }

    public function setComponentUrl($pane, $component, $url)
    {
        if ($component === null && strpos($pane, '.')) {
            list($pane, $component) = preg_split('~\.~', $pane, 2);
        }
        $pane = $this->getPane($pane);
        if ($pane->hasComponent($component)) {
            $pane->getComponent($component)->setUrl($url);
        } else {
            $pane->addComponent($component, $url);
        }
        return $this;
    }

    public function removeComponent($pane, $component)
    {
        if ($component === null && strpos($pane, '.')) {
            list($pane, $component) = preg_split('~\.~', $pane, 2);
        }
        $this->getPane($pane)->removeComponent($component);
        return $this;
    }

    public function paneEnum()
    {
        $list = array();
        foreach ($this->panes as $name => $pane) {
            $list[$name] = $pane->getTitle();
        }
        return $list;
    }

    public function getComponentEnum()
    {
        $list = array();
        foreach ($this->panes as $name => $pane) {
            foreach ($pane->getComponents() as $component) {
                $list[$name . '.' . $component->getTitle()] =
                    $pane->getTitle() . ': ' . $component->getTitle();
            }
        }
        return $list;
    }

    public function addPane(Pane $pane)
    {
        $this->panes[$pane->getName()] = $pane;
        return $this;
    }

    public function getPane($name)
    {
        return $this->panes[$name];
    }
    
    public function render(\Zend_View_Abstract $view)
    {
        if (empty($this->panes)) {
            return '';
        }

        return $this->tabs() . $this->getActivePane();
    }

    public function getActivePane()
    {
        $active = $this->tabs()->getActiveName();
        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                $this->activate($active);
            } else {
                reset($this->panes);
                $active = key($this->panes);
                $this->activate($active);
            }
        }

        return $this->panes[$active];
    }

    public function toIni()
    {
        $ini = '';
        foreach ($this->panes as $pane) {
            $ini .= $pane->toIni();
        }
        return $ini;
    }
    
    protected function loadConfigPanes()
    {
        $items = $this->config->keys();
        $app = Icinga::app();
        foreach ($items as $key => $item) {
            if (false === strstr($key, '.')) {
                $pane = new Pane($key);
                if (isset($item['title'])) {
                    $pane->setTitle($item['title']);
                }
                $this->addPane($pane);
            } else {
                list($dashboard, $title) = preg_split('~\.~', $key, 2);
                $base_url = $item['base_url'];

                $module = substr($base_url, 0, strpos($base_url, '/'));
                $whitelist = array();
                if (! $app->hasModule($module)) {
                    continue;
                }

                unset($item['base_url']);
                $this->getPane($dashboard)->addComponent(
                    $title,
                    Url::fromPath($base_url, $item)
                );
            }
        }
    }
}
