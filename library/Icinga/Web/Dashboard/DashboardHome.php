<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\Http\HttpNotFoundException;
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
     * Get whether this home is the default one
     *
     * @return bool
     */
    public function isDefaultHome(): bool
    {
        return $this->getName() === self::DEFAULT_HOME;
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

    /**
     * Activate the given pane and deactivate all the others
     *
     * @param Pane $pane
     *
     * @return $this
     */
    public function activatePane(Pane $pane): self
    {
        if (! $this->hasEntry($pane->getName())) {
            throw new ProgrammingError('Trying to activate Dashboard Pane "%s" that does not exist.', $pane->getName());
        }

        $active = $this->getActivePane();
        if ($active && $active->getName() !== $pane->getName()) {
            $active->setActive(false);
        }

        $pane->setActive(true);

        return $this;
    }

    /**
     * Determine the active pane currently being loaded
     *
     * @return ?Pane
     */
    public function getActivePane()
    {
        /** @var Pane $pane */
        foreach ($this->getEntries() as $pane) {
            if ($pane->isActive()) {
                return $pane;
            }
        }

        return null;
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

    public function loadDashboardEntries(string $name = null)
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

            $this->addEntry($newPane);
            $newPane->loadDashboardEntries();
        }

        if ($name !== null) {
            if ($this->hasEntry($name)) {
                $pane = $this->getEntry($name);

                $this->activatePane($pane);
            } else {
                throw new HttpNotFoundException(t('Pane "%s" not found'), $name);
            }
        } elseif (($firstPane = $this->rewindEntries())) {
            $this->activatePane($firstPane);
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

    public function manageEntry($entryOrEntries, BaseDashboard $origin = null, bool $manageRecursive = false)
    {
        $user = Dashboard::getUser();
        $conn = DBUtils::getConn();

        $panes = is_array($entryOrEntries) ? $entryOrEntries : [$entryOrEntries];
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
            $movePane = $origin && $origin->hasEntry($pane->getName());

            if (! $this->hasEntry($pane->getName()) && ! $movePane) {
                $conn->insert(Pane::TABLE, [
                    'id'       => $uuid,
                    'home_id'  => $this->getUuid(),
                    'name'     => $pane->getName(),
                    'label'    => $pane->getTitle(),
                    'priority' => $order++
                ]);
            } elseif (! $this->hasEntry($pane->getName()) || ! $movePane) {
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
                    'priority' => $movePane ? $order++ : $pane->getPriority()
                ], $filterCondition);
            } else {
                // Failed to move the pane! Should have already been handled by the caller,
                // though I think it's better that we raise an exception here!!
                throw new AlreadyExistsException(
                    'Dashboard Pane "%s" could not be managed. Dashboard Home "%s" has Pane with the same name!',
                    $pane->getTitle(),
                    $this->getTitle()
                );
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
