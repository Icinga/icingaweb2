<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\Application\Hook\PasswordPolicyHook;
use Zend_Validate_Abstract;

class PasswordValidator extends Zend_Validate_Abstract
{
    /**
     * @var PasswordPolicyHook|null
     */
    private ?PasswordPolicyHook $passwordPolicyObject;

    /**
     * Constructor
     *
     * @param PasswordPolicyHook|null $passwordPolicyObject
     */
    public function __construct(?PasswordPolicyHook $passwordPolicyObject = null)
    {
        $this->passwordPolicyObject = $passwordPolicyObject;
    }

    /**
     * Checks if password matches with password policy
     * throws a message if not
     *
     * If no password policy is set, all passwords are considered valid
     *
     * @param mixed $value The password to validate
     *
     * @return bool
     *
     */
    public function isValid($value): bool
    {
        $this->_messages = [];

        if ($this->passwordPolicyObject === null) {
            return true;
        }

        $errorMessage = $this->passwordPolicyObject->validatePassword($value);

        if ($errorMessage !== null) {
            $this->_messages[] = $errorMessage;
            return false;
        }

        return true;
    }
}
