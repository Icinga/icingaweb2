<?php

namespace Icinga\Web\Navigation;

use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\User;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Sql\Select;
use ipl\Web\Url;

class DashboardHome extends NavigationItem
{
    use Database;

    /**
     * Name of the default home
     *
     * @var string
     */
    const DEFAULT_HOME = 'Default Home';

    /**
     * Default user of the "Default Home"
     *
     * @var string
     */
    const DEFAULT_IW2_USER = 'icingaweb2';

    /**
     * The parameter that will be added to identify homes
     *
     * @var string
     */
    const TAB_PARAM = 'home';

    const URL_PATH = 'dashboard/home';

    /**
     * This home's dashboard panes
     *
     * @var Pane[]
     */
    protected $panes = [];

    /**
     * A flag whether this home is disabled
     *
     * @var bool
     */
    protected $disabled;

    /**
     * This home's owner
     *
     * @var string
     */
    protected $owner = self::DEFAULT_IW2_USER;

    /**
     * This home's unique identifier
     *
     * @var integer
     */
    protected $identifier;

    /**
     * Whether this home is active
     *
     * @var bool
     */
    protected $active;

    /**
     * @var User
     */
    protected $user;

    /**
     * Get Database connection
     *
     * @return \ipl\Sql\Connection
     */
    public static function getConn()
    {
        return (new DashboardHome('Dummy'))->getDb();
    }

    /**
     * Generate the sha1 hash of the provided string
     *
     * @param string $name
     *
     * @return string
     */
    public static function getSHA1($name)
    {
        return sha1($name, true);
    }

    /**
     * Set whether this home is active
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active = true)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get whether this home is active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set this home's unique identifier
     *
     * @param  integer $id
     *
     * @return $this
     */
    public function setIdentifier($id)
    {
        $this->identifier = $id;

        return $this;
    }

    /**
     * Get this home's identifier
     *
     * @return int
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set this home's owner
     *
     * @param string $owner
     *
     * @return $this
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get this home's owner
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set disabled state for system homes
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled($disabled = true)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled state for this home
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        if ($this->getName() !== self::DEFAULT_HOME && ! $this->getDisabled()) {
            $this->setUrl(Url::fromPath(self::URL_PATH, [self::TAB_PARAM => $this->getName()]));
        }

        // Set default url to false when this home has been disabled so it
        // doesn't show up as a drop down menu under the navigation bar
        if ($this->getDisabled()) {
            $this->loadWithDefaultUrl(false);
        }
    }

    /**
     * Merge panes with existing panes
     *
     * @param Pane[] $panes
     *
     * @return $this
     */
    public function mergePanes(array $panes)
    {
        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->getDisabled()) {
            return $this;
        }

        foreach ($panes as $pane) {
            if ($pane->getOwner() !== self::DEFAULT_IW2_USER && empty($pane->getPaneId())) {
                throw new ProgrammingError('Pane "%s" does not have an identifier', $pane->getName());
            }

            $currentPane = null;
            if ($this->hasPane($pane->getName())) {
                $currentPane = $this->getPane($pane->getName());

                // Check whether the user has cloned system pane w/o modifying it
                if ($pane->getOwner() !== self::DEFAULT_IW2_USER &&  $pane->getType() === Pane::SYSTEM &&
                    $currentPane->getOwner() === self::DEFAULT_IW2_USER) {
                    if ($pane->getTitle() === $currentPane->getTitle() && ! $pane->hasDashlets()) {
                        // Cleaning up cloned system panes from the DB
                        $this->getDb()->delete('dashboard', ['id = ?' => $pane->getPaneId()]);

                        continue;
                    }
                }
            }

            $this->updatePaneData($pane);

            if ($currentPane) {
                $currentPane
                    ->setTitle($pane->getTitle())
                    ->setOwner($pane->getOwner())
                    ->setPaneId($pane->getPaneId())
                    ->setUserWidget($pane->isUserWidget())
                    ->setOverride($pane->isOverridingPane())
                    ->addDashlets($pane->getDashlets());

                continue;
            }

            $this->panes[$pane->getName()] = $pane;
        }

        return $this;
    }

    /**
     * Load system panes provided by all enabled modules which doesn't
     *
     * belong to any dashboard home
     *
     * @return $this
     */
    public function loadSystemDashboards()
    {
        // Standalone system panes are being loaded only in "Default Home" dashboard home
        if ($this->getName() !== self::DEFAULT_HOME) {
            return $this;
        }

        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->getDisabled()) {
            return $this;
        }

        $navigation = new Navigation();
        $navigation->load('dashboard-pane');

        $panes = [];
        /** @var DashboardPane $dashboardPane */
        foreach ($navigation as $dashboardPane) {
            $paneId = self::getSHA1(self::DEFAULT_IW2_USER . self::DEFAULT_HOME . $dashboardPane->getName());
            $pane = new Pane($dashboardPane->getName());
            $pane
                ->setPaneId($paneId)
                ->setTitle($dashboardPane->getLabel());

            $dashlets = [];
            /** @var NavigationItem $dashlet */
            foreach ($dashboardPane->getChildren() as $dashlet) {
                $dashletId = self::getSHA1(
                    self::DEFAULT_IW2_USER . self::DEFAULT_HOME . $pane->getName() . $dashlet->getName()
                );

                $newDashlet = new Dashlet($dashlet->getLabel(), $dashlet->getUrl()->getRelativeUrl(), $pane);
                $newDashlet
                    ->setName($dashlet->getName())
                    ->setDashletId($dashletId);

                $dashlets[$dashlet->getName()] = $newDashlet;
            }

            $pane->addDashlets($dashlets);
            $panes[$pane->getName()] = $pane;
        }

        $this->mergePanes($panes);

        return $this;
    }

    /**
     * Load user specific dashboards and dashlets from the DB and
     *
     * merge them to the system dashboards
     *
     * @return $this
     */
    public function loadUserDashboards()
    {
        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->getDisabled()) {
            return $this;
        }

        // Skip if this home is a system
        if ($this->getName() !== self::DEFAULT_HOME && $this->getOwner() === self::DEFAULT_IW2_USER) {
            return $this;
        }

        $dashboards = $this->getDb()->select((new Select())
            ->columns('d.*')
            ->from('dashboard d')
            ->joinLeft('dashboard_home dh', 'dh.id = d.home_id')
            ->where([
                'd.owner = ?'   => $this->user->getUsername(),
                'dh.owner = ?'  => $this->user->getUsername()
            ], 'OR'));

        $panes = [];
        foreach ($dashboards as $dashboard) {
            $pane = new Pane($dashboard->name);
            $pane
                ->setUserWidget()
                ->setPaneId($dashboard->id)
                ->setTitle($dashboard->label)
                ->setOwner($dashboard->owner)
                ->setType($dashboard->source);

            $dashlets = $this->getDb()->select((new Select())
                ->columns('ds.*')
                ->from('dashlet ds')
                ->joinLeft('dashboard d', 'd.id = ds.dashboard_id')
                ->where(['ds.dashboard_id = ?'  => $pane->getPaneId()])
                ->where([
                    'ds.owner = ?'  => $this->user->getUsername(),
                    'd.owner = ?'   => $this->user->getUsername()
                ], 'OR'));

            $paneDashlets = [];
            foreach ($dashlets as $dashletData) {
                $dashlet = new Dashlet($dashletData->label, $dashletData->url, $pane);
                $dashlet
                    ->setUserWidget()
                    ->setName($dashletData->name)
                    ->setDashletId($dashletData->id);

                $paneDashlets[$dashlet->getName()] = $dashlet;
            }

            $pane->addDashlets($paneDashlets);
            $panes[$pane->getName()] = $pane;
        }

        $this->mergePanes($panes);

        return $this;
    }

    /**
     * Set this home's dashboards
     *
     * @param Pane[]|Navigation $panes
     */
    public function setPanes($panes)
    {
        if ($panes instanceof Navigation) {
            $newPanes = [];

            /** @var DashboardPane $pane */
            foreach ($panes as $pane) {
                $newPane = new Pane($pane->getName());
                $newPane
                    ->setTitle($pane->getLabel())
                    ->setOwner(self::DEFAULT_IW2_USER)
                    ->setPaneId(self::getSHA1($this->getOwner() . $this->getName() . $pane->getName()));

                /** Cast array dashelts to NavigationItem */
                $pane->setChildren($pane->getAttribute('dashlets'));
                $pane->setAttribute('dashlets', null);

                $dashlets = [];
                /** @var NavigationItem $dashlet */
                foreach ($pane->getChildren() as $dashlet) {
                    $newDashlet = new Dashlet($dashlet->getLabel(), $dashlet->getUrl()->getRelativeUrl(), $newPane);
                    $newDashlet
                        ->setName($dashlet->getName())
                        ->setDashletId(self::getSHA1(
                            $this->getOwner() . $this->getName() . $pane->getName() . $dashlet->getName()
                        ));

                    $dashlets[$dashlet->getName()] = $newDashlet;
                }

                $newPane->addDashlets($dashlets);
                $newPanes[$pane->getName()] = $newPane;
            }

            $panes = $newPanes;
        }

        $this->panes = $panes;

        return $this;
    }

    /**
     * Load a pane from the DB, which overwrites a system panes, if any
     *
     * @return $this
     */
    public function loadOverridingPanes()
    {
        foreach ($this->getPanes() as $pane) {
            if ($pane->getOwner() !== self::DEFAULT_IW2_USER) {
                continue;
            }

            $this->updatePaneData($pane);
        }

        return $this;
    }

    /**
     * Get this home's dashboard panes
     *
     * @param bool $skipDisabled Whether to skip disabled panes
     *
     * @return Pane[]
     */
    public function getPanes($skipDisabled = false)
    {
        $panes = $this->panes;
        if ($skipDisabled) {
            $panes = array_filter($this->panes, function ($pane) {
                return ! $pane->getDisabled();
            });
        }

        return $panes;
    }

    /**
     * Return the pane with the provided name
     *
     * @param string $name The name of the pane to return
     *
     * @return Pane
     * @throws ProgrammingError
     */
    public function getPane($name)
    {
        if (! $this->hasPane($name)) {
            throw new ProgrammingError(
                'Trying to retrieve invalid dashboard pane "%s"',
                $name
            );
        }

        return $this->panes[$name];
    }

    /**
     * Update the given pane's properties
     *
     * @param Pane $pane
     */
    private function updatePaneData(Pane $pane)
    {
        // Check whether the pane is a system or cloned pane
        if ($pane->getOwner() === self::DEFAULT_IW2_USER || $pane->getType() === Pane::SYSTEM) {
            $paneId = self::getSHA1($this->user->getUsername() . $this->getName() . $pane->getName());
            $overridingPane = $this->getDb()->select((new Select())
                ->columns('*')
                ->from('dashboard_override')
                ->where([
                    'owner = ?'         => $this->user->getUsername(),
                    'dashboard_id = ?'  => $paneId
                ]))->fetch();

            if ($overridingPane) {
                // Remove the custom pane if label is null|rolled back to it's org value and is not disabled
                if ((! $overridingPane->label || $overridingPane->label === $pane->getTitle()) &&
                    ! (bool) $overridingPane->disabled) {
                    $this->getDb()->delete('dashboard_override', [
                        'owner = ?'         => $this->user->getUsername(),
                        'dashboard_id = ?'  => $paneId
                    ]);
                } else {
                    $pane
                        ->setUserWidget()
                        ->setOverride(true)
                        ->setOwner($overridingPane->owner)
                        ->setPaneId($paneId)
                        ->setDisabled($overridingPane->disabled);

                    if ($overridingPane->label) {
                        $pane->setTitle($overridingPane->label);
                    }
                }
            }
        }

        /** @var Dashlet $dashlet */
        foreach ($pane->getDashlets() as $dashlet) {
            if (! $dashlet->isUserWidget()) {
                // Since the system dashlet ids are being modified when writing them into the
                // DB, we have to regenerate them here as well.
                $dashletId = self::getSHA1(
                    $this->user->getUsername() . $this->getName() . $pane->getName() . $dashlet->getName()
                );

                $overridingDashlet = $this->getDb()->select((new Select())
                    ->columns('*')
                    ->from('dashlet_override')
                    ->where([
                        'dashlet_id = ?'    => $dashletId,
                        'dashboard_id = ?'  => $pane->getPaneId()
                    ]))->fetch();

                if ($overridingDashlet) {
                    // Remove the overriding dashlet if label & url are null|rolled back to their org value
                    // and is not disabled
                    if ((! $overridingDashlet->label || $overridingDashlet->label === $dashlet->getTitle()) &&
                        (! $overridingDashlet->url || $dashlet->getUrl()->matches($overridingDashlet->url)) &&
                        ! (bool) $overridingDashlet->disabled) {
                        $this->getDb()->delete('dashlet_override', [
                            'dashlet_id = ?'    => $dashletId,
                            'owner = ?'         => $this->user->getUsername()
                        ]);
                    } else {
                        $dashlet
                            ->setUserWidget()
                            ->setOverride()
                            ->setDashletId($dashletId)
                            ->setDisabled($overridingDashlet->disabled);

                        if ($overridingDashlet->url) {
                            $dashlet->setUrl($overridingDashlet->url);
                        }

                        if ($overridingDashlet->label) {
                            $dashlet->setTitle($overridingDashlet->label);
                        }
                    }
                }
            }
        }
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
     * Add a pane object to this home
     *
     * @param Pane $pane
     */
    public function addPane(Pane $pane)
    {
        $pane->setParentId($this->getIdentifier());
        $this->panes[$pane->getName()] = $pane;

        return $this;
    }

    /**
     * Creates a new empty pane with the given name
     *
     * @param $name
     *
     * @return $this
     */
    public function createPane($name)
    {
        $pane = new Pane($name);
        $pane->setTitle($name);

        $this->addPane($pane);

        return $this;
    }

    /**
     * Remove a specific pane form this home
     *
     * @param $pane
     *
     * @return $this
     *
     * @throws ProgrammingError
     */
    public function removePane($pane)
    {
        if (! $pane instanceof Pane) {
            if (! $this->hasPane($pane)) {
                throw new ProgrammingError(
                    'Trying to remove invalid dashboard pane "%s"',
                    $pane
                );
            }

            $pane = $this->getPane($pane);
        }

        if ($pane->getOwner() !== self::DEFAULT_IW2_USER && ! $pane->getDisabled()) {
            $table = 'dashboard';
            $condition = 'id = ?';

            if ($pane->isOverridingPane()) {
                $table = 'dashboard_override';
                $condition = 'dashboard_id = ?';
            }

            $this->getDb()->delete($table, [$condition => $pane->getPaneId(), 'owner = ?' => $pane->getOwner()]);
        } elseif (! $pane->getDisabled() && ! $this->getDisabled()) {
            $paneId = self::getSHA1($this->user->getUsername() . $this->getName() . $pane->getName());

            // User is going to disable a system pane
            $this->getDb()->insert('dashboard_override', [
                'dashboard_id'  => $paneId,
                'home_id'       => $this->getIdentifier(),
                'owner'         => $this->user->getUsername(),
                'disabled'      => true
            ]);
        }

        return $this;
    }

    /**
     * Remove all panes from this home, unless you specified the panes
     *
     * @param Pane[] $panes
     *
     * @return $this
     */
    public function removePanes(array $panes = [])
    {
        if (empty($panes)) {
            $panes = $this->getPanes();
        }

        foreach ($panes as $pane) {
            $this->removePane($pane);
        }

        return $this;
    }

    /**
     * Get an array with pane name=>title format used for combobox
     *
     * @return array
     */
    public function getPaneKeyTitleArray()
    {
        $panes = [];
        foreach ($this->getPanes() as $pane) {
            if ($pane->getDisabled()) {
                continue;
            }

            $panes[$pane->getName()] = $pane->getTitle();
        }

        return $panes;
    }

    /**
     * Setter for user object
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
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
}
