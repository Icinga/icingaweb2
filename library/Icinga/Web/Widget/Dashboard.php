<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\User;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Menu;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\Tabs;

/**
 * Dashboards display multiple views on a single page
 *
 * The terminology is as follows:
 * - Dashlet:           A single view showing a specific url
 * - Pane:              Aggregates one or more dashlets on one page, displays its title as a tab
 * - Dashboard/Home:    Shows all panes belonging to this home
 *
 */
class Dashboard extends BaseHtmlElement
{
    /**
     * Upon mysql duplicate key error raised error code
     *
     * @var int
     */
    const PDO_DUPLICATE_KEY_ERR = 1062;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'dashboard content'];

    /**
     * The @see \ipl\Web\Widget\Tabs object for displaying displayable panes
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
     * Home items loaded from the „dashboard“ menu item
     *
     * @var DashboardHome[]
     */
    private $homes = [];

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
     * Set this dashboard's tabs.
     *
     * Since as of php5 objects are passed by reference by default, this gives
     *
     * us the ability to manipulate the original controller tabs as desired.
     *
     * @param Tabs $tabs
     *
     * @return $this
     */
    public function setTabs(Tabs $tabs)
    {
        $this->tabs = $tabs;

        return $this;
    }

    /**
     * Load Home items and their dashboard panes
     *
     * @return  $this
     */
    public function load()
    {
        $this->loadHomeItems();
        $this->loadDashboards();

        if (! $this->hasHome(DashboardHome::AVAILABLE_DASHLETS)) {
            DashboardHome::getConn()->insert(DashboardHome::TABLE, [
                'name'  => DashboardHome::AVAILABLE_DASHLETS,
                'label' => DashboardHome::AVAILABLE_DASHLETS,
                'owner' => DashboardHome::DEFAULT_IW2_USER
            ]);
        }

        return $this;
    }

    /**
     * Load dashboard panes belonging to a specific home
     *
     * @param string|null $home
     *
     * @throws ProgrammingError
     */
    public function loadDashboards($home = null)
    {
        if (! empty($home)) {
            $home = $this->getHome($home);
            $this->setActiveHome($home->getName());
            $home->setUser($this->user);

            $home
                ->loadSystemDashboards()
                ->loadOverridingPanes()
                ->loadUserDashboards();

            return;
        }

        if (Url::fromRequest()->getPath() === 'dashboard') {
            if (! $this->hasHome(DashboardHome::DEFAULT_HOME)) {
                $db = DashboardHome::getConn();

                $db->insert(DashboardHome::TABLE, [
                    'name'  => DashboardHome::DEFAULT_HOME,
                    'label' => DashboardHome::DEFAULT_HOME,
                    'owner' => DashboardHome::DEFAULT_IW2_USER
                ]);

                $home = new DashboardHome(DashboardHome::DEFAULT_HOME);
                $home->setIdentifier($db->lastInsertId());
            } else {
                $home = $this->getHome(DashboardHome::DEFAULT_HOME);
            }

            $this->setActiveHome($home->getName());
            $home->setUser($this->user);
        } else {
            $homeParam = Url::fromRequest()->getParam('home');

            if (empty($homeParam) || ! $this->hasHome($homeParam)) {
                // Was opened e.g from icingaweb2/search
                $home = $this->rewindHomes();

                if (empty($home)) {
                    return;
                }
            } else {
                $home = $this->getHome($homeParam);
            }

            $home->setUser($this->user);
            $this->setActiveHome($home->getName());
        }

        $home
            ->loadSystemDashboards()
            ->loadOverridingPanes()
            ->loadUserDashboards();
    }

    /**
     * Load home items from the navigation menu
     */
    public function loadHomeItems()
    {
        $menu = new Menu();
        /** @var DashboardHome $home */
        foreach ($menu->getItem('dashboard')->getChildren() as $home) {
            $this->homes[$home->getName()] = $home;
        }
    }

    /**
     * Return the tab object used to navigate through this dashboard
     *
     * @return Tabs
     */
    public function getTabs()
    {
        $activeHome = $this->getActiveHome();

        if ($activeHome && $activeHome->getName() !== DashboardHome::DEFAULT_HOME) {
            $url = Url::fromPath('dashboard/home')->getUrlWithout(['home', $this->tabParam]);
            $url->addParams(['home' => $activeHome->getName()]);
        } else {
            $url = Url::fromPath('dashboard')->getUrlWithout($this->tabParam);
        }

        $this->tabs->disableLegacyExtensions();

        // Only system home can be disabled
        if (! $activeHome || $activeHome->getDisabled()) {
            return  $this->tabs;
        }

        foreach ($activeHome->getPanes() as $key => $pane) {
            if ($pane->getDisabled()) {
                continue;
            }

            if (! $this->tabs->get($key)) {
                $this->tabs->add(
                    $key,
                    [
                        'title' => sprintf(
                            t('Show %s', 'dashboard.pane.tooltip'),
                            $pane->getTitle()
                        ),
                        'label'     => $pane->getTitle(),
                        'url'       => clone($url),
                        'urlParams' => [$this->tabParam => $key]
                    ]
                );
            }
        }

        return $this->tabs;
    }

    /**
     * Get the active home that is being loaded
     *
     * @return DashboardHome
     */
    public function getActiveHome()
    {
        $active = null;
        foreach ($this->getHomes() as $home) {
            if ($home->getActive()) {
                $active = $home;

                break;
            }
        }

        return $active;
    }

    /**
     * Activates the provided home name and sets the other homes to inactive
     *
     * @param  string $name
     *
     * @return $this
     *
     * @throws ProgrammingError
     */
    public function setActiveHome($name)
    {
        $activeHome = $this->getActiveHome();
        if ($activeHome && $activeHome->getName() !== $name) {
            $activeHome->setActive(false);
        }

        if ($this->hasHome($name)) {
            $this->getHome($name)->setActive();
        }

        return $this;
    }

    /**
     * Return dashboard home Navigation items
     *
     * @return DashboardHome[]
     */
    public function getHomes()
    {
        return $this->homes;
    }

    /**
     * Get home from the Navigation by the given name
     *
     * @param  string|int $nameOrId
     *
     * @return DashboardHome
     *
     * @throws ProgrammingError
     */
    public function getHome($nameOrId)
    {
        if (is_int($nameOrId)) {
            foreach ($this->homes as $home) {
                if ($home->getIdentifier() === (int) $nameOrId) {
                    return $home;
                }
            }
        }

        if ($this->hasHome($nameOrId)) {
            return $this->homes[$nameOrId];
        }

        throw new ProgrammingError('Trying to retrieve invalid dashboard home "%s"', $nameOrId);
    }

    /**
     * Remove a specific home from this dashboard
     *
     * @param string $home
     *
     * @return $this
     *
     * @throws ProgrammingError
     */
    public function removeHome($home)
    {
        if (! $this->hasHome($home)) {
            throw new ProgrammingError('Dashboard home not found: ' . $home);
        }

        $parent = $this->getHome($home);

        if ($parent->getOwner() === DashboardHome::DEFAULT_IW2_USER &&
            $parent->getName() !== DashboardHome::DEFAULT_HOME) {
            DashboardHome::getConn()->insert(DashboardHome::TABLE, [
                'name'      => $parent->getName(),
                'label'     => $parent->getLabel(),
                'owner'     => $this->user->getUsername(),
                'disabled'  => (int) true
            ]);
        } elseif (! $parent->getDisabled()) {
            if ($parent->getName() === DashboardHome::DEFAULT_HOME) {
                DashboardHome::getConn()->update(DashboardHome::TABLE, [
                    'owner'     => $this->user->getUsername(),
                    'disabled'  => (int) true,
                ], [
                    'id = ?' => $parent->getIdentifier()
                ]);
            } else {
                foreach ($this->getActiveHome()->getPanes() as $pane) {
                    $pane->removeDashlets();
                }

                $this->getActiveHome()->removePanes();

                DashboardHome::getConn()->delete(DashboardHome::TABLE, ['id = ?' => $parent->getIdentifier()]);
            }
        }

        return $this;
    }

    /**
     * Return an array with home name=>label format used for comboboxes
     *
     * @param bool $skipDisabled Whether to skip disabled homes
     *
     * @return array
     */
    public function getHomeKeyNameArray($skipDisabled = true)
    {
        $list = [];
        foreach ($this->getHomes() as $name => $home) {
            if ($home->getDisabled() && $skipDisabled) {
                continue;
            }

            // User is not allowed to add new content directly to this dashboard home
            if ($home->getName() === DashboardHome::AVAILABLE_DASHLETS
                || $home->getName() === DashboardHome::SHARED_DASHBOARDS) {
                continue;
            }

            $list[$name] = $home->getLabel();
        }

        return $list;
    }

    /**
     * Reset the current position of the internal home object
     *
     * @return null|DashboardHome
     */
    public function rewindHomes()
    {
        return reset($this->homes);
    }

    /**
     * Unset the provided home if exists from the list
     *
     * @param $home
     *
     * @return $this
     */
    public function unsetHome($home)
    {
        if ($this->hasHome($home)) {
            unset($this->homes[$home]);
        }

        return $this;
    }

    /**
     * Checks whether the given home exists
     *
     * @return bool
     */
    public function hasHome($home)
    {
        return $home && array_key_exists($home, $this->getHomes());
    }

    /**
     * Activates the default pane of this dashboard and returns its name
     *
     * @return mixed
     */
    private function setDefaultPane()
    {
        $activeHome = $this->getActiveHome();
        $active = null;

        foreach ($activeHome->getPanes() as $key => $pane) {
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
        $activeHome = $this->getActiveHome();
        $active = $this->getTabs()->getActiveTab();

        if (! $active) {
            if ($active = Url::fromRequest()->getParam($this->tabParam)) {
                if ($activeHome->hasPane($active)) {
                    $this->activate($active);
                } else {
                    throw new ProgrammingError(
                        'Try to get an inexistent pane.'
                    );
                }
            } else {
                $active = $this->setDefaultPane();
            }
        } else {
            $active = $active->getName();
        }

        $panes = $activeHome->getPanes();
        if (isset($panes[$active])) {
            return $panes[$active];
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
     * @inheritDoc
     */
    public function assemble()
    {
        $activeHome = $this->getActiveHome();

        if ($activeHome && ! empty($activeHome->getPanes(true))) {
            $dashlets = $this->getActivePane()->getDashlets(true);

            if (empty($dashlets)) {
                $this->setAttribute('class', 'content');
                $dashlets = new HtmlElement('h1', null, Text::create(t('No dashlet added to this pane.')));
            }
        } else {
            $this->setAttribute('class', 'content');
            $format = t(
                'Currently there is no pane available. This might change once you enabled some of the available %s.'
            );

            $dashlets = [
                new HtmlElement('h1', null, Text::create(t('Welcome to Icinga Web!'))),
                sprintf($format, new Link('modules', 'config/modules'))
            ];
        }

        $this->add($dashlets);
    }
}
