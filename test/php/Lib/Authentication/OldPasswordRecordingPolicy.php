<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Lib\Authentication;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\Html\ValidHtml;
use SensitiveParameter;

/**
 * A policy that accepts every password and records the old password it was given
 */
class OldPasswordRecordingPolicy extends PasswordPolicyHook
{
    /** @var ?string The old password passed to the last validation */
    public static ?string $oldPassword = null;

    public function getDisplayName(): string
    {
        return 'Old password recording';
    }

    public function getName(): string
    {
        return 'old-password-recording';
    }

    public function getDescription(): ?ValidHtml
    {
        return null;
    }

    public function validate(
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array {
        static::$oldPassword = $oldPassword;

        return [];
    }
}
