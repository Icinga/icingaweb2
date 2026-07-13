<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use ipl\Html\ValidHtml;
use SensitiveParameter;

/**
 * Contract for password policy implementations
 *
 * A policy validates passwords and exposes metadata for the configuration
 * UI: a display name for selection, a machine-readable name for
 * persistence, and an optional description of its requirements.
 */
interface PasswordPolicy
{
    /**
     * Get the human-readable name of the password policy
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Get the machine-readable name of the password policy
     *
     * Used to identify the policy in configuration. Must be unique across all policies
     * provided by a module.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the password policy
     *
     * Shown in the form when the policy is active. Describe the requirements the policy
     * enforces so that users know what to enter before submitting.
     *
     * @return ?ValidHtml Null if the policy provides no description
     */
    public function getDescription(): ?ValidHtml;

    /**
     * Validate a password against the policy
     *
     * @param string $newPassword The new password to validate
     * @param ?string $oldPassword The current password, if available, for policies that
     *   verify the new password differs from the old one
     *
     * @return list<string> Empty list if valid. One message per violation describing why
     *   the password was rejected
     */
    public function validate(
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array;
}
