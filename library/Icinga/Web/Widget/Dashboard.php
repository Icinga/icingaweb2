<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Config;
use Icinga\Common\Database;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\ProgrammingError;
use Icinga\Legacy\DashboardConfig;
use Icinga\User;
use Icinga\Web\Navigation\DashboardPane;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard\Dashlet as DashboardDashlet;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Sql\Select;

/**
 * Dashboards display multiple views on a single page
 *
 * The terminology is as follows:
 * - Dashlet:     A single view showing a specific url
 * - Pane:          Aggregates one or more dashlets on one page, displays its title as a tab
 * - Dashboard:     Shows all panes
 *
 */
class Dashboard extends AbstractWidget
{
    use Database;

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
    protected $tabs;

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
     * @return  $this
     */
    public function load()
    {
        $navigation = new Navigation();
        $navigation->load('dashboard-pane');

        $panes = array();
        foreach ($navigation as $dashboardPane) {
            /** @var DashboardPane $dashboardPane */
            $pane = new Pane($dashboardPane->getLabel());
            foreach ($dashboardPane->getChildren() as $dashlet) {
                $pane->addDashlet($dashlet->getLabel(), $dashlet->getUrl());
            }

            $panes[] = $pane;
        }

        $this->mergePanes($panes);
        $this->loadUserDashboards($navigation);
        return $this;
    }

    /**
     * Create and return a Config object for this dashboard
     *
     * @return  Config
     */
    public function getConfig()
    {
        $output = array();
        foreach ($this->panes as $pane) {
            if ($pane->isUserWidget()) {
                $output[$pane->getName()] = $pane->toArray();
            }
            foreach ($pane->getDashlets() as $dashlet) {
                if ($dashlet->isUserWidget()) {
                    $output[$pane->getName() . '.' . $dashlet->getName()] = $dashlet->toArray();
                }
            }
        }

        return DashboardConfig::fromArray($output)->setConfigFile($this->getConfigFile())->setUser($this->user);
    }

    /**
     * Load user dashboards from all config files that match the username
     */
    protected function loadUserDashboards(Navigation $navigation)
    {
        foreach (DashboardConfig::listConfigFilesForUser($this->user) as $file) {
            $this->loadUserDashboardsFromFile($file, $navigation);
        }
    }

    /**
     * Load user specific dashboards and dashlets from the database
     * and merges them to the dashboards loaded from an ini file
     *
     * @param User $user
     *
     * @return bool
     */
    public function loadUserDashboardsFromDatabase(User $user)
    {
        $dashboards = array();

        $id = 0;
        if (Url::fromRequest()->hasParam('home')) {
            $id = Url::fromRequest()->getParam('id');
        }
        $results = $this->getDb()->select((new Select())
            ->columns('*')
            ->from('dashboard')
            ->where(['home_id = ?' => $id]));

        foreach ($results as $dashboard) {
            $dashboards[$dashboard->name] = new Pane($dashboard->name);
            $dashboards[$dashboard->name]->setTitle($dashboard->name);
            $dashboards[$dashboard->name]->setUserWidget();

            $newResults = $this->getDb()->select((new Select())
                ->columns('*')
                ->from('dashlet')
                ->where(['dashboard_id = ?' => $dashboard->id, 'dashlet.owner = ?' => $user->getUsername()]));

            foreach ($newResults as $dashletData) {
                $dashlet = new DashboardDashlet(
                    $dashletData->name,
                    $dashletData->url,
                    $dashboards[$dashboard->name]
                );

                $dashlet->setName($dashletData->name);
                $dashlet->setUserWidget();
                $dashboards[$dashboard->name]->addDashlet($dashlet);
            }
        }

        $this->mergePanes($dashboards);

        return true;
    }

    /**
     * Load user dashboards from the given config file
     *
     * @param   string  $file
     *
     * @return  bool
     */
    protected function loadUserDashboardsFromFile($file, Navigation $dashboardNavigation)
    {
        try {
            $config = Config::fromIni($file);
        } catch (NotReadableError $e) {
            return false;
        }

        if (! count($config)) {
            return false;
        }
        $panes = array();
        $dashlets = array();
        foreach ($config as $key => $part) {
            if (strpos($key, '.') === false) {
                $dashboardPane = $dashboardNavigation->getItem($key);
                if ($dashboardPane !== null) {
                    $key = $dashboardPane->getLabel();
                }
                if ($this->hasPane($key)) {
                    $panes[$key] = $this->getPane($key);
                } else {
                    $panes[$key] = new Pane($key);
                    $panes[$key]->setTitle($part->title);
                }
                $panes[$key]->setUserWidget();
                if ((bool) $part->get('disabled', false) === true) {
                    $panes[$key]->setDisabled();
                }
            } else {
                list($paneName, $dashletName) = explode('.', $key, 2);
                $dashboardPane = $dashboardNavigation->getItem($paneName);
                if ($dashboardPane !== null) {
                    $paneName = $dashboardPane->getLabel();
                    $dashletItem = $dashboardPane->getChildren()->getItem($dashletName);
                    if ($dashletItem !== null) {
                        $dashletName = $dashletItem->getLabel();
                    }
                }
                $part->pane = $paneName;
                $part->dashlet = $dashletName;
                $dashlets[] = $part;
            }
        }
        foreach ($dashlets as $dashletData) {
            $pane = null;

            if (array_key_exists($dashletData->pane, $panes) === true) {
                $pane = $panes[$dashletData->pane];
            } elseif (array_key_exists($dashletData->pane, $this->panes) === true) {
                $pane = $this->panes[$dashletData->pane];
            } else {
                continue;
            }
            $dashlet = new DashboardDashlet(
                $dashletData->title,
                $dashletData->url,
                $pane
            );
            $dashlet->setName($dashletData->dashlet);

            if ((bool) $dashletData->get('disabled', false) === true) {
                $dashlet->setDisabled(true);
            }

            $dashlet->setUserWidget();
            $pane->addDashlet($dashlet);
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
            if ($this->hasPane($pane->getName()) === true) {
                /** @var $current Pane */
                $current = $this->panes[$pane->getName()];

                /** @var $dashlet DashboardDashlet */
                foreach ($pane->getDashlets() as $dashlet) {
                    if ($current->hasDashlet($dashlet->getName()) === true) {
                        $currentUrl = $current->getDashlet($dashlet->getName())->getUrl();
                        if ($currentUrl->getAbsoluteUrl() === $dashlet->getUrl()->getAbsoluteUrl()) {
                            $current->removeDashlet($dashlet->getName());
                        }
                    }
                }

                $current->addDashlets($pane->getDashlets());
            } else {
                $this->panes[$pane->getName()] = $pane;
            }
        }

        return $this;
    }

    /**
     * Return the tab object used to navigate through this dashboard
     *
     * @param bool $dashboardHomes
     *
     * @return Tabs
     */
    public function getTabs($dashboardHomes = false)
    {
        $url = Url::fromPath('dashboard')->getUrlWithout($this->tabParam);

        if ($dashboardHomes) {
            // We don't want to discard the existing url params at dashboard homes
            $url = Url::fromRequest();
        }

        if ($this->tabs === null) {
            $this->tabs = new Tabs();

            foreach ($this->panes as $key => $pane) {
                if ($pane->getDisabled()) {
                    continue;
                }
                $this->tabs->add(
                    $key,
                    array(
                        'title'       => sprintf(
                            t('Show %s', 'dashboard.pane.tooltip'),
                            $pane->getTitle()
                        ),
                        'label'     => $pane->getTitle(),
                        'url'       => clone($url),
                        'urlParams' => $dashboardHomes ? ['name' => $key] : [ $this->tabParam => $key ]
                    )
                );
            }
        }

        // This is only required for dashboard homes
        if ($dashboardHomes) {
            $this->setDefaultPane();
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
        return $this->panes;
    }


    /**
     * Creates a new empty pane with the given title
     *
     * @param string $title
     *
     * @return $this
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
     * Check if a panel exist
     *
     * @param   string  $pane
     * @return  bool
     */
    public function hasPane($pane)
    {
        return $pane && array_key_exists($pane, $this->panes);
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

    public function removePane($title)
    {
        if ($this->hasPane($title) === true) {
            $pane = $this->getPane($title);
            if ($pane->isUserWidget() === true) {
                unset($this->panes[$pane->getName()]);
            } else {
                $pane->setDisabled();
                $pane->setUserWidget();
            }
        } else {
            throw new ProgrammingError('Pane not found: ' . $title);
        }
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
     * Activates the default pane of this dashboard and returns its name
     *
     * @return mixed
     */
    private function setDefaultPane()
    {
        $active = null;

        foreach ($this->panes as $key => $pane) {
            if ($pane->getDisabled() === false) {
                $active = $key;
                break;
            }
        }

        if ($active !== null) {
            $this->activate($active);
        }
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
            throw new ProgrammingError('Can\'t load dashboards. User is not set');
        }
        return Config::resolvePath('dashboards/' . strtolower($this->user->getUsername()) . '/dashboard.ini');
    }
}
