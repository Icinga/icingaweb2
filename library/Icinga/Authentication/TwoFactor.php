<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Web\Compat\CompatForm;

interface TwoFactor
{
    public function getName(): string;

    public function getDisplayName(): string;

    public function isEnrolled(User $user): bool;

    /**
     * Enroll a user in a 2FA method
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function enroll(User $user): void;

    /**
     * Unenroll a user from a 2FA method
     *
     * @throws ConfigurationError If the database operation fails
     */
    public function removeEnrollment(User $user): void;

    public function verify(string $token): bool;

    public function enrollmentForm(): CompatForm;
}
