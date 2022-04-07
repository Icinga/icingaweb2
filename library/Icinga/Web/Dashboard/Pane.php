<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Model;
use Icinga\Web\Dashboard\Common\DashboardControls;
use Icinga\Web\Dashboard\Common\Sortable;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

use function ipl\Stdlib\get_php_type;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane extends BaseDashboard implements Sortable
{
    use DashboardControls;

    const TABLE = 'icingaweb_dashboard';

    /**
     * A dashboard home this pane is a part of
     *
     * @var DashboardHome
     */
    protected $home;

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

    public function createEntry($name, $url = null)
    {
        $dashlet = new Dashlet($name, $url, $this);
        $this->addDashlet($dashlet);

        return $this;
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
            $this->addEntry($dashlet);
        } elseif (is_string($dashlet) && $url !== null) {
            $this->createEntry($dashlet, $url);
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
        $this->createEntry($name, $url);
        $dashlet = $this->getEntry($name);
        $dashlet
            ->setDescription($description)
            ->setPriority($priority);

        return $this;
    }

    public function removeEntry($dashlet)
    {
        $name = $dashlet instanceof Dashlet ? $dashlet->getName() : $dashlet;
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to remove invalid dashlet: %s', $name);
        }

        if (! $dashlet instanceof Dashlet) {
            $dashlet = $this->getEntry($dashlet);
        }

        Dashboard::getConn()->delete(Dashlet::TABLE, [
            'id = ?'           => $dashlet->getUuid(),
            'dashboard_id = ?' => $this->getUuid()
        ]);

        return $this;
    }

    public function loadDashboardEntries($name = '')
    {
        $dashlets = Model\Dashlet::on(Dashboard::getConn())
            ->utilize(self::TABLE)
            ->with('icingaweb_module_dashlet');
        $dashlets->filter(Filter::equal('dashboard_id', $this->getUuid()));

        $this->setEntries([]);
        foreach ($dashlets as $dashlet) {
            $newDashlet = new Dashlet($dashlet->name, $dashlet->url, $this);
            $newDashlet->fromArray([
                'uuid'        => $dashlet->id,
                'title'       => $dashlet->label,
                'priority'    => $dashlet->priority,
                'pane'        => $this,
                'description' => $dashlet->icingaweb_module_dashlet->description
            ]);

            $this->addDashlet($newDashlet);
        }

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, $manageRecursive = false)
    {
        if ($origin && ! $origin instanceof Pane) {
            throw new \InvalidArgumentException(sprintf(
                __METHOD__ . ' expects parameter "$origin" to be an instance of "%s". Got "%s" instead.',
                get_php_type($this),
                get_php_type($origin)
            ));
        }

        if (! $this->getHome()) {
            throw new \LogicException(
                'Dashlets cannot be managed. Please make sure to set the current dashboard home beforehand.'
            );
        }

        $home = $this->getHome();
        $user = Dashboard::getUser()->getUsername();
        $conn = Dashboard::getConn();

        $dashlets = is_array($entry) ? $entry : [$entry];
        // highest priority is 0, so count($entries) are all always lowest prio + 1
        $order = count($this->getEntries());
        foreach ($dashlets as $dashlet) {
            if (is_array($dashlet)) {
                $this->manageEntry($dashlet, $origin);
            }

            if (! $dashlet instanceof Dashlet) {
                break;
            }

            $uuid = Dashboard::getSHA1($user . $home->getName() . $this->getName() . $dashlet->getName());
            if (! $this->hasEntry($dashlet->getName()) && (! $origin || ! $origin->hasEntry($dashlet->getName()))) {
                $conn->insert(Dashlet::TABLE, [
                    'id'           => $uuid,
                    'dashboard_id' => $this->getUuid(),
                    'name'         => $dashlet->getName(),
                    'label'        => $dashlet->getTitle(),
                    'url'          => $dashlet->getUrl()->getRelativeUrl(),
                    'priority'     => $order++
                ]);

                if ($dashlet->isModuleDashlet()) {
                    $systemUuid = $dashlet->getUuid();
                    if (! $systemUuid && $dashlet->getPane()) {
                        $systemUuid = Dashboard::getSHA1(
                            $dashlet->getModule() . $dashlet->getPane()->getName() . $dashlet->getName()
                        );
                    }

                    if ($systemUuid) {
                        $conn->insert('icingaweb_system_dashlet', [
                            'dashlet_id'        => $uuid,
                            'module_dashlet_id' => $systemUuid
                        ]);
                    }
                }
            } elseif (! $this->hasEntry($dashlet->getName()) || ! $origin
                || ! $origin->hasEntry($dashlet->getName())) {
                $filterCondition = [
                    'id = ?'           => $dashlet->getUuid(),
                    'dashboard_id = ?' => $this->getUuid()
                ];

                if ($origin && $origin->hasEntry($dashlet->getName())) {
                    $filterCondition = [
                        'id = ?'           => $origin->getEntry($dashlet->getName())->getUuid(),
                        'dashboard_id = ?' => $origin->getUuid()
                    ];
                }

                $conn->update(Dashlet::TABLE, [
                    'id'           => $uuid,
                    'dashboard_id' => $this->getUuid(),
                    'label'        => $dashlet->getTitle(),
                    'url'          => $dashlet->getUrl()->getRelativeUrl(),
                    'priority'     => $dashlet->getPriority()
                ], $filterCondition);
            } else {
                // This should have already been handled by the caller
                break;
            }

            $dashlet->setPane($this);
        }

        return $this;
    }

    public function toArray($stringify = true)
    {
        $home = $this->getHome();
        return [
            'id'       => $this->getUuid(),
            'name'     => $this->getName(),
            'label'    => $this->getTitle(),
            'home'     => ! $stringify ? $home : ($home ? $home->getName() : null),
            'priority' => $this->getPriority(),
        ];
    }
}
