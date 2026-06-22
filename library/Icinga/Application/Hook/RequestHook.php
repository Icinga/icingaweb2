<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Logger;
use Icinga\Web\Request;
use Throwable;

abstract class RequestHook
{
    use HookEssentials;

    /**
     * Always runs without a permission check
     *
     * Request hooks are system-level concerns that fire on every request as
     * part of the framework dispatch cycle, independent of user permissions.
     *
     * @return bool
     */
    protected static function isAlwaysRun(): bool
    {
        return true;
    }

    final protected static function getHookName(): string
    {
        return 'RequestHook';
    }

    /**
     * Triggered after a request has been dispatched
     *
     * @param Request $request
     *
     * @return void
     */
    abstract public function onPostDispatch(Request $request): void;

    /**
     * Call the onPostDispatch() method of all registered RequestHooks
     *
     * @param Request $request
     *
     * @return void
     */
    final public static function postDispatch(Request $request): void
    {
        foreach (static::all() as $hook) {
            try {
                $hook->onPostDispatch($request);
            } catch (Throwable $e) {
                Logger::error('Failed to execute hook on request: %s', $e);
            }
        }
    }
}
