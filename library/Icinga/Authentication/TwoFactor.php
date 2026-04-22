<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Web\Compat\CompatForm;

interface TwoFactor
{
    /**
     * Get the unique machine-readable identifier for this 2FA method
     *
     * Used as a stable key to look up the implementation by name, e.g. via {@link TwoFactorHook::fromName()}.
     *
     * @return string A lowercase, identifier, e.g. 'totp'
     */
    public function getName(): string;

    /**
     * Get the human-readable name for this 2FA method shown in the UI
     *
     * @return string E.g. 'TOTP'
     */
    public function getDisplayName(): string;

    /**
     * Get whether a user is enrolled in this 2FA method
     *
     * Implementations typically query the database for a stored credential (secret, key, ...).
     * Returns false if the lookup fails, so callers can treat an unavailable backend
     * the same as "not yet enrolled".
     *
     * @param ?User $user The user to check for, if not set the currently authenticated user will be used
     *
     * @return bool
     */
    public function isEnrolled(?User $user = null): bool;

    /**
     * Enroll the currently authenticated user in this 2FA method
     *
     * Persists whatever credential was assembled during {@link assembleEnrollmentForm()} —
     * e.g. for TOTP this would be the shared secret. Should only be called after a successful
     * {@link verify()} so the credential is confirmed to work before it is stored. After a
     * successful call of this method, {@link isEnrolled()} must return true for the same user.
     *
     * @throws ConfigurationError If persisting the credential fails
     */
    public function enroll(): void;

    /**
     * Unenroll the currently authenticated user from this 2FA method
     *
     * Removes the stored credential from the backend. After a successful call of this method,
     * {@link isEnrolled()} must return false for the same user.
     *
     * @throws ConfigurationError If removing the credential fails
     */
    public function unenroll(): void;

    /**
     * Verify a 2FA token provided by the user
     *
     * Called both during login (to gate access) and during enrollment (to confirm
     * the credential works before {@link enroll()} is called).
     *
     * @param string $token The raw token string entered by the user, e.g. a 6-digit TOTP code
     *
     * @return bool true if the token is valid for the current user, false otherwise
     */
    public function verify(string $token): bool;

    /**
     * Add the method-specific form elements to the enrollment form
     *
     * Called from the enrollment base form's {@link CompatForm::assemble()} method.
     *
     * @param CompatForm $form The form to add elements to
     */
    public function assembleEnrollmentForm(CompatForm $form): void;

    /**
     * Handle a successful enrollment form submission
     *
     * Called from the enrollment base form's {@link CompatForm::onSuccess()} handler. Implementations
     * read the submitted values, call {@link enroll()} or {@link unenroll()} as appropriate, and set
     * a redirect URL on the form via {@link CompatForm::setRedirectUrl()}.
     *
     * @param CompatForm $form The successfully submitted form
     */
    public function onSuccessEnrollmentForm(CompatForm $form): void;
}
