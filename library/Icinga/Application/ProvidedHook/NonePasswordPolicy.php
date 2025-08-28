<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;

/**
 * None Password Policy to validate all passwords
 */
class NonePasswordPolicy implements PasswordPolicyHook
{
    public function getName(): string
    {
        return 'None';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function validatePassword(string $password): array
    {
        return [];
    }
}
