<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\User;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use SensitiveParameter;

/**
 * Implementation of a common password policy
 *
 * Enforces:
 * - Minimum length of 12 characters
 * - At least one number
 * - At least one special character
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - Not equal to, contained in, or containing the username
 */
class CommonPasswordPolicy extends PasswordPolicyHook
{
    use Translation;

    final public function getDisplayName(): string
    {
        return $this->translate('Common');
    }

    final public function getName(): string
    {
        return 'common';
    }

    public function getDescription(): ?ValidHtml
    {
        return new Text($this->translate(
            'Minimum 12 characters, at least 1 number, 1 special character, lowercase and uppercase letters,'
            . ' and must not be contained in, contain or match the username.',
        ));
    }

    public function validate(
        User $user,
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array {
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

        $username = mb_strtolower($user->getUsername());
        if ($username !== '') {
            $lowerPassword = mb_strtolower($newPassword);

            if ($username === $lowerPassword) {
                $violations[] = $this->translate('Username and password must not match');
            } else {
                if (str_contains($username, $lowerPassword)) {
                    $violations[] = $this->translate('Password must not be contained in username');
                }

                if (str_contains($lowerPassword, $username)) {
                    $violations[] = $this->translate('Password must not contain username');
                }
            }
        }

        return $violations;
    }
}
