<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Icinga\Data\StreamInterface;
use Traversable;

/**
 * Generates CSV from a query result
 */
class Csv
{
    /**
     * Render the result of the given query as CSV and write the rendered CSV to the given stream
     *
     * @param   Traversable     $query
     * @param   StreamInterface $stream
     *
     * @return  StreamInterface The given stream
     */
    public static function queryToStream(Traversable $query, StreamInterface $stream)
    {
        $first = true;
        foreach ($query as $row) {
            if ($first) {
                $stream->write(static::renderRow(array_keys((array) $row)));
                $first = false;
            }

            $stream->write(static::renderRow(array_values((array) $row)));
        }

        return $stream;
    }

    /**
     * Return a single CSV row string representing the given columns
     *
     * @param   string[]    $columns
     *
     * @return  string
     */
    protected static function renderRow(array $columns)
    {
        $quoted = array();
        foreach ($columns as $column) {
            $quoted[] = '"' . str_replace('"', '""', $column) . '"';
        }

        return implode(',', $quoted) . "\r\n";
    }
}
