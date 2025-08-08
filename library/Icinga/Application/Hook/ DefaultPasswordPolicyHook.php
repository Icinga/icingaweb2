<?php

namespace Icinga\Application\Hook;

use Icinga\Web\Hook;

abstract class DefaultPasswordPolicyHook
{

abstract public function displayPasswordPolicy(): string;

abstract public function validatePassword(string $password): bool;

abstract public function getPolicyViolation(string $password): string;


}
