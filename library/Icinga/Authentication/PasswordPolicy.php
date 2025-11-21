<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Authentication;

interface PasswordPolicy
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
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Validate a given password against the defined policy
     *
     * @param string $password
     * @return string[] Returns an empty array if the password is valid,
     * otherwise returns error messages describing the violations
     */
    public function validate(string $password): array;
}
