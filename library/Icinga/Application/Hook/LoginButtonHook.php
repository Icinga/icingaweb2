<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Hook\LoginButton\LoginButton;

abstract class LoginButtonHook
{
    /**
     * @return LoginButton[]
     */
    abstract public function getButtons(): array;

    /**
     * @return static[]
     */
    public static function all(): array
    {
        return Hook::all('LoginButton');
    }

    public static function register(): void
    {
        Hook::register('LoginButton', static::class, static::class, true);
    }
}
