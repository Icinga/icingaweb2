<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * Default implementation of a password policy
 *
 * Enforces:
 * - Minimum length of 12 characters
 * - At least one number
 * - At least one special character
 * - At least one uppercase letter
 * - At least one lowercase letter
 */
class DefaultPasswordPolicy implements PasswordPolicyHook
{
    use Translation;

    public function getName(): string
    {
        return $this->translate('Default');
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Password requirements: minimum 12 characters,' .
            'at least 1 number, 1 special character, uppercase and lowercase letters.'
        );
    }

    public function validatePassword(string $password): array
    {
        $violations = [];

        if (mb_strlen($password) < 12) {
            $violations[] = $this->translate(
                'Password must be at least 12 characters long'
            );
        }

        if (! preg_match('/[0-9]/', $password)) {
            $violations[] = $this->translate(
                'Password must contain at least one number'
            );
        }

        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $violations[] = $this->translate(
                'Password must contain at least one special character'
            );
        }

        if (! preg_match('/[A-Z]/', $password)) {
            $violations[] = $this->translate(
                'Password must contain at least one uppercase letter'
            );
        }

        if (! preg_match('/[a-z]/', $password)) {
            $violations[] = $this->translate(
                'Password must contain at least one lowercase letter'
            );
        }

        return $violations;
    }
}
