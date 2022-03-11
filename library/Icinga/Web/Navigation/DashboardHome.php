<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Common\DisableWidget;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use Icinga\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

class DashboardHome extends NavigationItem
{
    use DisableWidget;

    /**
     * Name of the default home
     *
     * @var string
     */
    const DEFAULT_HOME = 'Default Home';

    /**
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard_home';

    /**
     * A list of @see Pane assigned to this dashboard home
     *
     * @var Pane[]
     */
    private $panes = [];

    /**
     * This home's unique identifier
     *
     * @var int
     */
    private $uuid;

    /**
     * A type of this dashboard home
     *
     * @var string
     */
    private $type = Dashboard::SYSTEM;

    /**
     * Init this dashboard home
     *
     * Doesn't set the url of this dashboard home if it's the default one or is disabled
     * to prevent from being rendered as dropdown in the navigation bar
     *
     * @return void
     */
    public function init()
    {
        if ($this->getName() !== self::DEFAULT_HOME && ! $this->isDisabled()) {
            $this->setUrl(Url::fromPath(Dashboard::BASE_ROUTE . '/home', [
                'home' => $this->getName()
            ]));
        }
    }

    /**
     * Get this dashboard home's url
     *
     * Parent class would always report a default url if $this->url isn't
     * set, which we do it on purpose.
     *
     * @return \Icinga\Web\Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get whether this home has been activated
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set whether this home is active
     *
     * DB dashboard will load only when this home has been activated
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
     * Get the type of this dashboard home
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type of this dashboard home
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the uuid of this dashboard home
     *
     * @return int
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Get the uuid of this dashboard home
     *
     * @param int $uuid
     *
     * @return $this
     */
    public function setUuid(int $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get a pane with the given name if exists
     *
     * @param string $name
     *
     * @return Pane
     */
    public function getPane($name)
    {
        if (! $this->hasPane($name)) {
            throw new ProgrammingError('Trying to retrieve invalid dashboard pane "%s"', $name);
        }

        return $this->panes[$name];
    }

    /**
     * Get whether this home has any dashboard panes
     *
     * @return bool
     */
    public function hasPanes()
    {
        return ! empty($this->panes);
    }

    /**
     * Get whether the given pane exist
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasPane($name)
    {
        return array_key_exists($name, $this->panes);
    }

    /**
     * Get all dashboards of this home
     *
     * @param bool $skipDisabled Whether to skip disabled dashboards
     *
     * @return Pane[]
     */
    public function getPanes($skipDisabled = false)
    {
        // As the panes can also be added individually afterwards, it might be the case that the priority
        // order gets mixed up, so we have to sort things here before being able to render them
        uasort($this->panes, function (Pane $x, Pane $y) {
            return $x->getPriority() - $y->getPriority();
        });

        return ! $skipDisabled ? $this->panes : array_filter($this->panes, function ($pane) {
            return ! $pane->isDisabled();
        });
    }

    /**
     * Set dashboards of this home
     *
     * @param Pane|Pane[] $panes
     *
     * @return $this
     */
    public function setPanes($panes)
    {
        if ($panes instanceof Pane) {
            $panes = [$panes->getName() => $panes];
        }

        $this->panes = $panes;

        return $this;
    }

    /**
     * Add a new dashboard pane to this home
     *
     * @param Pane|string $pane
     *
     * @return $this
     */
    public function addPane($pane)
    {
        if (! $pane instanceof Pane) {
            $pane = new Pane($pane);
            $pane
                ->setHome($this)
                ->setTitle($pane->getName());
        }

        $this->panes[$pane->getName()] = $pane;

        return $this;
    }

    /**
     * Get an array with pane name=>title format
     *
     * @return string[]
     */
    public function getPaneKeyTitleArr()
    {
        $panes = [];
        foreach ($this->getPanes(true) as $pane) {
            $panes[$pane->getName()] = $pane->getName();
        }

        return $panes;
    }

    /**
     * Remove the given pane from this home
     *
     * @param Pane|string $pane
     *
     * @return $this
     */
    public function removePane($pane)
    {
        $name = $pane instanceof Pane ? $pane->getName() : $pane;
        if (! $this->hasPane($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard pane "%s"', $name);
        }

        $pane = $pane instanceof Pane ? $pane : $this->getPane($pane);
        if (! $pane->isOverriding()) {
            $pane->removeDashlets();

            Dashboard::getConn()->delete(Pane::TABLE, [
                'id = ?'      => $pane->getUuid(),
                'home_id = ?' => $this->getUuid()
            ]);
        }

        return $this;
    }

    /**
     * Remove all/the given dashboard panes from this home
     *
     * @param Pane[] $panes
     *
     * @return $this
     */
    public function removePanes(array $panes = [])
    {
        $panes = ! empty($panes) ? $panes : $this->getPanes();
        foreach ($panes as $pane) {
            $this->removePane($pane);
        }

        return $this;
    }

    /**
     * Load all dashboards this user is assigned to from the DB
     *
     * @return $this
     */
    public function loadPanesFromDB()
    {
        // Skip when this home is either disabled or inactive
        if (! $this->getActive() || $this->isDisabled()) {
            return $this;
        }

        $this->panes = [];
        $panes = Model\Pane::on(Dashboard::getConn())->utilize('home');
        $panes
            ->filter(Filter::equal('home_id', $this->getUuid()))
            ->filter(Filter::equal('username', Dashboard::getUser()->getUsername()));

        foreach ($panes as $pane) {
            $newPane = new Pane($pane->name);
            //$newPane->disable($pane->disable);
            $newPane->fromArray([
                'uuid'     => $pane->id,
                'title'    => $pane->label,
                'priority' => $pane->priority,
                'home'     => $this
            ]);

            $newPane->loadDashletsFromDB();

            $this->panes[$newPane->getName()] = $newPane;
        }

        return $this;
    }

    /**
     * Manage the given pane(s)
     *
     * If you want to move the pane(s) from another to this home,
     * you have to also pass through the origin home with
     *
     * @param Pane|Pane[] $panes
     * @param ?DashboardHome $origin
     * @param bool $mngPaneDashlets
     *
     * @return $this
     */
    public function managePanes($panes, DashboardHome $origin = null, $mngPaneDashlets = false)
    {
        $user = Dashboard::getUser();
        $conn = Dashboard::getConn();

        $panes = is_array($panes) ? $panes : [$panes];
        $order = count($this->getPanes()) + 1;

        foreach ($panes as $pane) {
            $uuid = Dashboard::getSHA1($user->getUsername() . $this->getName() . $pane->getName());
            if (! $pane->isOverriding()) {
                if (! $this->hasPane($pane->getName()) && (! $origin || ! $origin->hasPane($pane->getName()))) {
                    $conn->insert(Pane::TABLE, [
                        'id'       => $uuid,
                        'home_id'  => $this->getUuid(),
                        'name'     => $pane->getName(),
                        'label'    => $pane->getTitle(),
                        'username' => $user->getUsername(),
                        'priority' => $order++
                    ]);
                } elseif (! $this->hasPane($pane->getName()) || ! $origin || ! $origin->hasPane($pane->getName())) {
                    $conn->update(Pane::TABLE, [
                        'id'       => $uuid,
                        'home_id'  => $this->getUuid(),
                        'label'    => $pane->getTitle(),
                        'priority' => $pane->getPriority()
                    ], [
                        'id = ?'      => $pane->getUuid(),
                        'home_id = ?' => $this->getUuid()
                    ]);
                } else {
                    // Failed to move the pane! Should have been handled already by the caller
                    break;
                }

                $pane->setUuid($uuid);
            } else {
                // TODO(TBD)
            }

            $pane->setHome($this);
            if ($mngPaneDashlets) {
                // Those dashboard panes are usually system defaults and go up when
                // the user is clicking on the "Use System Defaults" button
                $dashlets = $pane->getDashlets();
                $pane->setDashlets([]);
                $pane->manageDashlets($dashlets);
            }
        }

        return $this;
    }
}
