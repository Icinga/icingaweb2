<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Navigation\DashboardHome;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane
{
    use UserWidget;

    use Database;

    /**
     * System panes are provided by the modules in PHP code
     *
     * and are available to all users
     *
     * @var string
     */
    const SYSTEM = 'system';

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
     * Database pane overriding table name
     *
     * @var string
     */
    const OVERRIDING_TABLE = 'dashboard_override';

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
    private $dashlets = array();

    /**
     * Disabled flag of a pane
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * Dashboard home id if the current pane
     *
     * @var integer
     */
    private $parentId;

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
    private $type = 'system';

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
     * Set unique identifier of this pane
     *
     * @param  string  $id
     */
    public function setPaneId($id)
    {
        $this->paneId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this pane
     *
     * @return string
     */
    public function getPaneId()
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
     * Return true if a dashlet with the given title exists in this pane
     *
     * @param string $title The title of the dashlet to check for existence
     *
     * @return bool
     */
    public function hasDashlet($title)
    {
        return $title && array_key_exists($title, $this->dashlets);
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
     * @param string $title       The title of the dashlet to return
     *
     * @return Dashlet            The dashlet with the given title
     * @throws ProgrammingError   If the dashlet doesn't exist
     */
    public function getDashlet($title)
    {
        if ($this->hasDashlet($title)) {
            return $this->dashlets[$title];
        }

        throw new ProgrammingError(
            'Trying to access invalid dashlet: %s',
            $title
        );
    }

    /**
     * Removes the dashlet with the given title if it exists in this pane
     *
     * @param   Dashlet|string $dashlet
     *
     * @return  $this
     */
    public function removeDashlet($dashlet)
    {
        if (! $dashlet instanceof Dashlet) {
            if (! $this->hasDashlet($dashlet)) {
                throw new ProgrammingError('Trying to remove invalid dashlet: %s', $dashlet);
            }

            $dashlet = $this->getDashlet($dashlet);
        }

        $owner = $dashlet->getPane()->getOwner();
        if ($owner === DashboardHome::DEFAULT_IW2_USER) {
            $owner = $this->getHome()->getUser()->getUsername();
        }

        if ($dashlet->isUserWidget() === true && ! $dashlet->getDisabled()) {
            if ($dashlet->isOverridingWidget()) {
                $this->getDb()->delete('dashlet_override', [
                    'dashlet_id = ?'    => $dashlet->getDashletId(),
                    'owner = ?'         => $owner
                ]);
            } else {
                $this->getDb()->delete('dashlet', [
                    'id = ?'            => $dashlet->getDashletId(),
                    'dashboard_id = ?'  => $this->getPaneId()
                ]);
            }
        } elseif (! $dashlet->getDisabled() && ! $this->getDisabled()) {
            // When modifying system dashlets, we need also to change the pane id accordingly,
            // so that we won't have id mismatch in DashboardHome class when it is loading.
            $paneId = DashboardHome::getSHA1(
                $owner . $this->getHome()->getName() . $this->getName()
            );

            $this->getDb()->insert('dashlet_override', [
                'dashlet_id'    => $dashlet->getDashletId(),
                'dashboard_id'  => $paneId,
                'owner'         => $owner,
                'disabled'      => true
            ]);
        }

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

        // Remove dashlets only if this is a custom pane
        if ($this->getOwner() !== DashboardHome::DEFAULT_IW2_USER) {
            foreach ($dashlets as $dashlet) {
                $this->removeDashlet($dashlet);
            }
        }

        return $this;
    }

    /**
     * Get all dashlets belongs to this pane
     *
     * @param bool $skipDisabled Whether to skip disabled dashlets
     *
     * @return array
     */
    public function getDashlets($skipDisabled = false)
    {
        $dashlets = $this->dashlets;

        if ($skipDisabled) {
            $dashlets = array_filter($this->dashlets, function ($dashlet) {
                return ! $dashlet->getDisabled();
            });
        }

        uasort($dashlets, function (Dashlet $x, Dashlet $y) {
            return $y->getOrder() - $x->getOrder();
        });

        return $dashlets;
    }

    /**
     * Add a dashlet to this pane, optionally creating it if $dashlet is a string
     *
     * @param string|Dashlet $dashlet The dashlet object or title (if a new dashlet will be created)
     * @param string|null $url        An Url to be used when dashlet is a string
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
                // Custom dashlets always take precedence over system dashlets
                if (! $dashlet->isUserWidget() && $this->dashlets[$dashlet->getName()]->isUserWidget()) {
                    continue;
                }
            }

            $this->dashlets[$dashlet->getName()] = $dashlet;
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
        $pane = ['title' => $this->getTitle()];
        if ($this->getDisabled() === true) {
            $pane['disabled'] = 1;
        }

        return $pane;
    }

    /**
     * Setter for disabled
     *
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = (bool) $disabled;

        return $this;
    }

    /**
     * Getter for disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
}
