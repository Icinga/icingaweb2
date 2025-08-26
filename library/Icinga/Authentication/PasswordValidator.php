<?php

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Zend_Validate_Abstract;

class PasswordValidator extends Zend_Validate_Abstract
{
    /**
     * Checks if password matches with password policy
     * throws a message if not
     *
     * If no password policy is configured, all passwords are considered valid
     *
     * @param mixed $value The password to validate
     *
     * @return bool
     *
     */
    public function isValid($value): bool
    {
        $this->_messages = [];
        $passwordPolicy = Config::app()
            ->get('global', 'password_policy');

        if (! isset($passwordPolicy) || ! class_exists($passwordPolicy)) {
            return true;
        }

        $passwordPolicyObject = new $passwordPolicy();
        $errorMessage = $passwordPolicyObject->validatePassword($value);

        if ($errorMessage != null) {
            $this->_messages[] = $errorMessage;
            return false;
        }

        return true;
    }
}
