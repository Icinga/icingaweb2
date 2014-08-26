<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard\Component as DashboardComponent;
use Icinga\Web\Url;

/**
 * Dashboards display multiple views on a single page
 *
 * The terminology is as follows:
 * - Component:     A single view showing a specific url
 * - Pane:          Aggregates one or more components on one page, displays it's title as a tab
 * - Dashboard:     Shows all panes
 *
 */
class Dashboard extends AbstractWidget
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
     * Load Pane items provided by all enabled modules
     *
     * @return  self
     */
    public static function load()
    {
        /** @var $dashboard Dashboard */
        $dashboard = new static('dashboard');
        $manager = Icinga::app()->getModuleManager();
        foreach ($manager->getLoadedModules() as $module) {
            /** @var $module \Icinga\Application\Modules\Module */
            $dashboard->mergePanes($module->getPaneItems());

        }
        return $dashboard;
    }

    /**
     * Merge panes with existing panes
     *
     * @param array $panes
     * @return $this
     */
    public function mergePanes(array $panes)
    {
        /** @var $pane Pane  */
        foreach ($panes as $pane) {
            if (array_key_exists($pane->getName(), $this->panes)) {
                /** @var $current Pane */
                $current = $this->panes[$pane->getName()];
                $current->addComponents($pane->getComponents());
            } else {
                $this->panes = array_filter(array_merge($this->panes, $panes));
            }
        }

        return $this;
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
                $this->tabs->add(
                    $key,
                    array(
                        'title'     => $pane->getTitle(),
                        'url'       => clone($url),
                        'urlParams' => array($this->tabParam => $key)
                    )
                );
            }
        }
        return $this->tabs;
    }

    /**
     * Populate this dashboard via the given configuration file
     *
     * @param IcingaConfig $config      The configuration file to populate this dashboard with
     *
     * @return self
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
     * @param string $title
     *
     * @return self
     */
    public function createPane($title)
    {
        $pane = new Pane($title);
        $pane->setTitle($title);
        $this->addPane($pane);

        return $this;
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
     * @return self
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
     * Checks if the current dashboard has any panes
     *
     * @return bool
     */
    public function hasPanes()
    {
        return ! empty($this->panes);
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
     * @return self
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
     * @return self
     */
    public function addPane(Pane $pane)
    {
        $this->panes[$pane->getName()] = $pane;
        return $this;
    }

    /**
     * Return the pane with the provided name
     *
     * @param string $name      The name of the pane to return
     *
     * @return Pane        The pane or null if no pane with the given name exists
     * @throws ProgrammingError
     */
    public function getPane($name)
    {
        if (! array_key_exists($name, $this->panes)) {
            throw new ProgrammingError(
                sprintf('Trying to retrieve invalid dashboard pane "%s"', $name)
            );
        }
        return $this->panes[$name];
    }

    /**
     * @see Icinga\Web\Widget::render
     */
    public function render()
    {
        if (empty($this->panes)) {
            return '';
        }
        return $this->determineActivePane()->render();
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

    public function getActivePane()
    {
        if ($active = $this->getTabs()->getActiveName()) {
            return $this->getPane($active);
        }
        return $this->determineActivePane();
    }

    /**
     * Determine the active pane either by the selected tab or the current request
     *
     * @return Pane         The currently active pane
     */
    public function determineActivePane()
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

        if (isset($this->panes[$active])) {
            return $this->panes[$active];
        }

        throw new ConfigurationError('Could not determine active pane');
    }

    /**
     * Return this dashboard's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this->panes as $pane) {
            $array += $pane->toArray();
        }

        return $array;
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
