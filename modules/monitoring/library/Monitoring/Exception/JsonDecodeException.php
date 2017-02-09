<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Exception;

use Icinga\Exception\IcingaException;

/**
 * Exception thrown if {@link json_decode()} fails
 */
class JsonDecodeException extends IcingaException
{
    /**
     * JsonDecodeException constructor
     *
     * @param   string      $invalidJson    The JSON string caused this error
     * @param   int|null    $jsonError      Error code (from {@link json_last_error()}) or null for the last occurred error
     */
    public function __construct($invalidJson, $jsonError = null)
    {
        if ($jsonError === null) {
            $msg = version_compare(PHP_VERSION, '5.5.0', '>=')
                ? json_last_error_msg()
                : $this->errorCodeToMessage(json_last_error());
        } else {
            $msg = $this->errorCodeToMessage($jsonError);
        }

        parent::__construct('%s: %s', $msg, $invalidJson);
    }

    /**
     * Convert the given error code (from {@link json_last_error()}) to a human readable error message
     *
     * @param   int     $jsonError
     * @return  string
     */
    protected function errorCodeToMessage($jsonError)
    {
        switch ($jsonError) {
            case JSON_ERROR_DEPTH:
                return 'The maximum stack depth has been exceeded';
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Invalid or malformed JSON';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'An error occured when parsing a JSON string';
        }
    }
}
