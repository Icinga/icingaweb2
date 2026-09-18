<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use SensitiveParameter;

/**
 * Policy to allow any password
 */
class AnyPasswordPolicy extends PasswordPolicyHook
{
    use Translation;

    /**
     * Get the human-readable name of the password policy
     *
     * Policy is named 'None' to indicate that no password policy is enforced and any password is accepted.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->translate('None');
    }

    public function getName(): string
    {
        return self::DEFAULT_PASSWORD_POLICY;
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
