<?php

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;

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
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Default Password Policy';
    }

    /**
     * @inheritdoc
     */
    public function displayPasswordPolicy(): string
    {
        $message = (
            'Password requirements: ' . 'minimum 12 characters, ' .
            'at least 1 number, ' .
            '1 special character, ' . 'uppercase and lowercase letters'
        );
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function validatePassword(string $password): ?string
    {
        $violations = [];

        if (strlen($password) < 12) {
            $violations[] = ('Password must be at least 12 characters long');
        }

        if (! preg_match('/[0-9]/', $password)) {
            $violations[] = ('Password must contain at least one number');
        }

        if (! preg_match('/[^a-zA-Z0-9]/', $password)) {
            $violations[] = ('Password must contain at least one special character');
        }

        if (! preg_match('/[A-Z]/', $password)) {
            $violations[] = ('Password must contain at least one uppercase letter');
        }

        if (! preg_match('/[a-z]/', $password)) {
            $violations[] = ('Password must contain at least one lowercase letter');
        }

        if (! empty($violations)) {
            return implode(", ", $violations);
        }

        return null;
    }
}
