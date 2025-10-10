<?php

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Web\Request;

abstract class RequestHook extends Hook
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
        foreach (static::all('Request') as $hook) {
            $hook->onPostDispatch($request);
        }
    }
}
