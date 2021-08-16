<?php

namespace Icinga\Web\Navigation;

use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\User;
use Icinga\Web\Widget\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;
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
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard_home';

    /**
     * Base path of this dashboards controller
     */
    const BASE_PATH = 'dashboards';

    /**
     * An array of @see Pane belongs to this home
     *
     * @var Pane[]
     */
    private $panes = [];

    /**
     * A user this home belongs to
     *
     * @var string
     */
    private $owner;

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
    public function setId($id)
    {
        $this->identifier = $id;

        return $this;
    }

    /**
     * Get this home's identifier
     *
     * @return int
     */
    public function getId()
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
     * {@inheritDoc}
     */
    public function init()
    {
        if ($this->getName() !== self::DEFAULT_HOME) {
            $this->setUrl(Url::fromPath(self::BASE_PATH . '/home', ['home' => $this->getName()]));
        }

        if ($this->getName() === self::DEFAULT_HOME) {
            $this->shouldHaveDefaultUrl(false);
        }
    }

    /**
     * Load user specific dashboards and dashlets from the DB
     *
     * @return $this
     */
    public function loadUserDashboards()
    {
        // Skip if this home is inactive
        if (! $this->getActive()) {
            return $this;
        }

        $dashboards = self::getConn()->select((new Select())
            ->columns('d.*, do.priority')
            ->from(Pane::TABLE . ' d')
            ->join(self::TABLE . ' dh', 'dh.id = d.home_id')
            ->joinLeft('dashboard_order do', 'd.id = do.dashboard_id')
            ->where(['dh.id = ?' => $this->getId()])
            ->where([
                'd.owner = ?'   => $this->getUser()->getUsername(),
                'dh.owner = ?'  => $this->getUser()->getUsername()
            ], 'OR')
            ->orderBy('do.priority', 'DESC'));

        $panes = [];
        foreach ($dashboards as $dashboard) {
            $pane = new Pane($dashboard->name);
            $pane
                ->setHome($this)
                ->setId($dashboard->id)
                ->setTitle($dashboard->label)
                ->setType($dashboard->source)
                ->setOrder($dashboard->priority);

            $dashlets = self::getConn()->select((new Select())
                ->columns('ds.*, do.priority')
                ->from(Dashlet::TABLE . ' ds')
                ->join(Pane::TABLE . ' d', 'd.id = ds.dashboard_id')
                ->joinLeft('dashlet_order do', 'ds.id = do.dashlet_id')
                ->where(['d.id = ?' => $pane->getId()])
                ->where([
                    'ds.owner = ?'  => $this->getUser()->getUsername(),
                    'd.owner = ?'   => $this->getUser()->getUsername()
                ], 'OR')
                ->orderBy('do.priority', 'DESC'));

            $paneDashlets = [];
            foreach ($dashlets as $dashletData) {
                $dashlet = new Dashlet($dashletData->label, $dashletData->url, $pane);
                $dashlet
                    ->setOrder($dashletData->priority)
                    ->setName($dashletData->name)
                    ->setId($dashletData->id);

                $paneDashlets[$dashlet->getName()] = $dashlet;
            }

            $pane->addDashlets($paneDashlets);
            $panes[$pane->getName()] = $pane;
        }

        $this->mergePanes($panes);

        return $this;
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
        // Skip if this home is inactive
        if (! $this->getActive()) {
            return $this;
        }

        foreach ($panes as $pane) {
            if (empty($pane->getId())) {
                throw new ProgrammingError('Pane "%s" does not have an identifier', $pane->getName());
            }

            if ($this->hasPane($pane->getName())) {
                $currentPane = $this->getPane($pane->getName());

                $currentPane->addDashlets($pane->getDashlets());
                $currentPane->setTitle($pane->getTitle());

                continue;
            }

            $this->panes[$pane->getName()] = $pane;
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
     * Check whether the given pane exist
     *
     * @param   string  $pane
     * @return  bool
     */
    public function hasPane($pane)
    {
        return $pane && array_key_exists($pane, $this->panes);
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
     * Get this home's dashboard panes
     *
     * @return Pane[]
     */
    public function getPanes()
    {
        $panes = $this->panes;

        uasort($panes, function (Pane $x, Pane $y) {
            return $y->getOrder() - $x->getOrder();
        });

        return $panes;
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

        self::getConn()->delete(Pane::TABLE, [
            'id = ?'    => $pane->getId(),
            'owner = ?' => $this->getUser()->getUsername()
        ]);

        return $this;
    }

    /**
     * Remove all or a given list of panes from this home
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
