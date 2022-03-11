<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Common\DataExtractor;
use Icinga\Web\Dashboard\Common\DisableWidget;
use Icinga\Web\Dashboard\Common\OrderWidget;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Model;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane implements OverridingWidget
{
    use DisableWidget;
    use OrderWidget;
    use DataExtractor;

    const TABLE = 'dashboard';

    /**
     * The name of this pane
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
     * @var array
     */
    private $dashlets = [];

    /**
     * Whether this widget overrides another widget
     *
     * @var bool
     */
    private $override;

    /**
     * Unique identifier of this pane
     *
     * @var string
     */
    private $uuid;

    /**
     * Name of the owner/creator of this pane
     *
     * @var string
     */
    private $owner;

    /**
     * Number of users who have subscribed to this pane if (public)
     *
     * @var int
     */
    private $acceptance;

    /**
     * A dashboard home this pane is a part of
     *
     * @var DashboardHome
     */
    private $home;

    /**
     * Create a new pane
     *
     * @param string $name The pane to create
     * @param array $properties
     */
    public function __construct($name, array $properties = [])
    {
        $this->name = $name;
        $this->title = $name;

        if (! empty($properties)) {
            $this->fromArray($properties);
        }
    }

    /**
     * Set the name of this pane
     *
     * @param string $name
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
     * @param string $title The new title to use for this pane
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function override(bool $override)
    {
        $this->override = $override;

        return $this;
    }

    public function isOverriding()
    {
        return $this->override;
    }

    /**
     * Set this pane's unique identifier
     *
     * @param string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get this pane's unique identifier
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the owner of this dashboard
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
     * Get owner of this dashboard
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the number of users who have subscribed to this pane if (public)
     *
     * @param int $acceptance
     */
    public function setAcceptance($acceptance)
    {
        $this->acceptance = $acceptance;

        return $this;
    }

    /**
     * Get the number of users who have subscribed to this pane if (public)
     *
     * @return int
     */
    public function getAcceptance()
    {
        return $this->acceptance;
    }

    /**
     * Get the dashboard home this pane is a part of
     *
     * This may return null if it wasn't set before (should never happen for DB dashboards)
     *
     * @return ?DashboardHome
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * Set the dashboard home this pane is a part of
     *
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function setHome(DashboardHome $home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Return true if a dashlet with the given name exists in this pane
     *
     * @param string $name The title of the dashlet to check for existence
     *
     * @return bool
     */
    public function hasDashlet($name)
    {
        return array_key_exists($name, $this->dashlets);
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
     * Get a dashlet with the given name if existing
     *
     * @param string $name
     *
     * @return Dashlet
     */
    public function getDashlet($name)
    {
        if ($this->hasDashlet($name)) {
            return $this->dashlets[$name];
        }

        throw new ProgrammingError('Trying to access invalid dashlet: %s', $name);
    }

    /**
     * Get all dashlets belongs to this pane
     *
     * @return Dashlet[]
     */
    public function getDashlets()
    {
        uasort($this->dashlets, function (Dashlet $x, Dashlet $y) {
            return $x->getPriority() - $y->getPriority();
        });

        return $this->dashlets;
    }

    /**
     * Set dashlets of this pane
     *
     * @param Dashlet[] $dashlets
     *
     * @return $this
     */
    public function setDashlets(array $dashlets)
    {
        $this->dashlets = $dashlets;

        return $this;
    }

    /**
     * Create, add and return a new dashlet
     *
     * @param string $name
     * @param string $url
     *
     * @return  Dashlet
     */
    public function createDashlet($name, $url = null)
    {
        $dashlet = new Dashlet($name, $url, $this);
        $this->addDashlet($dashlet);

        return $dashlet;
    }

    /**
     * Add a dashlet to this pane, optionally creating it if $dashlet is a string
     *
     * @param string|Dashlet $dashlet
     * @param ?string $url
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
     * Add a dashlet to the current pane
     *
     * @param string $name
     * @param Url|string $url
     *
     * @return $this
     * @see addDashlet()
     */
    public function add($name, $url, $priority = 0, $description = null)
    {
        $dashlet = $this->createDashlet($name, $url);
        $dashlet
            ->setDescription($description)
            ->setPriority($priority);
        $this->addDashlet($dashlet);

        return $this;
    }

    /**
     * Remove the given dashlet if it exists in this pane
     *
     * @param Dashlet|string $dashlet
     *
     * @return $this
     */
    public function removeDashlet($dashlet)
    {
        $name = $dashlet instanceof Dashlet ? $dashlet->getName() : $dashlet;
        if (! $this->hasDashlet($name)) {
            throw new ProgrammingError('Trying to remove invalid dashlet: %s', $name);
        }

        if (! $dashlet instanceof Dashlet) {
            $dashlet = $this->getDashlet($dashlet);
        }

        Dashboard::getConn()->delete(Dashlet::TABLE, [
            'id = ?'           => $dashlet->getUuid(),
            'dashboard_id = ?' => $this->getUuid()
        ]);

        return $this;
    }

    /**
     * Removes all or a given list of dashlets from this pane
     *
     * @param array $dashlets Optional list of dashlets
     *
     * @return $this
     */
    public function removeDashlets(array $dashlets = [])
    {
        $dashlets = ! empty($dashlets) ? $dashlets : $this->getDashlets();
        foreach ($dashlets as $dashlet) {
            $this->removeDashlet($dashlet);
        }

        return $this;
    }

    /**
     * Load all dashlets this dashboard is assigned to
     *
     * @return $this
     */
    public function loadDashletsFromDB()
    {
        if ($this->isDisabled()) {
            return $this;
        }

        $this->dashlets = [];
        $dashlets = Model\Dashlet::on(Dashboard::getConn())->with('module_dashlet');
        $dashlets->filter(Filter::equal('dashboard_id', $this->getUuid()));

        foreach ($dashlets as $dashlet) {
            $newDashlet = new Dashlet($dashlet->name, $dashlet->url, $this);
            $newDashlet->fromArray([
                'uuid'        => $dashlet->id,
                'title'       => t($dashlet->label),
                'priority'    => $dashlet->priority,
                'pane'        => $this,
                'description' => $dashlet->module_dashlet->description
            ]);

            $this->addDashlet($newDashlet);
        }

        return $this;
    }

    /**
     * Manage the given dashlet(s)
     *
     * If you want to move the dashlet(s) from another to this pane,
     * you have to also pass the origin pane
     *
     * @param Dashlet|Dashlet[] $dashlets
     * @param ?Pane $origin
     *
     * @return $this
     */
    public function manageDashlets($dashlets, Pane $origin = null)
    {
        if (! $this->getHome()) {
            throw new \LogicException(
                'Dashlets cannot be managed. Please make sure to set the current dashboard home beforehand.'
            );
        }

        $user = Dashboard::getUser();
        $conn = Dashboard::getConn();
        $dashlets = is_array($dashlets) ? $dashlets : [$dashlets];
        $order = count($this->getDashlets()) + 1;

        foreach ($dashlets as $dashlet) {
            if (is_array($dashlet)) {
                $this->manageDashlets($dashlet, $origin);
            }

            if (! $dashlet instanceof Dashlet) {
                break;
            }

            $uuid = Dashboard::getSHA1(
                $user->getUsername() . $this->getHome()->getName() . $this->getName() . $dashlet->getName()
            );

            if (! $this->hasDashlet($dashlet->getName()) && (! $origin || ! $origin->hasDashlet($dashlet->getName()))) {
                $conn->insert(Dashlet::TABLE, [
                    'id'           => $uuid,
                    'dashboard_id' => $this->getUuid(),
                    'name'         => $dashlet->getName(),
                    'label'        => $dashlet->getTitle(),
                    'url'          => $dashlet->getUrl()->getRelativeUrl(),
                    'priority'     => $order++
                ]);

                if ($dashlet->isModuleDashlet()) {
                    $systemUuid = Dashboard::getSHA1($dashlet->getModule() . $this->getName() . $dashlet->getName());
                    $conn->insert('dashlet_system', [
                        'dashlet_id'        => $uuid,
                        'module_dashlet_id' => $systemUuid
                    ]);
                }
            } elseif (! $this->hasDashlet($dashlet->getName())
                || ! $origin
                || ! $origin->hasDashlet($dashlet->getName())) {
                $conn->update(Dashlet::TABLE, [
                    'id'           => $uuid,
                    'dashboard_id' => $this->getUuid(),
                    'label'        => $dashlet->getTitle(),
                    'url'          => $dashlet->getUrl()->getRelativeUrl(),
                    'priority'     => $dashlet->getPriority()
                ], ['id = ?' => $dashlet->getUuid()]);
            } else {
                // This should have already been handled by the caller
                break;
            }

            $dashlet->setPane($this);
        }

        return $this;
    }

    public function toArray()
    {
        return [
            'id'       => $this->getUuid(),
            'name'     => $this->getName(),
            'label'    => $this->getTitle(),
            'home'     => $this->getHome() ? $this->getHome()->getName() : null,
            'priority' => $this->getPriority(),
            'disabled' => (int) $this->isDisabled()
        ];
    }
}
