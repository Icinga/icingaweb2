<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Logger;
use Icinga\Authentication\PasswordPolicy;
use RuntimeException;

/**
 * Base class for hookable password policies
 */
abstract class PasswordPolicyHook implements PasswordPolicy
{
    use HookEssentials {
        all as private essentialsAll;
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

    public static function all(): array
    {
        $policies = [];
        foreach (static::essentialsAll() as $policy) {
            if (! ($policy instanceof PasswordPolicy)) {
                Logger::warning(sprintf('Password policy %s is not an instance of PasswordPolicy', get_class($policy)));

                continue;
            }

            $policies[] = $policy;
        }

        usort($policies, fn (PasswordPolicy $a, PasswordPolicy $b) => $a->getDisplayName() <=> $b->getDisplayName());

        return $policies;
    }

    /**
     * Get the globally unique identifier for this password policy
     *
     * Combines the providing module's name with {@see getDisplayName()}, e.g. 'mymodule/password-policy',
     * so that two modules may register a method using the same {@see getDisplayName()} without
     * colliding. Falls back to the plain {@see getDisplayName()} for hook classes that are not
     * part of a module namespace.
     *
     * @return string
     */
    final public function getCanonicalName(): string
    {
        if ($module = $this->getModule()?->getName()) {
            return sprintf('%s/%s', $module, $this->getName());
        }

        return $this->getName();
    }

    /**
     * Get a password policy instance by its canonical name
     *
     * @param string $canonicalName The canonical name of the password policy
     *
     * @return static
     */
    final public static function fromCanonicalName(string $canonicalName): static
    {
        foreach (static::all() as $policy) {
            if ($policy->getCanonicalName() === $canonicalName) {
                return $policy;
            }
        }

        throw new RuntimeException("No password policy found for canonical name '$canonicalName'");
    }
}
