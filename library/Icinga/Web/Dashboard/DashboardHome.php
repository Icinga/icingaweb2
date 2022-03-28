<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Common\DashboardControls;
use Icinga\Web\Dashboard\Common\Sortable;
use Icinga\Web\Navigation\DashboardHomeItem;
use ipl\Stdlib\Filter;

use function ipl\Stdlib\get_php_type;

class DashboardHome extends BaseDashboard implements Sortable
{
    use DashboardControls;

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
     * A type of this dashboard home
     *
     * @var string
     */
    protected $type = Dashboard::SYSTEM;

    protected $active;

    /**
     * Create a new dashboard home from the given home item
     *
     * @param DashboardHomeItem $homeItem
     *
     * @return DashboardHome
     */
    public static function create(DashboardHomeItem $homeItem)
    {
        return new self($homeItem->getName(), $homeItem->getAttributes());
    }

    /**
     * Set whether this home is active
     *
     * DB dashboards will load only when this home has been activated
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
     * Get whether this home has been activated
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
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
     * Get the type of this dashboard home
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function removeEntry($pane)
    {
        $name = $pane instanceof Pane ? $pane->getName() : $pane;
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard pane "%s"', $name);
        }

        $pane = $pane instanceof Pane ? $pane : $this->getEntry($pane);
        if (! $pane->isOverriding()) {
            $pane->removeEntries();

            Dashboard::getConn()->delete(Pane::TABLE, [
                'id = ?'      => $pane->getUuid(),
                'home_id = ?' => $this->getUuid()
            ]);
        }

        return $this;
    }

    public function loadDashboardEntries($name = '')
    {
        if (! $this->getActive()) {
            return $this;
        }

        $this->setEntries([]);
        $panes = \Icinga\Model\Pane::on(Dashboard::getConn())->utilize('home');
        $panes
            ->filter(Filter::equal('home_id', $this->getUuid()))
            ->filter(Filter::equal('username', Dashboard::getUser()->getUsername()));

        foreach ($panes as $pane) {
            $newPane = new Pane($pane->name);
            $newPane->fromArray([
                'uuid'     => $pane->id,
                'title'    => $pane->label,
                'priority' => $pane->priority,
                'home'     => $this
            ]);

            $newPane->loadDashboardEntries($name);
            $this->addEntry($newPane);
        }

        return $this;
    }

    public function createEntry($name, $url = null)
    {
        $entry = new Pane($name);
        $entry->setHome($this);

        $this->addEntry($entry);

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, $updateChildEntries = false)
    {
        $user = Dashboard::getUser();
        $conn = Dashboard::getConn();

        $panes = is_array($entry) ? $entry : [$entry];
        // highest priority is 0, so count($entries) are all always lowest prio + 1
        $order = count($this->getEntries());

        if ($origin && ! $origin instanceof DashboardHome) {
            throw new \InvalidArgumentException(sprintf(
                __METHOD__ . ' expects parameter "$origin" to be an instance of "%s". Got "%s" instead.',
                get_php_type($this),
                get_php_type($origin)
            ));
        }

        /** @var Pane $pane */
        foreach ($panes as $pane) {
            $uuid = Dashboard::getSHA1($user->getUsername() . $this->getName() . $pane->getName());
            if (! $pane->isOverriding()) {
                if (! $this->hasEntry($pane->getName()) && (! $origin || ! $origin->hasEntry($pane->getName()))) {
                    $conn->insert(Pane::TABLE, [
                        'id'       => $uuid,
                        'home_id'  => $this->getUuid(),
                        'name'     => $pane->getName(),
                        'label'    => $pane->getTitle(),
                        'username' => $user->getUsername(),
                        'priority' => $order++
                    ]);
                } elseif (! $this->hasEntry($pane->getName()) || ! $origin || ! $origin->hasEntry($pane->getName())) {
                    $filterCondition = [
                        'id = ?'      => $pane->getUuid(),
                        'home_id = ?' => $this->getUuid()
                    ];

                    if ($origin && $origin->hasEntry($pane->getName())) {
                        $filterCondition = [
                            'id = ?'      => $origin->getEntry($pane->getName())->getUuid(),
                            'home_id = ?' => $origin->getUuid()
                        ];
                    }

                    $conn->update(Pane::TABLE, [
                        'id'       => $uuid,
                        'home_id'  => $this->getUuid(),
                        'label'    => $pane->getTitle(),
                        'priority' => $pane->getPriority()
                    ], $filterCondition);
                } else {
                    // Failed to move the pane! Should have been handled already by the caller
                    break;
                }

                $pane->setHome($this);
                $pane->setUuid($uuid);
            }

            if ($updateChildEntries) {
                // Those dashboard panes are usually system defaults and go up when
                // the user is clicking on the "Use System Defaults" button
                $dashlets = $pane->getEntries();
                $pane->setEntries([]);
                $pane->manageEntry($dashlets);
            }
        }
    }

    public function toArray($stringify = true)
    {
        return [
            'id'       => $this->getUuid(),
            'name'     => $this->getName(),
            'title'    => $this->getTitle(),
            'priority' => $this->getPriority()
        ];
    }
}
