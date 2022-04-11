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
    public function setHome(DashboardHome $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function createEntry(string $name, $url = null)
    {
        if ($url === null) {
            throw new ConfigurationError('Can\'t create a dashlet "%s" without a valid url', $name);
        }

        $dashlet = new Dashlet($name, $url, $this);
        $this->addEntry($dashlet);

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

    public function loadDashboardEntries(string $name = '')
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

            $this->addEntry($newDashlet);
        }

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, bool $manageRecursive = false)
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
        // Highest priority is 0, so count($entries) are always lowest prio + 1
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
                    $data = $dashlet->getModule();
                    if (($pane = $dashlet->getPane())) {
                        $data .= $pane->getName();
                    }

                    $conn->insert('icingaweb_system_dashlet', [
                        'dashlet_id'        => $uuid,
                        'module_dashlet_id' => Dashboard::getSHA1($data . $dashlet->getName())
                    ]);
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

    public function toArray(bool $stringify = true): array
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
