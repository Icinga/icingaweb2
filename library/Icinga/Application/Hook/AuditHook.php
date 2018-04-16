<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use Exception;
use InvalidArgumentException;
use Icinga\Application\Hook;
use Icinga\Application\Logger;

abstract class AuditHook
{
    /**
     * Log an activity to the audit log
     *
     * Propagates the given message details to all known hook implementations.
     *
     * @param   string  $type       An arbitrary name identifying the type of activity
     * @param   string  $message    A detailed description possibly referencing parameters in $data
     * @param   array   $data       Additional information (How this is stored or used is up to each implementation)
     */
    public static function logActivity($type, $message, array $data = null)
    {
        if (! Hook::has('audit')) {
            return;
        }

        foreach (Hook::all('audit') as $hook) {
            /** @var self $hook */
            try {
                $formattedMessage = $message;
                if ($data !== null) {
                    // Calling formatMessage on each hook is intended and allows
                    // intercepting message formatting while keeping it implicit
                    $formattedMessage = $hook->formatMessage($message, $data);
                }

                $hook->logMessage($type, $formattedMessage, $data);
            } catch (Exception $e) {
                Logger::error(
                    'Failed to propagate audit message to hook "%s". An error occurred: %s',
                    get_class($hook),
                    $e
                );
            }
        }
    }

    /**
     * Log a message to the audit log
     *
     * @param   string  $type       An arbitrary name identifying the type of activity
     * @param   string  $message    A detailed description of the activity
     * @param   array   $data       Additional activity information
     */
    abstract public function logMessage($type, $message, array $data = null);

    /**
     * Substitute the given message with its accompanying data
     *
     * @param   string  $message
     * @param   array   $messageData
     *
     * @return  string
     */
    public function formatMessage($message, array $messageData)
    {
        return preg_replace_callback('/{{(.+?)}}/', function ($match) use ($messageData) {
            return $this->extractMessageValue(explode('.', $match[1]), $messageData);
        }, $message);
    }

    /**
     * Extract the given value path from the given message data
     *
     * @param   array   $path
     * @param   array   $messageData
     *
     * @return  mixed
     *
     * @throws  InvalidArgumentException    In case of an invalid or missing format parameter
     */
    protected function extractMessageValue(array $path, array $messageData)
    {
        $key = array_shift($path);
        if (array_key_exists($key, $messageData)) {
            $value = $messageData[$key];
        } else {
            throw new InvalidArgumentException("Missing format parameter '$key'");
        }

        if (empty($path)) {
            if (! is_scalar($value)) {
                throw new InvalidArgumentException(
                    'Invalid format parameter. Expected scalar for path "' . join('.', $path) . '".'
                    . ' Got "' . gettype($value) . '" instead'
                );
            }

            return $value;
        } elseif (! is_array($value)) {
            throw new InvalidArgumentException(
                'Invalid format parameter. Expected array for path "'. join('.', $path) . '".'
                . ' Got "' . gettype($value) . '" instead'
            );
        }

        return $this->extractMessageValue($path, $value);
    }
}
