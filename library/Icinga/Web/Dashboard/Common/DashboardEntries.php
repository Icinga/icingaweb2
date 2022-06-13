<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Exception\NotImplementedError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Dashboard;

use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use function ipl\Stdlib\get_php_type;

trait DashboardEntries
{
    /**
     * A list of @see BaseDashboard assigned to this dashboard widget
     *
     * @var DashboardHome[]|Pane[]|Dashlet[]
     */
    private $dashboards = [];

    public function hasEntries(): bool
    {
        return ! empty($this->dashboards);
    }

    public function countEntries(): int
    {
        return count($this->dashboards);
    }

    public function getEntry(string $name)
    {
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to retrieve invalid dashboard entry "%s"', $name);
        }

        return $this->dashboards[strtolower($name)];
    }

    public function hasEntry(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->dashboards);
    }

    public function getEntries(): array
    {
        return $this->dashboards;
    }

    public function setEntries(array $entries)
    {
        $this->dashboards = array_change_key_case($entries);

        return $this;
    }

    public function addEntry(BaseDashboard $dashboard)
    {
        if ($this->hasEntry($dashboard->getName())) {
            $this->getEntry($dashboard->getName())->setProperties($dashboard->toArray(false));
        } else {
            $this->dashboards[strtolower($dashboard->getName())] = $dashboard;
        }

        return $this;
    }

    public function getEntryKeyTitleArr(): array
    {
        $dashboards = [];
        foreach ($this->getEntries() as $dashboard) {
            $dashboards[$dashboard->getName()] = $dashboard->getTitle();
        }

        return $dashboards;
    }

    public function removeEntries(array $entries = [])
    {
        $dashboards = ! empty($entries) ? $entries : $this->getEntries();
        foreach ($dashboards as $dashboard) {
            $this->removeEntry($dashboard);
        }

        return $this;
    }

    public function createEntry(string $name, $url = null)
    {
        throw new NotImplementedError('Not yet implemented by the concrete class!!');
    }

    public function rewindEntries()
    {
        $dashboards = $this->getEntries();
        if ($this instanceof Dashboard) {
            $dashboards = array_filter($dashboards, function ($home) {
                return ! $home->isDisabled();
            });
        }

        return reset($dashboards);
    }

    public function unsetEntry(BaseDashboard $dashboard)
    {
        if (! $this->hasEntry($dashboard->getName())) {
            throw new ProgrammingError('Trying to unset an invalid Dashboard entry: "%s"', $dashboard->getName());
        }

        unset($this->dashboards[strtolower($dashboard->getName())]);

        return $this;
    }

    public function reorderWidget(BaseDashboard $dashboard, int $position, Sortable $origin = null)
    {
        if ($origin && ! $origin instanceof $this) {
            throw new \InvalidArgumentException(sprintf(
                __METHOD__ . ' expects parameter "$origin" to be an instance of "%s". Got "%s" instead.',
                get_php_type($this),
                get_php_type($origin)
            ));
        }

        if ($this->countEntries() <= 1 || $this->countEntries() === $position) {
            $data = array_values($this->getEntries());
            if (! $this->hasEntries() || $this->countEntries() === $position) {
                $data[] = $dashboard;
            } else {
                array_unshift($data, $dashboard);
            }
        } else {
            if (! $this->hasEntry($dashboard->getName())) {
                $this->addEntry($dashboard);
            }

            $data = array_values($this->getEntries());
            array_splice($data, array_search(strtolower($dashboard->getName()), array_keys($this->getEntries())), 1);
            array_splice($data, $position, 0, [$dashboard]);

            // We have copied the data with the new dashboard entry, so we need to unset
            // the passed entry to prevent duplicate entry errors
            if ($origin && $origin->hasEntry($dashboard->getName())) {
                $this->unsetEntry($dashboard);
            }
        }

        $entries = [];
        foreach ($data as $index => $item) {
            $item->setPriority($index);

            $entries[$item->getName()] = $item;
            $this->manageEntry($item, $dashboard->getName() === $item->getName() ? $origin : null);
        }

        if ($origin && $origin->hasEntry($dashboard->getName())) {
            // The dashboard entry is moved to another one
            $origin->unsetEntry($dashboard);
        }

        $this->setEntries($entries);

        return $this;
    }
}
