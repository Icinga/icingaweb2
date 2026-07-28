<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Repository;

use Icinga\Data\Extensible;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;

/**
 * Operating mode for a {@see \Icinga\Web\Form\RepositoryForm}
 */
enum RepositoryMode
{
    /** Create a new repository entry */
    case Insert;

    /** Modify an existing repository entry */
    case Update;

    /** Remove an existing repository entry */
    case Delete;

    /**
     * Get the interface required for the respective mode
     *
     * @return class-string
     */
    public function getRequiredInterface(): string
    {
        return match ($this) {
            RepositoryMode::Insert => Extensible::class,
            RepositoryMode::Update => Updatable::class,
            RepositoryMode::Delete => Reducible::class,
        };
    }

    /**
     * Check whether the mode requires an identifier
     *
     * @return bool
     */
    public function requiresIdentifier(): bool
    {
        return match ($this) {
            RepositoryMode::Insert => false,
            RepositoryMode::Update,
            RepositoryMode::Delete => true,
        };
    }
}
