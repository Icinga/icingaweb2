<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\File\Ini\IniWriter;
use Icinga\User;
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
     * @var User
     */
    private $user;

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
    public function load()
    {
        $manager = Icinga::app()->getModuleManager();
        foreach ($manager->getLoadedModules() as $module) {
            /** @var $module \Icinga\Application\Modules\Module */
            $this->mergePanes($module->getPaneItems());

        }
        if ($this->user !== null) {
            $this->loadUserDashboards();
        }
        return $this;
    }

    /**
     * Write user specific dashboards to disk
     */
    public function write()
    {
        $configFile = $this->getConfigFile();
        $output = array();
        foreach ($this->panes as $pane) {
            if ($pane->isUserWidget() === true) {
                $output[$pane->getName()] = $pane->toArray();
            }
            foreach ($pane->getComponents() as $component) {
                if ($component->isUserWidget() === true) {
                    $output[$pane->getName() . '.' . $component->getTitle()] = $component->toArray();
                }
            }
        }

        $config = new Config($output);
        $writer = new IniWriter(array('config' => $config, 'filename' => $configFile));
        $writer->write();
    }

    /**
     * @return bool
     */
    private function loadUserDashboards()
    {
        $config = Config::fromIni($this->getConfigFile());
        if (! count($config)) {
            return false;
        }
        $panes = array();
        $components = array();
        foreach ($config as $key => $part) {
            if (strpos($key, '.') === false) {
                $panes[$key] = new Pane($key);
                $panes[$key]->setTitle($part->title);
                $panes[$key]->setUserWidget();

            } else {
                list($paneName, $componentName) = explode('.', $key, 2);
                $part->pane = $paneName;
                $part->component = $componentName;
                $components[] = $part;
            }
        }
        foreach ($components as $componentData) {
            $pane = null;

            if (array_key_exists($componentData->pane, $panes) === true) {
                $pane = $panes[$componentData->pane];
            } elseif (array_key_exists($componentData->pane, $this->panes) === true) {
                $pane = $this->panes[$componentData->pane];
            } else {
                continue;
            }
            $component = new DashboardComponent(
                $componentData->title,
                $componentData->url,
                $pane
            );

            if ((bool) $componentData->get('disabled', false) === true) {
                $component->setDisabled(true);
            }

            $component->setUserWidget();
            $pane->addComponent($component);
        }

        $this->mergePanes($panes);

        return true;
    }

    /**
     * Merge panes with existing panes
     *
     * @param   array $panes
     *
     * @return  $this
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
                $this->panes[$pane->getName()] = $pane;
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
     * Return all panes of this dashboard
     *
     * @return array
     */
    public function getPanes()
    {
        return '';
        return $this->panes;
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
     * Checks if the current dashboard has any panes
     *
     * @return bool
     */
    public function hasPanes()
    {
        return ! empty($this->panes);
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
                'Trying to retrieve invalid dashboard pane "%s"',
                $name
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

    /**
     * @see determineActivePane()
     */
    public function getActivePane()
    {
        return $this->determineActivePane();
    }

    /**
     * Determine the active pane either by the selected tab or the current request
     *
     * @throws \Icinga\Exception\ConfigurationError
     * @throws \Icinga\Exception\ProgrammingError
     *
     * @return Pane The currently active pane
     */
    public function determineActivePane()
    {
        $active = $this->getTabs()->getActiveName();
        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                if ($this->hasPane($active)) {
                    $this->activate($active);
                } else {
                    throw new ProgrammingError(
                        'Try to get an inexistent pane.'
                    );
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
     * Setter for user object
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Getter for user object
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        if ($this->user === null) {
            return '';
        }
        return '/var/lib/icingaweb/' . $this->user->getUsername() . '/dashboard.ini';
    }
}
