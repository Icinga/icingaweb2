<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Exception\NotImplementedError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Dashboard;

use function ipl\Stdlib\get_php_type;

trait DashboardEntries
{
    /**
     * A list of @see BaseDashboard assigned to this dashboard widget
     *
     * @var BaseDashboard
     */
    private $dashboards = [];

    public function hasEntries()
    {
        return ! empty($this->dashboards);
    }

    public function getEntry(string $name)
    {
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to retrieve invalid dashboard entry "%s"', $name);
        }

        return $this->dashboards[$name];
    }

    public function hasEntry(string $name)
    {
        return array_key_exists($name, $this->dashboards);
    }

    public function getEntries()
    {
        return $this->dashboards;
    }

    public function setEntries(array $entries)
    {
        $this->dashboards = $entries;

        return $this;
    }

    public function addEntry(BaseDashboard $dashboard)
    {
        if ($this->hasEntry($dashboard->getName())) {
            $this->getEntry($dashboard->getName())->setProperties($dashboard->toArray(false));
        } else {
            $this->dashboards[$dashboard->getName()] = $dashboard;
        }

        return $this;
    }

    public function getEntryKeyTitleArr()
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

        unset($this->dashboards[$dashboard->getName()]);

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

        if (! $this->hasEntry($dashboard->getName())) {
            $dashboard->setPriority($position);
            $data = [$dashboard];
        } else {
            $data = array_values($this->getEntries());
            array_splice($data, array_search($dashboard->getName(), array_keys($this->getEntries())), 1);
            array_splice($data, $position, 0, [$dashboard]);
        }

        $entries = [];
        foreach ($data as $index => $item) {
            if (count($data) !== 1) {
                $item->setPriority($index);
            }

            $entries[$item->getName()] = $item;
            $this->manageEntry($item, $dashboard->getName() === $item->getName() ? $origin : null);

            if ($dashboard->getName() === $item->getName() && $origin) {
                $origin->unsetEntry($dashboard);
            }
        }

        $this->setEntries($entries);

        return $this;
    }
}
