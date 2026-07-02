<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Loader;

use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Reason\StaticCspReason;
use Icinga\User;
use ipl\Web\Common\Csp;

/**
 * Loads CSP directives from an array.
 * Useful for testing or providing user-defined CSP configuration.
 */
class ArrayCspLoader implements CspLoader
{
    /**
     * @param string $name The name to display for CSP reason
     * @param array $directives The CSP directives to load. Each key is a
     *   directive name, and each value is an array of values for that directive.
     */
    public function __construct(
        protected string $name,
        protected array $directives,
    ) {
    }

    /**
     * Load the CSP directives for a specific user
     *
     * Since the array loader loads the CSP directives from the given array of directives,
     * this method just returns the same result for all users {@see loadForAllUsers()}
     *
     * @param User $user
     *
     * @return AttributedCsp[]
     */
    public function loadForUser(User $user): array
    {
        return $this->loadForAllUsers();
    }

    public function loadForAllUsers(): array
    {
        $csp = new Csp();
        foreach ($this->directives as $directive => $values) {
            $csp->add($directive, $values);
        }

        return [new AttributedCsp($csp, new StaticCspReason($this->name))];
    }
}
