<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

use Countable;
use Generator;
use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Setup\Utils\DbTool;
use Icinga\Module\Setup\WebWizard;
use ipl\I18n\Translation;
use ipl\Sql;
use ReflectionClass;

/**
 * Migration manager allows you to manage all pending migrations in a structured way.
 */
final class MigrationManager implements Countable
{
    use Translation;

    /** @var array<string, DbMigrationHook> All pending migration hooks */
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
     * @return array<string, DbMigrationHook>
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
     * @return DbMigrationHook
     *
     * @throws NotFoundError When there are no pending migrations matching the given module name
     */
    public function getMigration(string $module): DbMigrationHook
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
        if ($migration->isModule() && $this->hasMigrations(DbMigrationHook::DEFAULT_MODULE)) {
            return false;
        }

        return $this->apply($migration);
    }

    /**
     * Apply the given migration hook
     *
     * @param DbMigrationHook $hook
     * @param ?array<string, string> $elevateConfig
     *
     * @return bool
     */
    public function apply(DbMigrationHook $hook, array $elevateConfig = null): bool
    {
        if ($hook->isModule() && $this->hasMigrations(DbMigrationHook::DEFAULT_MODULE)) {
            Logger::error(
                'Please apply the Icinga Web pending migration(s) first or apply all the migrations instead'
            );

            return false;
        }

        $conn = $hook->getDb();
        if ($elevateConfig && ! $this->checkRequiredPrivileges($conn)) {
            $conn = $this->elevateDatabaseConnection($conn, $elevateConfig);
        }

        if ($hook->run($conn)) {
            unset($this->pendingMigrations[$hook->getModuleName()]);

            Logger::info('Applied pending %s migrations successfully', $hook->getName());

            return true;
        }

        return false;
    }

    /**
     * Apply all pending modules/framework migrations
     *
     * @param ?array<string, string> $elevateConfig
     *
     * @return bool
     */
    public function applyAll(array $elevateConfig = null): bool
    {
        $default = DbMigrationHook::DEFAULT_MODULE;
        if ($this->hasMigrations($default)) {
            $migration = $this->getMigration($default);
            if (! $this->apply($migration, $elevateConfig)) {
                return false;
            }
        }

        $succeeded = true;
        foreach ($this->getPendingMigrations() as $migration) {
            if (! $this->apply($migration, $elevateConfig) && $succeeded) {
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
     * @return Generator<DbMigrationHook>
     */
    public function yieldMigrations(bool $modules = false): Generator
    {
        foreach ($this->getPendingMigrations() as $migration) {
            if ($modules === $migration->isModule()) {
                yield $migration;
            }
        }
    }

    /**
     * Get the required database privileges for database migrations
     *
     * @return string[]
     */
    public function getRequiredDatabasePrivileges(): array
    {
        return ['CREATE','SELECT','INSERT','UPDATE','DELETE','DROP','ALTER','CREATE VIEW','INDEX','EXECUTE'];
    }

    /**
     * Verify whether all database users of all pending migrations do have the required SQL privileges
     *
     * @param ?array<string, string> $elevateConfig
     * @param bool $canIssueGrant
     *
     * @return bool
     */
    public function validateDatabasePrivileges(array $elevateConfig = null, bool $canIssueGrant = false): bool
    {
        if (! $this->hasPendingMigrations()) {
            return true;
        }

        foreach ($this->getPendingMigrations() as $migration) {
            if (! $this->checkRequiredPrivileges($migration->getDb(), $elevateConfig, $canIssueGrant)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if there are missing grants for the Icinga Web database and fix them
     *
     * This fixes the following problems on existing installations:
     * - Setups made by the wizard have no access to `icingaweb_schema`
     * - Setups made by the wizard have no DDL grants
     * - Setups done manually using the advanced documentation chapter have no DDL grants
     *
     * @param Sql\Connection $db
     * @param array<string, string> $elevateConfig
     */
    public function fixIcingaWebMysqlGrants(Sql\Connection $db, array $elevateConfig): void
    {
        $wizardProperties = (new ReflectionClass(WebWizard::class))
            ->getDefaultProperties();
        /** @var array<int, string> $privileges */
        $privileges = $wizardProperties['databaseUsagePrivileges'];
        /** @var array<int, string> $tables */
        $tables = $wizardProperties['databaseTables'];

        $actualUsername = $db->getConfig()->username;
        $db = $this->elevateDatabaseConnection($db, $elevateConfig);
        $tool = $this->createDbTool($db);
        $tool->connectToDb();

        if ($tool->checkPrivileges(['SELECT'], [], $actualUsername)) {
            // Checks only database level grants. If this succeeds, the grants were issued manually.
            if (! $tool->checkPrivileges($privileges, [], $actualUsername) && $tool->isGrantable($privileges)) {
                // Any missing grant is now granted on database level as well, not to mix things up
                $tool->grantPrivileges($privileges, [], $actualUsername);
            }
        } elseif (! $tool->checkPrivileges($privileges, $tables, $actualUsername) && $tool->isGrantable($privileges)) {
            // The above ensures that if this fails, we can safely apply table level grants, as it's
            // very likely that the existing grants were issued by the setup wizard
            $tool->grantPrivileges($privileges, $tables, $actualUsername);
        }
    }

    /**
     * Create and return a DbTool instance
     *
     * @param Sql\Connection $db
     *
     * @return DbTool
     */
    private function createDbTool(Sql\Connection $db): DbTool
    {
        $config = $db->getConfig();

        return new DbTool(array_merge([
            'db' => $config->db,
            'host' => $config->host,
            'port' => $config->port,
            'dbname' => $config->dbname,
            'username' => $config->username,
            'password' => $config->password,
            'charset'  => $config->charset
        ], $db->getAdapter()->getOptions($config)));
    }

    protected function load(): void
    {
        $this->pendingMigrations = [];

        /** @var DbMigrationHook $hook */
        foreach (Hook::all('DbMigration') as $hook) {
            if (empty($hook->getMigrations())) {
                continue;
            }

            $this->pendingMigrations[$hook->getModuleName()] = $hook;
        }

        ksort($this->pendingMigrations);
    }

    /**
     * Check the required SQL privileges of the given connection
     *
     * @param Sql\Connection $conn
     * @param ?array<string, string> $elevateConfig
     * @param bool $canIssueGrants
     *
     * @return bool
     */
    protected function checkRequiredPrivileges(
        Sql\Connection $conn,
        array $elevateConfig = null,
        bool $canIssueGrants = false
    ): bool {
        if ($elevateConfig) {
            $conn = $this->elevateDatabaseConnection($conn, $elevateConfig);
        }

        $wizardProperties = (new ReflectionClass(WebWizard::class))
            ->getDefaultProperties();
        /** @var array<int, string> $tables */
        $tables = $wizardProperties['databaseTables'];

        $dbTool = $this->createDbTool($conn);
        $dbTool->connectToDb();
        if (! $dbTool->checkPrivileges($this->getRequiredDatabasePrivileges())
            && ! $dbTool->checkPrivileges($this->getRequiredDatabasePrivileges(), $tables)
        ) {
            return false;
        }

        if ($canIssueGrants && ! $dbTool->isGrantable($this->getRequiredDatabasePrivileges())) {
            return false;
        }

        return true;
    }

    /**
     * Override the database config of the given connection by the specified new config
     *
     * Overrides only the username and password of existing database connection.
     *
     * @param Sql\Connection $conn
     * @param array<string, string> $elevateConfig
     * @return Sql\Connection
     */
    protected function elevateDatabaseConnection(Sql\Connection $conn, array $elevateConfig): Sql\Connection
    {
        $config = clone $conn->getConfig();
        $config->username = $elevateConfig['username'];
        $config->password = $elevateConfig['password'];

        return new Sql\Connection($config);
    }

    /**
     * Get all pending migrations as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $framework = [];
        $serialize = function (DbMigrationHook $hook): array {
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
