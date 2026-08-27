<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Web\Url;

/**
 * Offer a further destination for the current view in the "Add To Dashboard" dropdown
 *
 * Icinga Web's own dashboards are one place a view can be sent to; a module that implements dashboards of its
 * own is another, and until now had no way to say so — {@see \Icinga\Web\Widget\Tabextension\DashboardAction}
 * named a single hardcoded route, and `Tabextension` has no registry a module could add itself to.
 *
 * What every destination has in common is the parameter: the current view's own relative URL, carrying
 * whatever its controls have applied — filter, sort, limit, page, view mode. That is added by the tabextension
 * and is not the hook's to build, so a destination cannot accidentally receive a different view than the one
 * the user is looking at.
 *
 * Filtered by the providing module's permission like every hook, so a destination in a module the viewer may
 * not use is never offered.
 */
abstract class DashboardActionHook
{
    use HookEssentials;

    final protected static function getHookName(): string
    {
        return 'DashboardAction';
    }

    /**
     * Get the label of the dropdown entry, translated by the providing module
     *
     * It sits beside Icinga Web's own "Add To Dashboard", so it has to name its destination rather than the
     * action — two entries differing only in punctuation are two entries nobody can choose between.
     */
    abstract public function getLabel(): string;

    /**
     * Get the route the view's URL is handed to
     *
     * Receives it as the `url` parameter, rawurlencoded, exactly as
     * {@see \Icinga\Controllers\DashboardController::newDashletAction()} reads it — so a module that already
     * accepts Icinga Web's own hand-off needs nothing new to accept this one.
     */
    abstract public function getUrl(): Url;

    /**
     * Get the entry's icon
     */
    public function getIcon(): string
    {
        return 'dashboard';
    }

    /**
     * Get whether the destination is shown in a modal rather than as a page of its own
     *
     * Declared rather than handed over as attributes, so which attributes make a modal stays Icinga Web's to
     * decide and a destination cannot put arbitrary markup on a link in somebody else's tab bar.
     *
     * A destination that only asks which dashboard to use wants this: it is one question, and answering it in
     * a modal leaves the view the user came from on screen behind it.
     */
    public function opensInModal(): bool
    {
        return false;
    }
}
