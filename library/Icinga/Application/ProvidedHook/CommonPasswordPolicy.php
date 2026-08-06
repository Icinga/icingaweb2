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
            'Minimum 12 characters, at least 1 number, 1 special character, lowercase and uppercase letters.',
        ));
    }

    public function validate(
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
        ?User $user = null,
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

        return $violations;
    }
}
