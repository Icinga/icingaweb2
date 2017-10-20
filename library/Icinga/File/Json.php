<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use Icinga\Exception\IcingaException;
use Icinga\Util\Buffer;
use Psr\Http\Message\StreamInterface;
use stdClass;
use Traversable;

/**
 * Generates JSON from a query result
 */
class Json
{
    /**
     * Render the result of the given query as JSON and write the rendered JSON to the given stream
     *
     * @param   Traversable     $query
     * @param   StreamInterface $stream
     *
     * @return  StreamInterface The given stream
     */
    public static function queryToStream(Traversable $query, StreamInterface $stream)
    {
        $stream->write('[');

        $first = true;
        foreach ($query as $row) {
            if ($first) {
                $first = false;
            } else {
                $stream->write(',');
            }
            $stream->write(static::renderRow($row));
        }

        $stream->write(']');

        return $stream;
    }

    /**
     * Return a JSON string representing the given columns of a single row
     *
     * @param   stdClass    $columns
     *
     * @return  string
     *
     * @throws  IcingaException     In case of an error
     */
    protected static function renderRow(stdClass $columns)
    {
        $result = json_encode($columns);
        if ($result === false) {
            if (function_exists('json_last_error_msg')) {
                // since PHP 5.5
                $errorMessage = json_last_error_msg();
            } else {
                $lastError = json_last_error();
                $constants = get_defined_constants(true);
                $errorMessage = 'Unknown error';
                foreach ($constants['json'] as $constant => $value) {
                    if ($value === $lastError && substr($constant, 0, 11) === 'JSON_ERROR_') {
                        $errorMessage = $constant;
                        break;
                    }
                }
            }

            throw new IcingaException('Couldn\'t encode %s as JSON: %s', print_r($columns, true), $errorMessage);
        }
        return $result;
    }
}
