<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Common\Database;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Expression;

/**
 * Just a bunch of DB utils
 */
class DBUtils
{
    use Database;

    /** @var Connection */
    private static $conn;

    /**
     * Get Database connection
     *
     * This is needed because we don't want to always initiate a new DB connection when calling $this->getDb().
     * And as we are using PDO transactions to manage the dashboards, this wouldn't work if $this->getDb()
     * is called over again after a transaction has been initiated
     *
     * @return Connection
     */
    public static function getConn(): Connection
    {
        if (self::$conn === null) {
            self::$conn = (new self())->getDb();
        }

        return self::$conn;
    }

    /**
     * Set the database connection
     *
     * You can set the DB connection beforehand if you want, but you don't need to. You can just call
     * the {@see getConn()} method if you have a valid resource factory configured. This is only used
     * for the unit tests in order to allow to fake the DB connection.
     *
     * @param Connection $conn
     */
    public static function setConn(Connection $conn): void
    {
        self::$conn = $conn;
    }

    /**
     * Get whether the DB connection being used is an instance of {@see Pgsql}
     *
     * @return bool
     */
    public static function isPgsql(): bool
    {
        return self::getConn()->getAdapter() instanceof Pgsql;
    }

    /**
     * Get hex encoded uuid expression of the given binary data
     *
     * @param mixed $uuid
     *
     * @return Expression|string
     */
    public static function getBinaryExpr($uuid)
    {
        if (! self::isPgsql() || (! is_string($uuid) || ! self::isBinary($uuid))) {
            return $uuid;
        }

        return new Expression(sprintf("DECODE('%s', 'hex')", bin2hex($uuid)));
    }

    /**
     * Transform the given binary data into a valid hex format that pgsql can understand
     *
     * @param mixed $uuid
     *
     * @return mixed|string
     */
    public static function binary2Hex($uuid)
    {
        if (! self::isPgsql() || (! is_string($uuid) || ! self::isBinary($uuid))) {
            return $uuid;
        }

        return sprintf('\\x%s', bin2hex($uuid));
    }

    /**
     * Transform boolean types to DB bool enums ('y', 'n')
     *
     * @param bool $value
     *
     * @return string
     */
    public static function bool2BoolEnum(bool $value): string
    {
        return $value ? 'y' : 'n';
    }

    /**
     * Get whether the given data is a binary string
     *
     * @param string $data
     *
     * @return bool
     */
    public static function isBinary(string $data): bool
    {
        // Stolen from php.net
        $data = preg_replace('/\s/', '', $data ?? '');

        return ! empty($data) && ! ctype_print($data);
    }

    /**
     * Transform binary values to hex format
     *
     * @param array $values
     *
     * @return void
     */
    public static function transformValues(array &$values): void
    {
        foreach ($values as &$value) {
            if (! is_string($value)) {
                continue;
            }

            $value = DBUtils::binary2Hex($value);
        }
    }
}
