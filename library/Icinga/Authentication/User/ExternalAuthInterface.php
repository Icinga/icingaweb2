<?php
/* Icinga Web 2 | (c) 2019 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\User;

/**
 * Test login with external authentication mechanism, e.g. Apache
 */
interface ExternalAuthInterface extends UserBackendInterface
{
    public function authenticateExternal(User $user);

    public function authenticate(User $user, $password = null);
}
