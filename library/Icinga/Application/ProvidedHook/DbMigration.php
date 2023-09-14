<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\MigrationHook;
use Icinga\Common\Database;
use Icinga\Model\Schema;
use ipl\Orm\Query;
use ipl\Sql\Connection;

class DbMigration extends MigrationHook
{
    use Database {
        getDb as public getPublicDb;
    }

    public function getDb(): Connection
    {
        return $this->getPublicDb();
    }

    public function getName(): string
    {
        return $this->translate('Icinga Web');
    }

    public function providedDescriptions(): array
    {
        return [];
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            $conn = $this->getDb();
            $schemaQuery = $this->getSchemaQuery()
                ->orderBy('id', SORT_DESC)
                ->limit(2);

            if (static::getColumnType($conn, $schemaQuery->getModel()->getTableName(), 'success')) {
                /** @var Schema $schema */
                foreach ($schemaQuery as $schema) {
                    if ($schema->success) {
                        $this->version = $schema->version;

                        break;
                    }
                }

                if (! $this->version) {
                    $this->version = '2.12.0';
                }
            } elseif (static::tableExists($conn, $schemaQuery->getModel()->getTableName())) {
                $this->version = '2.11.0';
            } elseif (static::tableExists($conn, 'icingaweb_rememberme')) {
                $randomIvType = static::getColumnType($conn, 'icingaweb_rememberme', 'random_iv');
                if ($randomIvType === 'varchar(32)') {
                    $this->version = '2.9.1';
                } else {
                    $this->version = '2.9.0';
                }
            } else {
                $usernameType = static::getColumnType($conn, 'icingaweb_group_membership', 'username');
                if ($usernameType === 'varchar(254)') {
                    $this->version = '2.5.0';
                } else {
                    $this->version = '2.0.0';
                }
            }
        }

        return $this->version;
    }

    protected function getSchemaQuery(): Query
    {
        return Schema::on($this->getDb());
    }
}
