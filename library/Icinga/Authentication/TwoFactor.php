<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Exception\ConfigurationError;
use Icinga\User;
use ipl\Stdlib\Contract\Validator;
use ipl\Web\Compat\CompatForm;

interface TwoFactor
{
    /** @var string Name of the token text input in the verification form */
    const TOKEN_INPUT = '2fa_token';

    /** @var string Name of the verify submit button in the verification form */
    const SUBMIT_VERIFY_2FA = 'btn_submit_verify_2fa';

    /** @var string Name of the cancel submit button in the verification form */
    const SUBMIT_CANCEL_2FA = 'btn_submit_cancel_2fa';

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
     * If $user is null, the currently authenticated user will be used. Implementations typically
     * query the database for a stored credential (secret, key, ...). Returns false if the lookup
     * fails, so callers can treat an unavailable backend the same as "not yet enrolled".
     *
     * @param ?User $user The user to check for
     *
     * @return bool
     */
    public function isEnrolled(?User $user = null): bool;

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
     * Verify the submitted credential and persist it for the currently authenticated user
     *
     * Called from the enrollment form's {@link CompatForm::onSuccess()} handler when the enroll
     * button is pressed. Read the method-specific values from $form, verify that the credential
     * works, and store it on success.
     *
     * @param CompatForm $form The successfully submitted enrollment form
     *
     * @return bool true if the credential was verified and stored, false if verification failed
     *
     * @throws ConfigurationError If the credential cannot be persisted
     */
    public function enroll(CompatForm $form): bool;

    /**
     * Remove the stored credential for the currently authenticated user
     *
     * Called from the enrollment form's {@link CompatForm::onSuccess()} handler when the unenroll
     * button is pressed. After this call {@link isEnrolled()} will return false for the same user.
     *
     * @throws ConfigurationError If the credential cannot be removed
     */
    public function unenroll(): void;

    /**
     * Add the method-specific form elements to the enrollment form
     *
     * Called from the enrollment base form's {@link CompatForm::assemble()} method.
     *
     * @param CompatForm $form The form to add elements to
     */
    public function assembleEnrollmentFormElements(CompatForm $form): void;

    /**
     * Add the method-specific form elements to the login form
     *
     * Called from the login form's assemble method when a 2FA challenge is required.
     * Implementations add the token input using {@link TOKEN_INPUT} as its name and
     * the verify submit button using {@link SUBMIT_VERIFY_2FA} as its name. To cancel
     * the verification process a cancel button using {@link SUBMIT_CANCEL_2FA} as its
     * name should be created.
     *
     * @param CompatForm $form The form to add elements to
     */
    public function assembleVerificationForm(CompatForm $form): void;
}
