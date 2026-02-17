<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Logger;

/**
 * Application state hook base class
 */
abstract class ApplicationStateHook
{
    use HookEssentials;

    const ERROR = 'error';

    private $messages = [];

    final protected static function getHookName(): string
    {
        return 'ApplicationState';
    }

    final public function hasMessages()
    {
        return ! empty($this->messages);
    }

    final public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Add an error message
     *
     * The timestamp of the message is used for deduplication and thus must refer to the time when the error first
     * occurred. Don't use {@link time()} here!
     *
     * @param   string  $id         ID of the message. The ID must be prefixed with the module name
     * @param   int     $timestamp  Timestamp when the error first occurred
     * @param   string  $message    Error message
     *
     * @return  $this
     */
    final public function addError($id, $timestamp, $message)
    {
        $id = trim($id);
        $timestamp = (int) $timestamp;

        if (! strlen($id)) {
            throw new \InvalidArgumentException('ID expected.');
        }

        if (! $timestamp) {
            throw new \InvalidArgumentException('Timestamp expected.');
        }

        $this->messages[sha1($id . $timestamp)] = [self::ERROR, $timestamp, $message];

        return $this;
    }

    /**
     * Override this method in order to provide application state messages
     */
    abstract public function collectMessages();

    final public static function getAllMessages()
    {
        $messages = [];

        if (! static::isRegistered()) {
            return $messages;
        }

        foreach (static::all() as $hook) {
            try {
                $hook->collectMessages();
            } catch (\Exception $e) {
                Logger::error(
                    "Failed to collect messages from hook '%s'. An error occurred: %s",
                    get_class($hook),
                    $e
                );
            }

            if ($hook->hasMessages()) {
                $messages += $hook->getMessages();
            }
        }

        return $messages;
    }
}
