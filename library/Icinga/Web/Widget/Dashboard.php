<?php

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Config\Config as IcingaConfig;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Widget\Widget;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard\Component as DashboardComponent;

use Icinga\Web\Url;
use Zend_View_Abstract;

/**
 * Dashboards display multiple views on a single page
 *
 * The terminology is as follows:
 * - Component:     A single view showing a specific url
 * - Pane:          Aggregates one or more components on one page, displays it's title as a tab
 * - Dashboard:     Shows all panes
 *
 */
class Dashboard implements Widget
{
    /**
     * The configuration containing information about this dashboard
     *
     * @var IcingaConfig;
     */
    private $config;

    /**
     * An array containing all panes of this dashboard
     *
     * @var array
     */
    private $panes = array();

    /**
     * The @see Icinga\Web\Widget\Tabs object for displaying displayable panes
     *
     * @var Tabs
     */
    private $tabs;

    /**
     * The parameter that will be added to identify panes
     *
     * @var string
     */
    private $tabParam = 'pane';

    /**
     * Set the given tab name as active.
     *
     * @param string $name      The tab name to activate
     *
     */
    public function activate($name)
    {
        $this->getTabs()->activate($name);
    }

    /**
     * Return the tab object used to navigate through this dashboard
     *
     * @return Tabs
     */
    public function getTabs()
    {
        $url = Url::fromRequest()->getUrlWithout($this->tabParam);
        if ($this->tabs === null) {
            $this->tabs = new Tabs();

            foreach ($this->panes as $key => $pane) {
                $this->tabs->add($key, array(
                    'title'     => $pane->getTitle(),
                    'url'       => clone($url),
                    'urlParams' => array($this->tabParam => $key)
                ));
            }
        }
        return $this->tabs;
    }

    /**
     * Store the current dashboard with all it's panes and components to the given file (or the default one if none is
     * given)
     *
     *
     * @param string $file                              The filename to store this dashboard as an ini
     *
     * @return $this
     * @throws \Icinga\Exception\ConfigurationError     If persisting fails, details are written to the log
     *
     */
    public function store($file = null)
    {
        if ($file === null) {
            $file = IcingaConfig::app('dashboard/dashboard')->getConfigFile();
        }

        if (!is_writable($file)) {
            Logger::error('Tried to persist dashboard to %s, but path is not writeable', $file);
            throw new ConfigurationError('Can\'t persist dashboard');
        }
        // make sure empty dashboards don't cause errors
        $iniString = trim($this->toIni());
        if (!$iniString) {
            $iniString = " ";
        }
        if (!@file_put_contents($file, $iniString)) {
            $error = error_get_last();
            if ($error == NULL) {
                $error = 'Unknown error';
            } else {
                $error = $error['message'];
            }
            Logger::error('Tried to persist dashboard to %s, but got error: %s', $file, $error);
            throw new ConfigurationError('Can\'t persist dashboard');
        } else {
            return $this;
        }
    }

    /**
     * Populate this dashboard via the given configuration file
     *
     * @param IcingaConfig $config      The configuration file to populate this dashboard with
     *
     * @return $this
     */
    public function readConfig(IcingaConfig $config)
    {
        $this->config = $config;
        $this->panes = array();
        $this->loadConfigPanes();
        return $this;
    }

    /**
     * Creates a new empty pane with the given title
     *
     * @param $title
     */
    public function createPane($title)
    {
        $pane = new Pane($title);
        $pane->setTitle($title);
        $this->addPane($pane);
    }

    /**
     * Update or adds a new component with the given url to a pane
     *
     * @TODO:   Should only allow component objects to be added directly as soon as we store more information
     *
     * @param string $pane                  The pane to add the component to
     * @param Component|string $component   The component to add or the title of the newly created component
     * @param $url                          The url to use for the component
     *
     * @return $this
     */
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

    /**
     * Return true if a pane doesn't exist or doesn't have any components in it
     *
     * @param string $pane      The name of the pane to check for emptyness
     *
     * @return bool
     */
    public function isEmptyPane($pane)
    {
        $paneObj = $this->getPane($pane);
        if ($paneObj === null) {
            return true;
        }
        $cmps = $paneObj->getComponents();
        return !empty($cmps);
    }


    /**
     * Remove a component $component from the given pane
     *
     * @param string $pane                      The pane to remove the component from
     * @param Component|string $component       The component to remove or it's name
     *
     * @return $this
     */
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

    /**
     * Return an array with pane name=>title format used for comboboxes
     *
     * @return array
     */
    public function getPaneKeyTitleArray()
    {
        $list = array();
        foreach ($this->panes as $name => $pane) {
            $list[$name] = $pane->getTitle();
        }
        return $list;
    }

    /**
     * Add a pane object to this dashboard
     *
     * @param Pane $pane        The pane to add
     *
     * @return $this
     */
    public function addPane(Pane $pane)
    {
        $this->panes[$pane->getName()] = $pane;
        return $this;
    }

    /**
     * Return the pane with the provided name or null if it doesn't exit
     *
     * @param string $name      The name of the pane to return
     *
     * @return null|Pane        The pane or null if no pane with the given name exists
     */
    public function getPane($name)
    {
        if (!isset($this->panes[$name])) {
            return null;
        }
        return $this->panes[$name];
    }

    /**
     * @see Icinga\Web\Widget::render
     */
    public function render(Zend_View_Abstract $view)
    {
        if (empty($this->panes)) {
            return '';
        }
        return $this->determineActivePane()->render($view);
    }

    /**
     * Activates the default pane of this dashboard and returns it's name
     *
     * @return mixed
     */
    private function setDefaultPane()
    {
        reset($this->panes);
        $active = key($this->panes);
        $this->activate($active);
        return $active;
    }

    /**
     * Determine the active pane either by the selected tab or the current request
     *
     * @return Pane         The currently active pane
     */
    public function determineActivePane()
    {
        $active = $this->getTabs()->getActiveName();
        if (!$active) {
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

    /**
     * Return the ini string describing this dashboard
     *
     * @return string
     */
    public function toIni()
    {
        $ini = '';
        foreach ($this->panes as $pane) {
            $ini .= $pane->toIni();
        }
        return $ini;
    }

    /**
     * Load all config panes from @see Dashboard::$config
     *
     */
    private function loadConfigPanes()
    {
        $items = $this->config;
        foreach ($items->keys() as $key) {
            $item = $this->config->get($key, false);
            if (false === strstr($key, '.')) {
                $this->addPane(Pane::fromIni($key, $item));
            } else {
                list($paneName, $title) = explode('.', $key, 2);
                $pane = $this->getPane($paneName);
                $pane->addComponent(DashboardComponent::fromIni($title, $item, $pane));
            }
        }
    }
}

