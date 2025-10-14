<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * Policy to allow any password
 */
class AnyPasswordPolicy implements PasswordPolicyHook
{
    use Translation;

    /**
     *  Policy named 'none' to indicate that no password policy is enforced and any password is accepted
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->translate('None');
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function validatePassword(string $password): array
    {
        return [];
    }
}
