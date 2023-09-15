<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Countable;
use DateTime;
use DirectoryIterator;
use Exception;
use Icinga\Application\ClassLoader;
use Icinga\Application\Hook\Common\DbMigrationStep;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Model\Schema;
use Icinga\Web\Session;
use ipl\I18n\Translation;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;
use PDO;
use SplFileInfo;
use stdClass;

/**
 * Allows you to automatically perform database migrations.
 *
 * The version numbers of the sql migrations are determined by extracting the respective migration script names.
 * It's required to place the sql migrate scripts below the respective following directories:
 *
 *   `{IcingaApp,Module}::baseDir()/schema/{mysql,pgsql}-upgrades`
 */
abstract class DbMigrationHook implements Countable
{
    use Translation;

    public const MYSQL_UPGRADE_DIR = 'schema/mysql-upgrades';

    public const PGSQL_UPGRADE_DIR = 'schema/pgsql-upgrades';

    /** @var string Fakes a module when this hook is implemented by the framework itself */
    public const DEFAULT_MODULE = 'icingaweb2';

    /** @var string Migration hook param name */
    public const MIGRATION_PARAM = 'migration';

    public const ALL_MIGRATIONS = 'all-migrations';

    /** @var ?array<string, DbMigrationStep> All pending database migrations of this hook */
    protected $migrations;

    /** @var ?string The current version of this hook */
    protected $version;

    /**
     * Get whether the specified table exists in the given database
     *
     * @param Connection $conn
     * @param string $table
     *
     * @return bool
     */
    public static function tableExists(Connection $conn, string $table): bool
    {
        /** @var stdClass $query */
        $query = $conn->prepexec(
            'SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_name = ?) AS result',
            $table
        )->fetch(PDO::FETCH_OBJ);

        return $query->result;
    }

    /**
     * Get whether the specified column exists in the provided table
     *
     * @param Connection $conn
     * @param string $table
     * @param string $column
     *
     * @return ?string
     */
    public static function getColumnType(Connection $conn, string $table, string $column): ?string
    {
        $pdoStmt = $conn->prepexec(
            sprintf(
                'SELECT %s AS column_type, %s AS column_length FROM information_schema.columns'
                . ' WHERE table_name = ? AND column_name = ?',
                $conn->getAdapter() instanceof Pgsql ? 'udt_name' : 'column_type',
                $conn->getAdapter() instanceof Pgsql ? 'character_maximum_length' : 'NULL'
            ),
            [$table, $column]
        );

        /** @var false|stdClass $result */
        $result = $pdoStmt->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            return null;
        }

        if ($result->column_length !== null) {
            $result->column_type .= '(' . $result->column_length . ')';
        }

        return $result->column_type;
    }

    /**
     * Get statically provided descriptions of the individual migrate scripts
     *
     * @return string[]
     */
    abstract public function providedDescriptions(): array;

    /**
     * Get the full name of the component this hook is implemented by
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the current schema version of this migration hook
     *
     * @return string
     */
    abstract public function getVersion(): string;

    /**
     * Get a database connection
     *
     * @return Connection
     */
    abstract public function getDb(): Connection;

    /**
     * Get all the pending migrations of this hook
     *
     * @return DbMigrationStep[]
     */
    public function getMigrations(): array
    {
        if ($this->migrations === null) {
            $this->migrations = [];

            $this->load();
        }

        return $this->migrations ?? [];
    }

    /**
     * Get the latest migrations limited by the given number
     *
     * @param int $limit
     *
     * @return DbMigrationStep[]
     */
    public function getLatestMigrations(int $limit): array
    {
        $migrations = $this->getMigrations();
        if ($limit > 0) {
            $migrations = array_slice($migrations, -$limit, null, true);
        }

        return array_reverse($migrations);
    }

    /**
     * Apply all pending migrations of this hook
     *
     * @param ?Connection $conn Use the provided database connection to apply the migrations.
     *        Is only used to elevate database users with insufficient privileges.
     *
     * @return bool Whether the migration(s) have been successfully applied
     */
    final public function run(Connection $conn = null): bool
    {
        if (! $conn) {
            $conn = $this->getDb();
        }

        foreach ($this->getMigrations() as $migration) {
            try {
                $migration->apply($conn);

                $this->version = $migration->getVersion();
                unset($this->migrations[$migration->getVersion()]);

                Logger::info(
                    "Applied %s pending migration version %s successfully",
                    $this->getName(),
                    $migration->getVersion()
                );

                $this->storeState($migration->getVersion(), null);
            } catch (Exception $e) {
                Logger::error(
                    "Failed to apply %s pending migration version %s \n%s",
                    $this->getName(),
                    $migration->getVersion(),
                    $e->getMessage()
                );
                Logger::debug($e->getTraceAsString());

                static::insertFailedEntry(
                    $conn,
                    $migration->getVersion(),
                    $e->getMessage() . PHP_EOL . $e->getTraceAsString()
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Get whether this hook is implemented by a module
     *
     * @return bool
     */
    public function isModule(): bool
    {
        return ClassLoader::classBelongsToModule(static::class);
    }

    /**
     * Get the name of the module this hook is implemented by
     *
     * @return string
     */
    public function getModuleName(): string
    {
        if (! $this->isModule()) {
            return static::DEFAULT_MODULE;
        }

        return ClassLoader::extractModuleName(static::class);
    }

    /**
     * Get the number of pending migrations of this hook
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getMigrations());
    }

    /**
     * Get a schema version query
     *
     * @return Query
     */
    abstract protected function getSchemaQuery(): Query;

    protected function load(): void
    {
        $upgradeDir = static::MYSQL_UPGRADE_DIR;
        if ($this->getDb()->getAdapter() instanceof Pgsql) {
            $upgradeDir = static::PGSQL_UPGRADE_DIR;
        }

        if (! $this->isModule()) {
            $path = Icinga::app()->getBaseDir();
        } else {
            $path = Module::get($this->getModuleName())->getBaseDir();
        }

        $descriptions = $this->providedDescriptions();
        $version = $this->getVersion();
        /** @var SplFileInfo $file */
        foreach (new DirectoryIterator($path . DIRECTORY_SEPARATOR . $upgradeDir) as $file) {
            if (preg_match('/^(v)?([^_]+)(?:_(\w+))?\.sql$/', $file->getFilename(), $m, PREG_UNMATCHED_AS_NULL)) {
                [$_, $_, $migrateVersion, $description] = $m;
                if ($migrateVersion && version_compare($migrateVersion, $version, '>')) {
                    $migration = new DbMigrationStep($migrateVersion, $file->getRealPath());
                    if (isset($descriptions[$migrateVersion])) {
                        $migration->setDescription($descriptions[$migrateVersion]);
                    } elseif ($description) {
                        $migration->setDescription(str_replace('_', ' ', $description));
                    }

                    $migration->setLastState($this->loadLastState($migrateVersion));

                    $this->migrations[$migrateVersion] = $migration;
                }
            }
        }

        if ($this->migrations) {
            // Sort all the migrations by their version numbers in ascending order.
            uksort($this->migrations, function ($a, $b) {
                return version_compare($a, $b);
            });
        }
    }

    /**
     * Insert failed migration entry into the database or to the session
     *
     * @param Connection $conn
     * @param string $version
     * @param string $reason
     *
     * @return $this
     */
    protected function insertFailedEntry(Connection $conn, string $version, string $reason): self
    {
        $schemaQuery = $this->getSchemaQuery()
            ->filter(Filter::equal('version', $version));

        if (! static::getColumnType($conn, $schemaQuery->getModel()->getTableName(), 'success')) {
            $this->storeState($version, $reason);
        } else {
            /** @var Schema $schema */
            $schema = $schemaQuery->first();
            if ($schema) {
                $conn->update($schema->getTableName(), [
                    'timestamp' => (new DateTime())->getTimestamp() * 1000.0,
                    'success'   => 'n',
                    'reason'    => $reason
                ], ['id = ?' => $schema->id]);
            } else {
                $conn->insert($schemaQuery->getModel()->getTableName(), [
                    'version'   => $version,
                    'timestamp' => (new DateTime())->getTimestamp() * 1000.0,
                    'success'   => 'n',
                    'reason'    => $reason
                ]);
            }
        }

        return $this;
    }

    /**
     * Store a failed state message in the session for the given version
     *
     * @param string $version
     * @param ?string $reason
     *
     * @return $this
     */
    protected function storeState(string $version, ?string $reason): self
    {
        $session = Session::getSession()->getNamespace('migrations');
        /** @var array<string, string> $states */
        $states = $session->get($this->getModuleName(), []);
        $states[$version] = $reason;

        $session->set($this->getModuleName(), $states);

        return $this;
    }

    /**
     * Load last failed state from database/session for the given version
     *
     * @param string $version
     *
     * @return ?string
     */
    protected function loadLastState(string $version): ?string
    {
        $session = Session::getSession()->getNamespace('migrations');
        /** @var array<string, string> $states */
        $states = $session->get($this->getModuleName(), []);
        if (! isset($states[$version])) {
            $schemaQuery = $this->getSchemaQuery()
                ->filter(Filter::equal('version', $version))
                ->filter(Filter::all(Filter::equal('success', 'n')));

            if (static::getColumnType($this->getDb(), $schemaQuery->getModel()->getTableName(), 'reason')) {
                /** @var Schema $schema */
                $schema = $schemaQuery->first();
                if ($schema) {
                    return $schema->reason;
                }
            }

            return null;
        }

        return $states[$version];
    }
}
