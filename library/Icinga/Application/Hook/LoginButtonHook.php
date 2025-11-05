<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook\LoginButton\LoginButton;

interface LoginButtonHook
{
    /**
     * @return LoginButton[]
     */
    public function getButtons(): array;
}
