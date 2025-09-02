<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\Application\Hook\PasswordPolicyHook;
use Zend_Validate_Abstract;

class PasswordValidator extends Zend_Validate_Abstract
{
    /**
     *  The password policy object
     *
     * @var PasswordPolicyHook
     */
    private PasswordPolicyHook $passwordPolicyObject;

    /**
     * Constructor
     *
     * @param PasswordPolicyHook $passwordPolicyObject
     */
    public function __construct(PasswordPolicyHook $passwordPolicyObject)
    {
        $this->passwordPolicyObject = $passwordPolicyObject;
    }

    /**
     * Checks if password matches with password policy
     * throws a message if not
     *
     * @param mixed $value The password to validate
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $message = $this->passwordPolicyObject->validatePassword($value);

        if (!empty($message)) {
            $this->_messages = $message;
            return false;
        }

        return true;
    }
}
