<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook\Common;

use ipl\Sql\Connection;
use RuntimeException;

class DbMigration
{
    /** @var string The sql string to be executed */
    protected $query;

    /** @var string The sql script version the queries are loaded from */
    protected $version;

    /** @var string */
    protected $scriptPath;

    /** @var ?string */
    protected $description;

    /** @var ?string */
    protected $lastState;

    public function __construct(string $version, string $scriptPath)
    {
        $this->scriptPath = $scriptPath;
        $this->version = $version;
    }

    /**
     * Get the sql script version the queries are loaded from
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get upgrade script relative path name
     *
     * @return string
     */
    public function getScriptPath(): string
    {
        return $this->scriptPath;
    }

    /**
     * Get the description of this database migration if any
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of this database migration
     *
     * @param ?string $description
     *
     * @return DbMigration
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the last error message of this hook if any
     *
     * @return ?string
     */
    public function getLastState(): ?string
    {
        return $this->lastState;
    }

    /**
     * Set the last error message
     *
     * @param ?string $message
     *
     * @return $this
     */
    public function setLastState(?string $message): self
    {
        $this->lastState = $message;

        return $this;
    }

    /**
     * Perform the sql migration
     *
     * @param Connection $conn
     *
     * @return $this
     *
     * @throws RuntimeException Throws an error in case of any database errors or when there is nothing to migrate
     */
    public function apply(Connection $conn): self
    {
        if (! $this->query) {
            $statements = @file_get_contents($this->getScriptPath());
            if ($statements === false) {
                throw new RuntimeException(sprintf('Cannot load upgrade script %s', $this->getScriptPath()));
            }

            if (empty($statements)) {
                throw new RuntimeException('Nothing to migrate');
            }

            if (preg_match('/\s*delimiter\s*(\S+)\s*$/im', $statements, $matches)) {
                /** @var string $statements */
                $statements = preg_replace('/\s*delimiter\s*(\S+)\s*$/im', '', $statements);
                /** @var string $statements */
                $statements = preg_replace('/' . preg_quote($matches[1], '/') . '$/m', ';', $statements);
            }

            $this->query = $statements;
        }

        $conn->exec($this->query);

        return $this;
    }
}
