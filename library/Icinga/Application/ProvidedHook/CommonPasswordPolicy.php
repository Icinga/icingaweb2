<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * Common implementation of a password policy
 *
 * Enforces:
 * - Minimum length of 12 characters
 * - At least one number
 * - At least one special character
 * - At least one uppercase letter
 * - At least one lowercase letter
 */
class CommonPasswordPolicy extends PasswordPolicyHook
{
    use Translation;


    public function getName(): string
    {
        return $this->translate('Common');
    }

    public function getDescription(): ?string
    {
        return $this->translate(
            'Password requirements: minimum 12 characters, ' .
            'at least 1 number, 1 special character, uppercase and lowercase letters.'
        );
    }

    public function validate(string $newPassword, ?string $oldPassword): array
    {
        $violations = [];

        if (mb_strlen($newPassword) < 12) {
            $violations[] = $this->translate('Password must be at least 12 characters long');
        }

        if (! preg_match('/[0-9]/', $newPassword)) {
            $violations[] = $this->translate('Password must contain at least one number');
        }

        if (! preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $violations[] = $this->translate('Password must contain at least one special character');
        }

        if (! preg_match('/[A-Z]/', $newPassword)) {
            $violations[] = $this->translate('Password must contain at least one uppercase letter');
        }

        if (! preg_match('/[a-z]/', $newPassword)) {
            $violations[] = $this->translate('Password must contain at least one lowercase letter');
        }

        return $violations;
    }
}
