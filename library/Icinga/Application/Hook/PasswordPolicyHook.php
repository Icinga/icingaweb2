<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Authentication\PasswordPolicy;

/**
 * This class connects with the PasswordPolicy interface to use it as hook
 */
abstract class PasswordPolicyHook implements PasswordPolicy
{
    /**
    * Registers Hook as Password Policy
    *
    * @return void
    */
    public static function register(): void
    {
        Hook::register('PasswordPolicy', static::class, static::class);
    }

    /**
    * Returns all registered password policies sorted by
    *
    * @return array
    */
    public static function all(): array
    {
        $passwordPolicies = [];

        foreach (Hook::all('PasswordPolicy') as $class => $policy) {
            $passwordPolicies[$class] = $policy->getName();
        }

        asort($passwordPolicies);
        return $passwordPolicies;
    }
}
