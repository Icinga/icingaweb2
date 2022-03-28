<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Exception\ProgrammingError;

use function ipl\Stdlib\get_php_type;

trait DashboardControls
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

    public function getEntry($name)
    {
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to retrieve invalid dashboard entry "%s"', $name);
        }

        return $this->dashboards[$name];
    }

    public function hasEntry($name)
    {
        return array_key_exists($name, $this->dashboards);
    }

    public function getEntries()
    {
        // An entry can be added individually afterwards, it might be the case that the priority
        // order gets mixed up, so we have to sort things here before being able to render them
        uasort($this->dashboards, function (BaseDashboard $x, BaseDashboard $y) {
            return $x->getPriority() - $y->getPriority();
        });

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
            $this->getEntry($dashboard->getName())->fromArray($dashboard->toArray(false));
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

    public function createEntry($name, $url = null)
    {
    }

    public function rewindEntries()
    {
        return reset($this->dashboards);
    }

    public function reorderWidget(BaseDashboard $dashboard, $position, Sortable $origin = null)
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

        foreach ($data as $index => $item) {
            if (count($data) !== 1) {
                $item->setPriority($index);
            }

            $this->manageEntry($item, $dashboard->getName() === $item->getName() ? $origin : null);
        }

        return $this;
    }
}
