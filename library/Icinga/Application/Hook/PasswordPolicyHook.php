<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Authentication\PasswordPolicy;

/**
 * Base class for hookable password policies
 */
abstract class PasswordPolicyHook implements PasswordPolicy
{
    /**
     * Hook name
     */
    protected const HOOK_NAME = 'PasswordPolicy';

    /**
     * Register password policy
     *
     * @return void
     */
    public static function register(): void
    {
        Hook::register(self::HOOK_NAME, static::class, static::class);
    }

    /**
     * Return all registered password policies sorted by name
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
