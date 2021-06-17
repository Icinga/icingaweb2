<?php

namespace Icinga\Web\Navigation;

use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\User;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use ipl\Sql\Select;
use ipl\Web\Url;

/**
 * DashboardHome loads all the panes belonging to the actually selected Home,
 *
 * along with their dashlets.
 */
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
     * A Home where all collected dashlets provided by modules are
     *
     * being presented in a special view
     *
     * @var string
     */
    const AVAILABLE_DASHLETS = 'Available Dashlets';

    /**
     * A Home where all dashboards shared with this user are
     *
     * displayed in a dedicated view
     *
     * @var string
     */
    const SHARED_DASHBOARDS = 'Shared Dashboards';

    /**
     * A default user of some system homes
     *
     * @var string
     */
    const DEFAULT_IW2_USER = 'icingaweb2';

    /**
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard_home';

    /**
     * An array of @see Pane belongs to this home
     *
     * @var Pane[]
     */
    private $panes = [];

    /**
     * A flag whether this home is disabled
     *
     * @var bool
     */
    private $disabled;

    /**
     * A user this home belongs to
     *
     * @var string
     */
    private $owner = self::DEFAULT_IW2_USER;

    /**
     * This home's unique identifier
     *
     * @var integer
     */
    private $identifier;

    /**
     * @var User
     */
    private $user;

    /**
     * Whether this home is active
     *
     * @var bool
     */
    protected $active;

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
            $this->setUrl(Url::fromPath('dashboard/home', ['home' => $this->getName()]));
        }

        // Set default url to false when this home has been disabled, so it
        // doesn't show up as a drop-down menu under the navigation bar
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
                        $this->getDb()->delete(Pane::TABLE, ['id = ?' => $pane->getPaneId()]);

                        continue;
                    }
                }
            }

            $this->modifyPaneProperties($pane);

            if ($currentPane) {
                $currentPane->setOwner($pane->getOwner());
                $currentPane->setUserWidget($pane->isUserWidget());
                $currentPane->setOverride($pane->isOverridingWidget());

                $currentPane
                    ->setTitle($pane->getTitle())
                    ->setPaneId($pane->getPaneId())
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
                ->setHome($this)
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
            ->columns('d.*, do.priority')
            ->from(Pane::TABLE . ' d')
            ->join(self::TABLE . ' dh', 'dh.id = d.home_id')
            ->joinLeft('dashboard_order do', 'd.id = do.dashboard_id')
            ->where(['dh.id = ?' => $this->getIdentifier()])
            ->where([
                'd.owner = ?'   => $this->getUser()->getUsername(),
                'dh.owner = ?'  => $this->getUser()->getUsername()
            ], 'OR')
            ->orderBy('do.priority', 'DESC'));

        $panes = [];
        foreach ($dashboards as $dashboard) {
            $pane = new Pane($dashboard->name);
            $pane->setUserWidget();
            $pane->setOwner($dashboard->owner);
            $pane
                ->setHome($this)
                ->setPaneId($dashboard->id)
                ->setTitle($dashboard->label)
                ->setType($dashboard->source)
                ->setOrder($dashboard->priority);

            $dashlets = $this->getDb()->select((new Select())
                ->columns('ds.*, do.priority')
                ->from(Dashlet::TABLE . ' ds')
                ->join(Pane::TABLE . ' d', 'd.id = ds.dashboard_id')
                ->joinLeft('dashlet_order do', 'ds.id = do.dashlet_id')
                ->where(['d.id = ?' => $pane->getPaneId()])
                ->where([
                    'ds.owner = ?'  => $this->getUser()->getUsername(),
                    'd.owner = ?'   => $this->getUser()->getUsername()
                ], 'OR')
                ->orderBy('do.priority', 'DESC'));

            $paneDashlets = [];
            foreach ($dashlets as $dashletData) {
                $dashlet = new Dashlet($dashletData->label, $dashletData->url, $pane);
                $dashlet->setUserWidget();
                $dashlet
                    ->setOrder($dashletData->priority)
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
                $newPane->setOwner(self::DEFAULT_IW2_USER);
                $newPane
                    ->setHome($this)
                    ->setTitle($pane->getLabel())
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

            $this->modifyPaneProperties($pane);
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

        uasort($panes, function (Pane $x, Pane $y) {
            return $y->getOrder() - $x->getOrder();
        });

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
     * Modify the given pane's properties if it is a cloned or system pane
     *
     * @param Pane $pane
     */
    private function modifyPaneProperties(Pane $pane)
    {
        // Check whether the pane is a system or cloned pane
        if ($pane->getOwner() === self::DEFAULT_IW2_USER || $pane->getType() === Pane::SYSTEM) {
            $paneId = self::getSHA1($this->getUser()->getUsername() . $this->getName() . $pane->getName());
            $overridingPane = $this->getDb()->select((new Select())
                ->columns('*')
                ->from(Pane::OVERRIDING_TABLE)
                ->where([
                    'owner = ?'         => $this->getUser()->getUsername(),
                    'dashboard_id = ?'  => $paneId
                ]))->fetch();

            if ($overridingPane) {
                // Remove the custom pane if label is null|rolled back to it's org value and is not disabled
                if ((! $overridingPane->label || $overridingPane->label === $pane->getTitle()) &&
                    ! (bool) $overridingPane->disabled) {
                    $this->getDb()->delete(Pane::OVERRIDING_TABLE, [
                        'owner = ?'         => $this->getUser()->getUsername(),
                        'dashboard_id = ?'  => $paneId
                    ]);
                } else {
                    $pane->setUserWidget();
                    $pane->setOverride(true);
                    $pane->setOwner($overridingPane->owner);

                    $pane
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
                    $this->getUser()->getUsername() . $this->getName() . $pane->getName() . $dashlet->getName()
                );

                $paneId = $pane->getPaneId();
                if ($pane->getOwner() === self::DEFAULT_IW2_USER) {
                    $paneId = self::getSHA1(
                        $this->getUser()->getUsername() . $this->getName() . $pane->getName()
                    );
                }

                $overridingDashlet = self::getConn()->select((new Select())
                    ->columns('*')
                    ->from(Dashlet::OVERRIDING_TABLE)
                    ->where([
                        'dashlet_id = ?'    => $dashletId,
                        'dashboard_id = ?'  => $paneId
                    ]))->fetch();

                if ($overridingDashlet) {
                    // Remove the overriding dashlet if label & url are null|rolled back to their org value
                    // and is not disabled
                    if ((! $overridingDashlet->label || $overridingDashlet->label === $dashlet->getTitle()) &&
                        (! $overridingDashlet->url || $dashlet->getUrl()->matches($overridingDashlet->url)) &&
                        ! (bool) $overridingDashlet->disabled) {
                        $this->getDb()->delete(Dashlet::OVERRIDING_TABLE, [
                            'dashlet_id = ?'    => $dashletId,
                            'owner = ?'         => $this->getUser()->getUsername()
                        ]);
                    } else {
                        $pane->setPaneId($paneId);

                        $dashlet->setUserWidget();
                        $dashlet->setOverride(true);
                        $dashlet
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
     * Add a new pane to this home
     *
     * @param Pane|string $pane
     */
    public function addPane($pane)
    {
        if (! $pane instanceof Pane) {
            $pane = new Pane($pane);
            $pane->setTitle($pane->getName());
        }

        $pane->setHome($this);
        $this->panes[$pane->getName()] = $pane;

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
            $table = Pane::TABLE;
            $condition = 'id = ?';

            if ($pane->isOverridingWidget()) {
                $table = Pane::OVERRIDING_TABLE;
                $condition = 'dashboard_id = ?';
            }

            $this->getDb()->delete($table, [$condition => $pane->getPaneId(), 'owner = ?' => $pane->getOwner()]);
        } elseif (! $pane->getDisabled() && ! $this->getDisabled()) {
            $paneId = self::getSHA1($this->getUser()->getUsername() . $this->getName() . $pane->getName());

            // User is going to disable a system pane
            $this->getDb()->insert(Pane::OVERRIDING_TABLE, [
                'dashboard_id'  => $paneId,
                'home_id'       => $this->getIdentifier(),
                'owner'         => $this->getUser()->getUsername(),
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
