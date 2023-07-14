<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Application\Icinga;
use Icinga\Application\Modules;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Exception\ProgrammingError;
use Icinga\Model;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Util\DBUtils;
use ipl\Stdlib\Filter;

trait DashboardManager
{
    use DashboardUserManager;

    /**
     * Load the given or all homes (null)
     *
     * @param ?string $name
     *
     * @return void
     */
    public function load(string $name = null, string $activePane = null, bool $loadAll = false)
    {
        $query = Model\Home::on(DBUtils::getConn());
        $query->filter(Filter::equal('user_id', $this::getUser()->getAdditional('id')));

        $this->setEntries([]);
        $home = null;
        if ($name !== null && ! $loadAll) {
            $query->filter(Filter::equal('name', $name));

            /** @var Model\Home $row */
            if (($row = $query->first()) === null) {
                if ($name === DashboardHome::DEFAULT_HOME) {
                    $home = $this->initGetDefaultHome();
                }
            } else {
                $home = DashboardHome::create($row);
                $this->addEntry($home);
            }
        } else {
            foreach ($query as $row) {
                $this->addEntry(DashboardHome::create($row));
            }
        }

        if ($name === null) {
            $home = $this->rewindEntries();
        } elseif (! $home && $name === DashboardHome::DEFAULT_HOME) {
            $home = $this->initGetDefaultHome();
        } elseif (! $home && $this->hasEntry($name)) {
            $home = $this->getEntry($name);
        } elseif (! $home) {
            throw new HttpNotFoundException(t('Home "%s" not found'), $name);
        }

        if ($home) {
            $this->activateEntry($home);
            $home->loadDashboardEntries($activePane);
        }

        if (Icinga::app()->isWeb()) {
            Modules\DashletManager::deployDashlets();
        }
    }

    /**
     * Generate the sha1 hash of the provided string
     *
     * @param string $name
     *
     * @return string
     */
    public static function getSHA1(string $name): string
    {
        return sha1($name, true);
    }

    public function loadDashboardEntries(string $name = null)
    {
        $home = $this->getEntry($name);
        $home->loadDashboardEntries();

        return $this;
    }

    public function removeEntry($home)
    {
        $name = $home instanceof DashboardHome ? $home->getName() : $home;
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard home "%s"', $name);
        }

        $home = $home instanceof DashboardHome ? $home : $this->getEntry($home);
        $home->removeEntries();

        if (! $home->isDefaultHome()) {
            DBUtils::getConn()->delete(DashboardHome::TABLE, ['id = ?' => $home->getUuid()]);
        } elseif (! $home->isDisabled()) {
            DBUtils::getConn()->update(DashboardHome::TABLE, ['disabled' => DBUtils::bool2BoolEnum(true)], [
                'id = ?' => $home->getUuid()
            ]);
        }

        return $this;
    }

    public function manageEntry($entryOrEntries, BaseDashboard $origin = null, $manageRecursive = false)
    {
        $conn = DBUtils::getConn();
        $homes = is_array($entryOrEntries) ? $entryOrEntries : [$entryOrEntries];

        // Highest priority is 0, so count($entries) are always lowest prio + 1
        $priority = $this->countEntries();

        /** @var DashboardHome $home */
        foreach ($homes as $home) {
            if (! $this->hasEntry($home->getName())) {
                $conn->insert(DashboardHome::TABLE, [
                    'user_id'  => $this::getUser()->getAdditional('id'),
                    'name'     => $home->getName(),
                    'label'    => $home->getTitle(),
                    'priority' => $home->isDefaultHome() ? 0 : $priority++,
                    'type'     => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
                ]);

                $home->setUuid($conn->lastInsertId());
            } else {
                $conn->update(DashboardHome::TABLE, [
                    'label'    => $home->getTitle(),
                    'priority' => $home->getPriority(),
                    'disabled' => DBUtils::bool2BoolEnum(false)
                ], ['id = ?' => $home->getUuid()]);
            }
        }

        return $this;
    }

    /**
     * Initialize and get the default dashboard home
     *
     * @return DashboardHome
     */
    public function initGetDefaultHome(): DashboardHome
    {
        if ($this->hasEntry(DashboardHome::DEFAULT_HOME)) {
            return $this->getEntry(DashboardHome::DEFAULT_HOME);
        }

        $default = new DashboardHome(DashboardHome::DEFAULT_HOME);
        $this->manageEntry($default);
        $this->addEntry($default);

        return $default;
    }
}
