<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Navigation\DashboardHome;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane
{
    /**
     * Public panes are created by authorized users and
     *
     * are available to all users
     *
     * @var string
     */
    const PUBLIC_DS = 'public';

    /**
     * Private panes are created by any user and are only
     *
     * available to this user
     *
     * @var string
     */
    const PRIVATE_DS = 'private';

    /**
     * Are available to users who have accepted a share or who
     *
     * have been assigned the dashboard by their Admin
     *
     * @var string
     */
    const SHARED = 'shared';

    /**
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard';

    /**
     * The not translatable name of this pane
     *
     * @var string
     */
    private $name;

    /**
     * The title of this pane, as displayed in the dashboard tabs
     *
     * @var string
     */
    private $title;

    /**
     * An array of @see Dashlet that are displayed in this pane
     *
     * @var Dashlet[]
     */
    private $dashlets = [];

    /**
     * A user this pane belongs to
     *
     * @var string
     */
    private $owner;

    /**
     * Dashboard home of this pane
     *
     * @var DashboardHome
     */
    private $home;

    /**
     * Unique identifier of this pane
     *
     * @var string
     */
    private $paneId;

    /**
     * A type of this pane
     *
     * @var string
     */
    private $type = 'private';

    /**
     * The priority order of this pane
     *
     * @var int
     */
    private $order;

    /**
     * Create a new pane
     *
     * @param string $name The pane to create
     */
    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    /**
     * Set the name of this pane
     *
     * @param   string  $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this pane
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the title of this pane
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Overwrite the title of this pane
     *
     * @param string $title     The new title to use for this pane
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the dashboard home of this pane
     *
     * @param  DashboardHome $home
     *
     * @return $this
     */
    public function setHome(DashboardHome $home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Get the dashboard home of this pane
     *
     * @return DashboardHome
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * Set the owner of this pane
     *
     * @param string $user
     *
     * @return $this
     */
    public function setOwner($user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get the owner of this pane
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set unique identifier of this pane
     *
     * @param  string  $id
     */
    public function setId($id)
    {
        $this->paneId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this pane
     *
     * @return string
     */
    public function getId()
    {
        return $this->paneId;
    }

    /**
     * Get the priority order of this pane
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the priority order of this pane
     *
     * @param $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Set type of this pane
     *
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type of this pane
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return true if a dashlet with the given title exists in this pane
     *
     * @param string $dashlet   The title of the dashlet to check for existence
     *
     * @return bool
     */
    public function hasDashlet($dashlet)
    {
        return $dashlet && array_key_exists($dashlet, $this->dashlets);
    }

    /**
     * Checks if the current pane has any dashlets
     *
     * @return bool
     */
    public function hasDashlets()
    {
        return ! empty($this->dashlets);
    }

    /**
     * Return a dashlet with the given name if existing
     *
     * @param string $dashlet     The title of the dashlet to return
     *
     * @return Dashlet            The dashlet with the given title
     * @throws ProgrammingError   If the dashlet doesn't exist
     */
    public function getDashlet($dashlet)
    {
        if ($this->hasDashlet($dashlet)) {
            return $this->dashlets[$dashlet];
        }

        throw new ProgrammingError(
            'Trying to access invalid dashlet: %s',
            $dashlet
        );
    }

    /**
     * Removes the dashlet with the given title if it exists in this pane
     *
     * @param string $dashlet
     * @return Pane $this
     */
    public function removeDashlet(string $dashlet)
    {
        if (! $dashlet instanceof Dashlet) {
            if (! $this->hasDashlet($dashlet)) {
                throw new ProgrammingError('Trying to remove invalid dashlet: %s', $dashlet);
            }

            $dashlet = $this->getDashlet($dashlet);
        }

        DashboardHome::getConn()->delete('dashlet', [
            'id = ?'            => $dashlet->getId(),
            'dashboard_id = ?'  => $this->getId()
        ]);

        return $this;
    }

    /**
     * Removes all or a given list of dashlets from this pane
     *
     * @param array|null $dashlets Optional list of dashlet titles
     *
     * @return Pane $this
     */
    public function removeDashlets(array $dashlets = null)
    {
        if ($dashlets === null) {
            $dashlets = $this->getDashlets();
        }

        foreach ($dashlets as $dashlet) {
            $this->removeDashlet($dashlet);
        }

        return $this;
    }

    /**
     * Return all dashlets added at this pane
     *
     * @return array
     */
    public function getDashlets()
    {
        $dashlets = $this->dashlets;

        uasort($dashlets, function (Dashlet $x, Dashlet $y) {
            return $y->getOrder() - $x->getOrder();
        });

        return $dashlets;
    }

    /**
     * Add a dashlet to this pane, optionally creating it if $dashlet is a string
     *
     * @param string|Dashlet $dashlet     The dashlet object or title (if a new dashlet will be created)
     * @param string|null $url            An Url to be used when dashlet is a string
     *
     * @return $this
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function addDashlet($dashlet, $url = null)
    {
        if ($dashlet instanceof Dashlet) {
            $this->dashlets[$dashlet->getName()] = $dashlet;
        } elseif (is_string($dashlet) && $url !== null) {
            $this->dashlets[$dashlet] = new Dashlet($dashlet, $url, $this);
        } else {
            throw new ConfigurationError('Invalid dashlet added: %s', $dashlet);
        }

        return $this;
    }

    /**
     * Add new dashlets to existing dashlets
     *
     * @param array $dashlets
     * @return $this
     */
    public function addDashlets(array $dashlets)
    {
        /* @var $dashlet Dashlet */
        foreach ($dashlets as $dashlet) {
            if (array_key_exists($dashlet->getName(), $this->dashlets)) {
                if (preg_match('/_(\d+)$/', $dashlet->getName(), $m)) {
                    $name = preg_replace('/_\d+$/', $m[1]++, $dashlet->getName());
                } else {
                    $name = $dashlet->getName() . '_2';
                }
                $this->dashlets[$name] = $dashlet;
            } else {
                $this->dashlets[$dashlet->getName()] = $dashlet;
            }
        }

        return $this;
    }

    /**
     * Add a dashlet to the current pane
     *
     * @param $title
     * @param $url
     * @return Dashlet
     *
     * @see addDashlet()
     */
    public function add($title, $url = null)
    {
        $this->addDashlet($title, $url);

        return $this->dashlets[$title];
    }

    /**
     * Return the this pane's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        return [
            'title'     => $this->getTitle(),
            'name'      => $this->getName(),
            'dashlets'  => $this->getDashlets()
        ];
    }
}
