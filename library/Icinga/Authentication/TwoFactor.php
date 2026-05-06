<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Forms\Account\TwoFactorEnrollmentForm;
use Icinga\User;
use ipl\Html\FormElement\FieldsetElement;

/**
 * Contract for a two-factor authentication method
 */
interface TwoFactor
{
    /**
     * Get the unique machine-readable identifier for this 2FA method
     *
     * Used as a stable key to look up the implementation by name, e.g. via
     * {@see TwoFactorHook::fromName()}.
     *
     * @return string Lowercase identifier for this method, e.g. 'totp'. Must be globally
     *   unique across all registered implementations. A duplicate logs a warning and
     *   overwrites the earlier registration. Must be a valid HTML `name` attribute value,
     *   because it's used as the fieldset name in the enrollment form.
     */
    public function getName(): string;

    /**
     * Get the human-readable name for this 2FA method shown in the UI
     *
     * @return string Human-readable name for this method, e.g. 'TOTP'
     */
    public function getDisplayName(): string;

    /**
     * Check whether a user is enrolled in this 2FA method
     *
     * Implementations typically query the database for a stored credential (secret, key, ...).
     *
     * @param User $user The user to check for
     *
     * @return bool True if enrolled, false otherwise
     */
    public function isEnrolled(User $user): bool;

    /**
     * Verify a 2FA token provided by the user
     *
     * Called during login to verify the two-factor challenge against the credential
     * already stored for the user.
     *
     * @param User $user The user whose stored credential is checked
     * @param string $token The raw token string entered by the user, e.g. a 6-digit TOTP code
     *
     * @return bool True if the token is valid, false otherwise
     */
    public function verify(User $user, string $token): bool;

    /**
     * Verify the submitted credential and persist it for the given user
     *
     * Called from {@see TwoFactorEnrollmentForm::onSuccess()} when the enroll button
     * is pressed. Reads the method-specific values from $fieldset, verifies that the
     * credential works, and stores it on success.
     *
     * @param User $user The user to enroll
     * @param FieldsetElement $fieldset The method-specific fieldset containing the
     *   submitted credential elements
     *
     * @return bool True if the credential was verified and stored, false if verification
     *   failed. On failure, attach a user-visible error to the relevant element in
     *   $fieldset via {@see FieldsetElement::addMessage()}.
     */
    public function enroll(User $user, FieldsetElement $fieldset): bool;

    /**
     * Remove the stored credential for the given user
     *
     * Called from {@see TwoFactorEnrollmentForm::onSuccess()} when the unenroll button is pressed.
     *
     * @param User $user The user to unenroll
     *
     * @return void
     */
    public function unenroll(User $user): void;

    /**
     * Add the method-specific elements to the enrollment config fieldset
     *
     * Called from {@see TwoFactorEnrollmentForm::assemble()}.
     *
     * @param User $user The user being enrolled
     * @param FieldsetElement $fieldset The method-specific fieldset to add elements to.
     *   Implementations must not rename this element. Its name must equal {@see getName()}
     *   or a {@see LogicException} is thrown by {@see TwoFactorEnrollmentForm}.
     *
     * @return void
     */
    public function assembleEnrollmentFormElements(User $user, FieldsetElement $fieldset): void;
}
