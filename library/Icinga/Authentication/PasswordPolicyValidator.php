<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

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

    public function isValid($value, mixed $context = null): bool
    {
        $oldPassword = null;

        if (is_array($context)) {
            $oldPasswordValue = $context[$this->oldPasswordElementName] ?? null;
            if ($oldPasswordValue !== null && $oldPasswordValue !== '') {
                $oldPassword = $oldPasswordValue;
            }
        }

        $message = $this->passwordPolicy->validate($value, $oldPassword);

        if (! empty($message)) {
            $this->_messages = $message;

            return false;
        }

        return true;
    }
}
