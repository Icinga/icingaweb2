<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Authentication\Auth;
use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Dashboard\Dashlet;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane implements UserWidget
{
    use Database;

    /** @var string A type for all panes provided by modules */
    const SYSTEM = 'system';

    /** @var string A type for user created panes */
    const PRIVATE = 'private';

    /**
     * Flag if widget is created by an user
     *
     * @var bool
     */
    protected $userWidget = false;

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
     * A user this panel belongs to
     *
     * @var string
     */
    private $owner = 'icingaweb2';

    /**
     * An array of @see Dashlets that are displayed in this pane
     *
     * @var array
     */
    private $dashlets = array();

    /**
     * Disabled flag of a pane
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * Whether this pane overrides a system pane
     *
     * @var bool
     */
    private $override = false;

    /**
     * Dashboard home id if the current pane
     *
     * @var integer
     */
    private $parentId;

    /**
     * Unique identifier of the this pane
     *
     * @var integer
     */
    private $paneId;

    /**
     * A flag whether the user has create a dashlet in a system pane
     *
     * @var string
     */
    private $type = 'system';

    /**
     * Create a new pane
     *
     * @param string $name         The pane to create
     */
    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    /**
     * Set the dashboard home id for this pane
     *
     * @param  integer  $homeId
     */
    public function setParentId($homeId)
    {
        $this->parentId = $homeId;

        return $this;
    }

    /**
     * Returns the dashboard home id of this pane
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set unique identifier of this pane
     *
     * @param  integer  $id
     */
    public function setPaneId($id)
    {
        $this->paneId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this pane
     *
     * @return integer
     */
    public function getPaneId()
    {
        return $this->paneId;
    }

    /**
     * Set type of this pane (system | private)
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
     * Set the owner of this panel
     *
     * @param $owner
     *
     * @return $this
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get the owner of this panel
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Return true if a dashlet with the given title exists in this pane
     *
     * @param string $title     The title of the dashlet to check for existence
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
     * @param string $title         The title of the dashlet to return
     *
     * @return Dashlet            The dashlet with the given title
     * @throws ProgrammingError     If the dashlet doesn't exist
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
        if ($owner === \Icinga\Web\Navigation\DashboardHome::DEFAULT_IW2_USER) {
            $owner = Auth::getInstance()->getUser()->getUsername();
        }

        if ($dashlet->isUserWidget() === true && ! $dashlet->getDisabled()) {
            if ($dashlet->isOverriding()) {
                $this->getDb()->delete('dashlet_override', [
                    'dashlet_id = ?'    => $dashlet->getDashletId(),
                    'owner = ?'         => $owner
                ]);
            } else {
                $this->getDb()->delete('dashlet', [
                    'id = ?'            => $dashlet->getDashletId(),
                    'dashboard_id = ?'  => $dashlet->getPane()->getPaneId()
                ]);
            }
        } elseif (! $dashlet->getDisabled() && ! $this->isUserWidget()) {
            $this->getDb()->insert('dashlet_override', [
                'dashlet_id'    => $dashlet->getDashletId(),
                'dashboard_id'  => $dashlet->getPane()->getPaneId(),
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
        if ($this->getOwner() !== \Icinga\Web\Navigation\DashboardHome::DEFAULT_IW2_USER) {
            foreach ($dashlets as $dashlet) {
                $this->removeDashlet($dashlet);
            }
        }

        return $this;
    }

    /**
     * Return all dashlets added at this pane
     *
     * @return array
     */
    public function getDashlets($skipDisabled = false, $ordered = true)
    {
        $dashlets = $this->dashlets;

        if ($skipDisabled) {
            $dashlets = array_filter($this->dashlets, function ($dashlet) {
                return ! $dashlet->getDisabled();
            });
        }

        if ($ordered) {
            ksort($dashlets);
        }

        return $dashlets;
    }

    /**
     * Create, add and return a new dashlet
     *
     * @param   string  $title
     * @param   string  $url
     *
     * @return  Dashlet
     */
    public function createDashlet($title, $url = null)
    {
        $dashlet = new Dashlet($title, $url, $this);
        $this->addDashlet($dashlet);
        return $dashlet;
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
             $this->createDashlet($dashlet, $url);
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

    /**
     * Set whether this pane overrides a system pane
     *
     * @param  boolean $value
     *
     * @return $this
     */
    public function setOverride($value)
    {
        $this->override = (bool)$value;

        return $this;
    }

    /**
     * Get whether this pane overrides a system pane with the same name
     *
     * @return bool
     */
    public function isOverridingPane()
    {
        return $this->override;
    }

    /**
     * @inheritDoc
     */
    public function setUserWidget($userWidget = true)
    {
        $this->userWidget = (bool) $userWidget;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isUserWidget()
    {
        return $this->userWidget;
    }
}
