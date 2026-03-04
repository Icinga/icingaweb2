<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use Zend_Validate_Abstract;

/**
 * Use the provided password policy to validate the new password.
 * Optionally, retrieve the old password from the form context using the configured form element name
 * and pass it to the policy for comparative validation.
 * Delegate all validation logic to the policy instance and expose any returned violation messages.
 */
class PasswordPolicyValidator extends Zend_Validate_Abstract
{
    /**
     * The password policy object
     *
     * @var PasswordPolicy
     */
    protected PasswordPolicy $passwordPolicy;

    /**
     * Name of the old password element
     *
     * @var string|null
     */
    protected ?string $oldPasswordElementName;

    /**
     * Constructor
     *
     * @param PasswordPolicy $passwordPolicy
     * @param string|null $oldPasswordElementName
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
