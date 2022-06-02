<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Application\Modules;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Model;
use Icinga\Web\Dashboard\Common\DashboardEntries;
use Icinga\Web\Dashboard\Common\Sortable;
use Icinga\Util\DBUtils;
use Icinga\Web\Dashboard\Common\WidgetState;
use ipl\Stdlib\Filter;

use function ipl\Stdlib\get_php_type;

/**
 * A pane, displaying different Dashboard dashlets
 */
class Pane extends BaseDashboard implements Sortable
{
    use DashboardEntries;
    use WidgetState;

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

        DBUtils::getConn()->delete(Dashlet::TABLE, [
            'id = ?'           => $dashlet->getUuid(),
            'dashboard_id = ?' => $this->getUuid()
        ]);

        return $this;
    }

    public function loadDashboardEntries(string $name = null)
    {
        $dashlets = Model\Dashlet::on(DBUtils::getConn())
            ->utilize(self::TABLE)
            ->with('icingaweb_module_dashlet');
        $dashlets->filter(Filter::equal('dashboard_id', $this->getUuid()));

        // TODO(yh): Qualify those columns properly??
        $dashlets->getSelectBase()->columns([
            'system_dashlet_id'        => 'icingaweb_dashlet_icingaweb_system_dashlet.dashlet_id',
            'system_module_dashlet_id' => 'icingaweb_dashlet_icingaweb_system_dashlet.module_dashlet_id',
        ]);

        $this->setEntries([]);
        foreach ($dashlets as $dashlet) {
            $newDashlet = new Dashlet($dashlet->name, $dashlet->url, $this);
            $newDashlet
                ->setPane($this)
                ->setUuid($dashlet->id)
                ->setTitle($dashlet->label)
                ->setPriority($dashlet->priority)
                ->setDisabled($dashlet->disabled)
                ->setModule($dashlet->icingaweb_module_dashlet->module ?? '')
                ->setModuleDashlet($dashlet->system_dashlet_id !== null)
                ->setDescription($dashlet->icingaweb_module_dashlet->description);

            $this->addEntry($newDashlet);

            if (Modules\DashletManager::isOrphaned($newDashlet)
                || (
                    $newDashlet->isModuleDashlet()
                    && $dashlet->system_module_dashlet_id === null
                )
            ) {
                // The module from which this dashlet originates doesn't exist anymore
                $this->removeEntry($newDashlet);

                unset($this->dashboards[$newDashlet->getName()]);
            } elseif (! $newDashlet->isDisabled() && ! Modules\DashletManager::isUsable($newDashlet)) {
                // The module from which this dashlet originates is probably disabled,
                // so don't load this dashlet anymore and disable it
                $newDashlet->setDisabled(true);

                $this->manageEntry($newDashlet);
            } elseif ($newDashlet->isDisabled() && Modules\DashletManager::isUsable($newDashlet)) {
                // Dashlet was disabled, but it can be used now, so enabled it again
                $newDashlet->setDisabled(false);

                $this->manageEntry($newDashlet);
            }

            if ($newDashlet->isDisabled()) {
                unset($this->dashboards[$newDashlet->getName()]);
            }
        }

        return $this;
    }

    public function manageEntry($entryOrEntries, BaseDashboard $origin = null, bool $manageRecursive = false)
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
        $conn = DBUtils::getConn();

        $dashlets = is_array($entryOrEntries) ? $entryOrEntries : [$entryOrEntries];
        // Highest priority is 0, so count($entries) are always lowest prio + 1
        $order = count($this->getEntries());
        foreach ($dashlets as $dashlet) {
            if (is_array($dashlet)) {
                $this->manageEntry($dashlet, $origin);
            }

            if (! $dashlet instanceof Dashlet) {
                break;
            }

            $url = $dashlet->getUrl();
            $url = is_string($url) ?: $url->getRelativeUrl();
            $uuid = Dashboard::getSHA1($user . $home->getName() . $this->getName() . $dashlet->getName());
            $moveDashlet = $origin && $origin->hasEntry($dashlet->getName());

            if (! $this->hasEntry($dashlet->getName()) && ! $moveDashlet) {
                $conn->insert(Dashlet::TABLE, [
                    'id'           => $uuid,
                    'dashboard_id' => $this->getUuid(),
                    'name'         => $dashlet->getName(),
                    'label'        => $dashlet->getTitle(),
                    'url'          => $url,
                    'priority'     => $order++,
                    'disabled'     => DBUtils::bool2BoolEnum($dashlet->isDisabled())
                ]);

                if ($dashlet->isModuleDashlet()) {
                    $systemUuid = $dashlet->getUuid();
                    if (! $systemUuid) {
                        $data = $dashlet->getModule();
                        if (($pane = $dashlet->getPane())) {
                            $data .= $pane->getName();
                        }

                        $systemUuid = Dashboard::getSHA1($data . $dashlet->getName());
                    }

                    $conn->insert('icingaweb_system_dashlet', [
                        'dashlet_id'        => $uuid,
                        'module_dashlet_id' => $systemUuid
                    ]);
                }
            } elseif (! $this->hasEntry($dashlet->getName()) || ! $moveDashlet) {
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
                    'url'          => $url,
                    'priority'     => $moveDashlet ? $order++ : $dashlet->getPriority(),
                    'disabled'     => DBUtils::bool2BoolEnum($dashlet->isDisabled())
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
