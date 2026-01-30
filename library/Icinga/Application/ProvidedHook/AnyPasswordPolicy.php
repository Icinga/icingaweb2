<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * Policy to allow any password
 */
class AnyPasswordPolicy extends PasswordPolicyHook
{
    use Translation;

    public function getName(): string
    {
        // Policy is named 'None' to indicate that no password policy is enforced and any password is accepted
        return $this->translate('None');
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function validate(string $newPassword, ?string $oldPassword = null): array
    {
        return [];
    }
}
