<?php

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Web\Request;
use Throwable;

abstract class RequestHook
{
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

    /**
     * Get all registered implementations
     *
     * @return static[]
     */
    public static function all(): array
    {
        return Hook::all('RequestHook');
    }

    /**
     * Register the class as a RequestHook implementation
     *
     * Call this method on your implementation during module initialization to make Icinga Web aware of your hook.
     */
    public static function register(): void
    {
        Hook::register('RequestHook', static::class, static::class, true);
    }
}
