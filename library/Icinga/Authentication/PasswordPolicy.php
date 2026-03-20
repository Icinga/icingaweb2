<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

interface PasswordPolicy
{
    /**
     * Get the name of the password policy
     *
     * Displayed when configuring a password policy.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the password policy
     *
     * Displayed when creating or changing passwords while the policy is active.
     * Should contain the rules of the policy.
     *
     * @return ?string
     */
    public function getDescription(): ?string;

    /**
     * Validate a given password against the defined policy
     *
     * @param string $newPassword
     * @param ?string $oldPassword
     * 
     * @return string[] Returns an empty array if the password is valid,
     *   otherwise returns error messages describing the violations
     */
    public function validate(string $newPassword, ?string $oldPassword = null): array;
}
