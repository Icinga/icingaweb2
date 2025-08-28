<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * None Password Policy to validate all passwords
 */
class NonePasswordPolicy implements PasswordPolicyHook
{
    use Translation;
    public function getName(): string
    {
        return $this->translate('None');
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
