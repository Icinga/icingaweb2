<?php

namespace Icinga\Authentication;

interface PasswordPolicyInterface
{
    public function displayPasswordPolicy(): string;
    public function validatePassword(string $password): bool;
    public function getPolicyViolation(string $password): string;
}
