<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Lib\Authentication;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\Html\ValidHtml;
use RuntimeException;
use SensitiveParameter;

/**
 * A policy that cannot be enumerated
 *
 * Enumerating all policies sorts them by display name, so throwing here is what
 * a policy provided by a broken module looks like to the configuration form.
 */
class BrokenPasswordPolicy extends PasswordPolicyHook
{
    public function getDisplayName(): string
    {
        throw new RuntimeException('Broken policy');
    }

    public function getName(): string
    {
        return 'broken';
    }

    public function getDescription(): ?ValidHtml
    {
        return null;
    }

    public function validate(
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array {
        return [];
    }
}
