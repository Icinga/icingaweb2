<?php

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Config\Config as IcingaConfig;;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Widget\Widget;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard\Component as DashboardComponent;

use Icinga\Web\Url;

class Dashboard implements Widget
{
    /**
     * @var IcingaConfig;
     */
    private $config;
    private $configfile;
    private $panes = array();
    private $tabs;

    private $url      = null;
    private $tabParam = 'pane';


    public function __construct()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest()->getUrlWithout($this->tabParam);
        }
    }

    public function activate($name)
    {
        $this->getTabs()->activate($name);
    }
    
    public function getTabs()
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

    public function store($file = null)
    {
        if ($file === null) {
            $file = IcingaConfig::app('dashboard/dashboard')->getConfigFile();
        }
        $this->configfile = $file;
        if (!$this->isWritable()) {
            Logger::error("Tried to persist dashboard to %s, but path is not writeable", $this->configfile);
            throw new ConfigurationError('Can\'t persist dashboard');
        }

        if (! @file_put_contents($this->configfile, $this->toIni())) {
            $error = error_get_last();
            if ($error == NULL) {
                $error = "Unknown error";
            } else {
                $error = $error["message"];
            }
            Logger::error("Tried to persist dashboard to %s, but got error: %s", $this->configfile, $error);
            throw new ConfigurationError('Can\'t persist dashboard');
        } else {
            return $this;
        }

    }

    public function readConfig(IcingaConfig $config)
    {
        $this->config = $config;
        $this->panes = array();
        $this->loadConfigPanes();
        return $this;
    }

    public function createPane($title)
    {
        $pane = new Pane($title);
        $pane->setTitle($title);
        $this->addPane($pane);

    }

    public function setComponentUrl($pane, $component, $url)
    {
        if ($component === null && strpos($pane, '.')) {
            list($pane, $component) = preg_split('~\.~', $pane, 2);
        }

        if (!isset($this->panes[$pane])) {
            $this->createPane($pane);
        }
        $pane = $this->getPane($pane);
        if ($pane->hasComponent($component)) {
            $pane->getComponent($component)->setUrl($url);
        } else {
            $pane->addComponent($component, $url);
        }
        return $this;
    }

    public function isEmptyPane($pane)
    {
        $paneObj = $this->getPane($pane);
        if ($paneObj === null) {
            return true;
        }
        $cmps = $paneObj->getComponents();
        return !empty($cmps);
    }

    public function removeComponent($pane, $component)
    {
        if ($component === null && strpos($pane, '.')) {
            list($pane, $component) = preg_split('~\.~', $pane, 2);
        }
        $pane = $this->getPane($pane);
        if ($pane !== null) {
            $pane->removeComponent($component);
        }

        return $this;
    }

    public function getPaneKeyTitleArray()
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
        if (!isset($this->panes[$name]))
            return null;
        return $this->panes[$name];
    }
    
    public function render(\Zend_View_Abstract $view)
    {
        if (empty($this->panes)) {
            return '';
        }

        return $this->getActivePane()->render($view);
    }

    private function setDefaultPane()
    {
        reset($this->panes);
        $active = key($this->panes);
        $this->activate($active);
        return $active;
    }

    public function getActivePane()
    {
        $active = $this->getTabs()->getActiveName();
        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                if ($this->isEmptyPane($active)) {
                    $active = $this->setDefaultPane();
                } else {
                    $this->activate($active);
                }
            } else {
                $active = $this->setDefaultPane();
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
        $items = $this->config;
        $app = Icinga::app();
        foreach ($items->keys() as $key) {
            $item = $this->config->get($key, false);
            if (false === strstr($key, '.')) {
                $this->addPane(Pane::fromIni($key, $item));

            } else {
                list($paneName, $title) = explode('.', $key , 2);
                $pane = $this->getPane($paneName);
                $pane->addComponent(DashboardComponent::fromIni($title, $item, $pane));
            }
        }


    }
}
