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
    use HookEssentials {
        HookEssentials::all as private hookEssentialsAll;
    }

    protected static function getHookName(): string
    {
        return 'PasswordPolicy';
    }

    /**
     * Get whether the hook always runs without a permission check
     *
     * Password policies are a system hook and should always run for every user
     * regardless of the user's permission to access the module.
     */
    protected static function isAlwaysRun(): bool
    {
        return true;
    }

    /**
     * Return all registered password policies sorted by name
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $passwordPolicies = array_map(fn ($policy) => $policy->getName(), static::hookEssentialsAll());
        asort($passwordPolicies);

        return $passwordPolicies;
    }
}
