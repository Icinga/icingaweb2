<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

interface PasswordPolicyHook
{
    /**
     * Get the name of the password policy
     *
     * @return string
     */
    public function getName(): string;

/**
     * Displays the rules of the password policy for users
     *
     * @return string
     */
    public function displayPasswordPolicy(): string;

    /**
     * Validate a given password against the defined policy
     *
     * @param string $password
     * @return string|null Returns null if the password is valid,
     * otherwise returns an error message describing the violations
     */
    public function validatePassword(string $password): ?string;
}
