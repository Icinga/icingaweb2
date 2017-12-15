<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Exception\Json\JsonEncodeException;

/**
 * Wrap {@link json_encode()} and {@link json_decode()} with error handling
 */
class Json
{
    /**
     * {@link json_encode()} wrapper
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    public static function encode($value, $options = 0, $depth = 512)
    {
        if (version_compare(phpversion(), '5.5.0', '<')) {
            $encoded = json_encode($value, $options);
        } else {
            $encoded = json_encode($value, $options, $depth);
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonEncodeException('%s: %s', static::lastErrorMsg(), var_export($value, true));
        }
        return $encoded;
    }

    /**
     * {@link json_decode()} wrapper
     *
     * @param   string  $json
     * @param   bool    $assoc
     * @param   int     $depth
     * @param   int     $options
     *
     * @return  mixed
     * @throws  JsonDecodeException
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $decoded = json_decode($json, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonDecodeException('%s: %s', static::lastErrorMsg(), var_export($json, true));
        }
        return $decoded;
    }

    /**
     * {@link json_last_error_msg()} replacement for PHP < 5.5.0
     *
     * @return string
     */
    protected static function lastErrorMsg()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return json_last_error_msg();
        }

        // All possible error codes before PHP 5.5.0 (except JSON_ERROR_NONE)
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'State mismatch (invalid or malformed JSON)';
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}
