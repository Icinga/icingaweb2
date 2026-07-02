<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Loader;

use Icinga\Application\ClassLoader;
use Icinga\Application\Hook\CspHook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Reason\ModuleCspReason;
use RuntimeException;
use Icinga\User;
use Throwable;

/**
 * Loads CSP directives from modules. Modules can implement the {@see CspHook}
 * interface to provide custom CSP directives. The hook is called for each
 * request, allowing modules to dynamically add or modify CSP policies.
 */
class ModuleCspLoader implements CspLoader
{
    public function loadForAllUsers(): array
    {
        return $this->loadWith(fn($hook) => $hook->getCspForAllUsers());
    }

    public function loadForUser(User $user): array
    {
        return $this->loadWith(fn($hook) => $hook->getCspForUser($user));
    }

    /**
     * Load CSP directives from modules using the given fetch function
     *
     * @param callable $fetch A function that takes a CspHook instance and returns a Csp
     *
     * @return AttributedCsp[]
     */
    protected function loadWith(callable $fetch): array
    {
        $result = [];

        foreach (CspHook::all() as $hook) {
            try {
                $csp = $fetch($hook);
                if ($csp->isEmpty()) {
                    continue;
                }

                $moduleName = ClassLoader::extractModuleName(get_class($hook));
                if ($csp->hasDirective('default-src')) {
                    throw new RuntimeException(
                        sprintf("Setting 'default-src' is not allowed. Module: %s", $moduleName),
                    );
                }

                $result[] = new AttributedCsp($csp, new ModuleCspReason($moduleName));
            } catch (Throwable $e) {
                Logger::warning(
                    "Failed to invoke CSP hook %s: %s\n%s",
                    get_class($hook),
                    $e->getMessage(),
                    IcingaException::getConfidentialTraceAsString($e),
                );
            }
        }

        return $result;
    }
}
