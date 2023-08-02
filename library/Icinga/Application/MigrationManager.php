<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

use Countable;
use Generator;
use Icinga\Application\Hook\MigrationHook;
use Icinga\Exception\NotFoundError;
use ipl\I18n\Translation;

/**
 * Migration manager encapsulates PHP code and DB migrations and manages all pending migrations in a
 * structured way.
 */
final class MigrationManager implements Countable
{
    use Translation;

    /** @var array<string, MigrationHook> All pending migration hooks */
    protected $pendingMigrations;

    /** @var MigrationManager */
    private static $instance;

    private function __construct()
    {
    }

    /**
     * Get the instance of this manager
     *
     * @return $this
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get all pending migrations
     *
     * @return array<string, MigrationHook>
     */
    public function getPendingMigrations(): array
    {
        if ($this->pendingMigrations === null) {
            $this->load();
        }

        return $this->pendingMigrations;
    }

    /**
     * Get whether there are any pending migrations
     *
     * @return bool
     */
    public function hasPendingMigrations(): bool
    {
        return $this->count() > 0;
    }

    public function hasMigrations(string $module): bool
    {
        if (! $this->hasPendingMigrations()) {
            return false;
        }

        return isset($this->getPendingMigrations()[$module]);
    }

    /**
     * Get pending migration matching the given module name
     *
     * @param string $module
     *
     * @return MigrationHook
     *
     * @throws NotFoundError When there are no pending PHP code migrations matching the given module name
     */
    public function getMigration(string $module): MigrationHook
    {
        if (! $this->hasMigrations($module)) {
            throw new NotFoundError('There are no pending migrations matching the given name: %s', $module);
        }

        return $this->getPendingMigrations()[$module];
    }

    /**
     * Get the number of all pending migrations
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getPendingMigrations());
    }

    /**
     * Apply all pending migrations matching the given migration module name
     *
     * @param string $module
     *
     * @return bool
     */
    public function applyByName(string $module): bool
    {
        $migration = $this->getMigration($module);
        if ($migration->isModule() && $this->hasMigrations(MigrationHook::DEFAULT_MODULE)) {
            return false;
        }

        return $this->apply($migration);
    }

    /**
     * Apply the given migration hook
     *
     * @param MigrationHook $hook
     *
     * @return bool
     */
    public function apply(MigrationHook $hook): bool
    {
        if ($hook->isModule() && $this->hasMigrations(MigrationHook::DEFAULT_MODULE)) {
            Logger::error(
                'Please apply the Icinga Web pending migration(s) first or apply all the migrations instead'
            );

            return false;
        }

        if ($hook->run()) {
            unset($this->pendingMigrations[$hook->getModuleName()]);

            Logger::info('Applied pending %s migrations successfully', $hook->getName());

            return true;
        }

        return false;
    }

    /**
     * Apply all pending modules/framework migrations
     *
     * @return bool
     */
    public function applyAll(): bool
    {
        $default = MigrationHook::DEFAULT_MODULE;
        if ($this->hasMigrations($default)) {
            $migration = $this->getMigration($default);
            if (! $this->apply($migration)) {
                return false;
            }
        }

        $succeeded = true;
        foreach ($this->getPendingMigrations() as $migration) {
            if (! $this->apply($migration) && $succeeded) {
                $succeeded = false;
            }
        }

        return $succeeded;
    }

    /**
     * Yield module and framework pending migrations separately
     *
     * @param bool $modules
     *
     * @return Generator<MigrationHook>
     */
    public function yieldMigrations(bool $modules = false): Generator
    {
        foreach ($this->getPendingMigrations() as $migration) {
            if ($modules === $migration->isModule()) {
                yield $migration;
            }
        }
    }

    protected function load(): void
    {
        $this->pendingMigrations = [];

        /** @var MigrationHook $hook */
        foreach (Hook::all('migration') as $hook) {
            if (empty($hook->getMigrations())) {
                continue;
            }

            $this->pendingMigrations[$hook->getModuleName()] = $hook;
        }

        ksort($this->pendingMigrations);
    }

    /**
     * Get all pending migrations as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $framework = [];
        $serialize = function (MigrationHook $hook): array {
            $serialized = [
                'name'             => $hook->getName(),
                'module'           => $hook->getModuleName(),
                'isModule'         => $hook->isModule(),
                'migrated_version' => $hook->getVersion(),
                'migrations'       => []
            ];

            foreach ($hook->getMigrations() as $migration) {
                $serialized['migrations'][$migration->getVersion()] = [
                    'path'  => $migration->getScriptPath(),
                    'error' => $migration->getLastState()
                ];
            }

            return $serialized;
        };

        foreach ($this->yieldMigrations() as $migration) {
            $framework[] = $serialize($migration);
        }

        $modules = [];
        foreach ($this->yieldMigrations(true) as $migration) {
            $modules[] = $serialize($migration);
        }

        return ['System'  => $framework, 'Modules' => $modules];
    }
}
