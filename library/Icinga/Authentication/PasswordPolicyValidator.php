<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use Zend_Validate_Abstract;

class PasswordPolicyValidator extends Zend_Validate_Abstract
{
    /**
     * The password policy object
     *
     * @var PasswordPolicy
     */
    protected PasswordPolicy $passwordPolicy;

    /**
     * Constructor
     *
     * @param PasswordPolicy $passwordPolicy
     */
    public function __construct(PasswordPolicy $passwordPolicy)
    {
        $this->passwordPolicy = $passwordPolicy;
    }

    /**
     * Checks if password matches with password policy
     * throws a message if not
     *
     * @param mixed $value The password to validate
     * @param mixed|null $context The data of the form
     * @return bool
     */
    public function isValid($value, mixed $context = null): bool
    {
        $oldPassword = null;

        if (is_array($context)) {
            $oldPasswordValue = $context['old_password'] ?? null;
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
