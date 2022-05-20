<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Exception\ProgrammingError;
use Icinga\Model\Home;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Common\DashboardEntries;
use Icinga\Web\Dashboard\Common\Sortable;
use Icinga\Util\DBUtils;
use Icinga\Web\Dashboard\Common\WidgetState;
use ipl\Stdlib\Filter;

use function ipl\Stdlib\get_php_type;

class DashboardHome extends BaseDashboard implements Sortable
{
    use DashboardEntries;
    use WidgetState;

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
    const TABLE = 'icingaweb_dashboard_home';

    /**
     * A type of this dashboard home
     *
     * @var string
     */
    protected $type = Dashboard::SYSTEM;

    /**
     * A flag whether this home has been activated
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Create a new dashboard home from the given model
     *
     * @param Home $home
     *
     * @return DashboardHome
     */
    public static function create(Home $home): self
    {
        $self = new self($home->name);
        $self
            ->setTitle($home->label)
            ->setPriority($home->priority)
            ->setType($home->type)
            ->setUuid($home->id)
            ->setDisabled($home->disabled);

        return $self;
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
    public function setActive(bool $active = true): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get whether this home has been activated
     *
     * @return bool
     */
    public function getActive(): bool
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
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the type of this dashboard home
     *
     * @return string
     */
    public function getType(): string
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
        $pane->removeEntries();

        DBUtils::getConn()->delete(Pane::TABLE, [
            'id = ?'      => $pane->getUuid(),
            'home_id = ?' => $this->getUuid()
        ]);

        return $this;
    }

    public function loadDashboardEntries(string $name = '')
    {
        $this->setEntries([]);
        $panes = \Icinga\Model\Pane::on(DBUtils::getConn())->utilize(self::TABLE);
        $panes
            ->filter(Filter::equal('home_id', $this->getUuid()))
            ->filter(Filter::equal(
                self::TABLE . '.icingaweb_dashboard_owner.id',
                Dashboard::getUser()->getAdditional('id')
            ));

        foreach ($panes as $pane) {
            $newPane = new Pane($pane->name);
            $newPane
                ->setHome($this)
                ->setUuid($pane->id)
                ->setTitle($pane->label)
                ->setPriority($pane->priority);

            $newPane->loadDashboardEntries($name);
            $this->addEntry($newPane);
        }

        return $this;
    }

    public function createEntry(string $name, $url = null)
    {
        $entry = new Pane($name);
        $entry->setHome($this);

        $this->addEntry($entry);

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, bool $manageRecursive = false)
    {
        $user = Dashboard::getUser();
        $conn = DBUtils::getConn();

        $panes = is_array($entry) ? $entry : [$entry];
        // Highest priority is 0, so count($entries) are all always lowest prio + 1
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
            if (! $this->hasEntry($pane->getName()) && (! $origin || ! $origin->hasEntry($pane->getName()))) {
                $conn->insert(Pane::TABLE, [
                    'id'       => $uuid,
                    'home_id'  => $this->getUuid(),
                    'name'     => $pane->getName(),
                    'label'    => $pane->getTitle(),
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

            if ($manageRecursive) {
                // Those dashboard panes are usually system defaults and go up when
                // the user is clicking on the "Use System Defaults" button
                $dashlets = $pane->getEntries();
                $pane->setEntries([]);
                $pane->manageEntry($dashlets);
            }
        }
    }

    public function toArray(bool $stringify = true): array
    {
        return [
            'id'       => $this->getUuid(),
            'name'     => $this->getName(),
            'title'    => $this->getTitle(),
            'priority' => $this->getPriority()
        ];
    }
}
