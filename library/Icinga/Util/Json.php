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
        return static::encodeAndSanitize($value, $options, $depth, false);
    }

    /**
     * {@link json_encode()} wrapper, automatically sanitizes bad UTF-8
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    public static function sanitize($value, $options = 0, $depth = 512)
    {
        return static::encodeAndSanitize($value, $options, $depth, true);
    }

    /**
     * {@link json_encode()} wrapper, sanitizes bad UTF-8
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     * @param   bool    $autoSanitize   Automatically sanitize invalid UTF-8 (if any)
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    protected static function encodeAndSanitize($value, $options, $depth, $autoSanitize)
    {
        $encoded = json_encode($value, $options, $depth);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;

            /** @noinspection PhpMissingBreakStatementInspection */
            case JSON_ERROR_UTF8:
                if ($autoSanitize) {
                    return static::encode(static::sanitizeUtf8Recursive($value), $options, $depth);
                }
                // Fallthrough

            default:
                throw new JsonEncodeException('%s: %s', json_last_error_msg(), var_export($value, true));
        }
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
        $decoded = $json ? json_decode($json, $assoc, $depth, $options) : null;

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonDecodeException('%s: %s', json_last_error_msg(), var_export($json, true));
        }
        return $decoded;
    }

    /**
     * Replace bad byte sequences in UTF-8 strings inside the given JSON-encodable structure with question marks
     *
     * @param   mixed   $value
     *
     * @return  mixed
     */
    protected static function sanitizeUtf8Recursive($value)
    {
        switch (gettype($value)) {
            case 'string':
                return static::sanitizeUtf8String($value);

            case 'array':
                $sanitized = array();

                foreach ($value as $key => $val) {
                    if (is_string($key)) {
                        $key = static::sanitizeUtf8String($key);
                    }

                    $sanitized[$key] = static::sanitizeUtf8Recursive($val);
                }

                return $sanitized;

            case 'object':
                $sanitized = array();

                foreach ($value as $key => $val) {
                    if (is_string($key)) {
                        $key = static::sanitizeUtf8String($key);
                    }

                    $sanitized[$key] = static::sanitizeUtf8Recursive($val);
                }

                return (object) $sanitized;

            default:
                return $value;
        }
    }

    /**
     * Replace bad byte sequences in the given UTF-8 string with question marks
     *
     * @param   string  $string
     *
     * @return  string
     */
    protected static function sanitizeUtf8String($string)
    {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
}
