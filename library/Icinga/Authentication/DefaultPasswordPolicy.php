<?php

namespace Icinga\Authentication;

//use

use DateTime;
use ipl\Html\Text;
use ipl\I18n\Translation;
use Icinga\Authentication\PasswordPolicyInterface;


class DefaultPasswordPolicy implements PasswordPolicyInterface
{
    public function displayPasswordPolicy() : string
    {

        $message = (
            'Password requirements: ' . 'minimum 12 characters, ' .
            'at least 1 number, ' .
            '1 special character, ' . 'uppercase and lowercase letters'
        );
        return $message;
    }


    public function validatePassword(string $password): bool
    {
//passwort überprüfen auf policy, wenn alles passt dann true zurück, ansonsten false und in error meldungen gehen
        if (
            strlen($password) < 12
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/[0-9]/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            return false;
        }
        return true;
    }

    public function getPolicyViolation(string $password) : string
    {

        $violations = [];

        if (strlen($password) < 12) {
            $violations[] = ('Password must be at least 12 characters long');
        }
        if (!preg_match('/[0-9]/', $password)) {
            $violations[] = ('Password must contain at least one number');
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $violations[] =( 'Password must contain at least one special character');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $violations[] =('Password must contain at least one uppercase letter');
        }
        if (!preg_match('/[a-z]/', $password)) {
            $violations[] = ('Password must contain at least one lowercase letter');
        }
//
       $errorMessage = implode(", ", $violations);
        return $errorMessage;

    }

}
