<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use ipl\Stdlib\Str;
use Zend_Validate_Abstract;

/**
 * Validate passwords against a configured policy
 *
 * Optionally, retrieve the old password from the form context using the configured form element name
 * and pass it to the policy for validation.
 * Delegate all validation logic to the policy instance and expose any returned violation messages.
 */
class PasswordPolicyValidator extends Zend_Validate_Abstract
{
    /** @var PasswordPolicy Policy to use for validation */
    protected PasswordPolicy $passwordPolicy;

    /** @var ?string Name of the old password form element */
    protected ?string $oldPasswordElementName;

    /**
     * Create a new PasswordPolicyValidator
     *
     * @param PasswordPolicy $passwordPolicy
     * @param ?string $oldPasswordElementName
     */
    public function __construct(PasswordPolicy $passwordPolicy, ?string $oldPasswordElementName = null)
    {
        $this->passwordPolicy = $passwordPolicy;
        $this->oldPasswordElementName = $oldPasswordElementName;
    }

    /**
     * Check whether the given password satisfies the configured policy
     *
     * If $context is an array and an old-password element name is configured, extracts
     * the old password from the context and passes it to the policy. Violation messages
     * returned by the policy are stored in $this->_messages.
     *
     * @param mixed $value The new password to validate
     * @param mixed $context Form data array used to look up the old password
     *
     * @return bool True if the password satisfies the policy, false otherwise
     */
    public function isValid(mixed $value, mixed $context = null): bool
    {
        $oldPassword = null;
        if (is_array($context)) {
            $oldPasswordValue = $context[$this->oldPasswordElementName] ?? null;
            if (! Str::isEmpty($oldPasswordValue)) {
                $oldPassword = $oldPasswordValue;
            }
        }

        $message = $this->passwordPolicy->validate($value, $oldPassword);
        if (empty($message)) {
            return true;
        }

        $this->_messages = $message;

        return false;
    }
}
