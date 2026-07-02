<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\I18n\Translation;

/**
 * Policy to allow any password
 */
class AnyPasswordPolicy extends PasswordPolicyHook
{
    use Translation;

    public function getDisplayName(): string
    {
        // Policy is named 'None' to indicate that no password policy is enforced and any password is accepted.
        return $this->translate('None');
    }

    public function getName(): string
    {
        return 'any';
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
