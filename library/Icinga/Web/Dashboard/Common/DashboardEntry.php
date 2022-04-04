<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use ipl\Web\Url;

/**
 * Represents a dashboard widget types
 */
interface DashboardEntry
{
    /**
     * Check whether this widget doesn't contain any dashboard entries
     *
     * @return bool
     */
    public function hasEntries();

    /**
     * Get a dashboard entry by the given name if exists
     *
     * @param string $name
     *
     * @return BaseDashboard
     */
    public function getEntry($name);

    /**
     * Get whether the given dashboard entry exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasEntry($name);

    /**
     * Get all dashboard entries of this widget
     *
     * @return BaseDashboard[]
     */
    public function getEntries();

    /**
     * Set dashboard entries of this widget
     *
     * @param BaseDashboard[] $entries
     *
     * @return $this
     */
    public function setEntries(array $entries);

    /**
     * Add a new dashboard entry to this widget
     *
     * @param BaseDashboard $dashboard
     *
     * @return $this
     */
    public function addEntry(BaseDashboard $dashboard);

    /**
     * Create and add a new entry to this widget
     *
     * @param string $name
     * @param ?string|Url $url
     *
     * @return $this
     */
    public function createEntry($name, $url = null);

    /**
     * Get an array with entry name=>title format
     *
     * @return string[]
     */
    public function getEntryKeyTitleArr();

    /**
     * Remove the given entry from this widget
     *
     * @param BaseDashboard|string $entry
     *
     * @return $this
     */
    public function removeEntry($entry);

    /**
     * Removes the given list of entries from this widget
     *
     * If there is no entries passed, all the available entries of this widget will be removed
     *
     * @param BaseDashboard[] $entries
     *
     * @return $this
     */
    public function removeEntries(array $entries = []);

    /**
     * Manage the given widget(s)
     *
     * Performs all kinds of database actions for the given widget(s) except the DELETE action. If you want to
     * move pane(s)|dashlet(s) from another to this widget you have to also provide the origin from which the
     * given entry(ies) originated
     *
     * @param BaseDashboard|BaseDashboard[] $entry The actual dashboard entry to be managed
     * @param ?BaseDashboard $origin The original widget from which the given entry originates
     * @param bool $manageRecursive Whether the given entry should be managed recursively e.g if the given entry
     *                              is a Pane type, all its dashlets can be managed recursively
     *
     * @return $this
     */
    public function manageEntry($entry, BaseDashboard $origin = null, $manageRecursive = false);

    /**
     * Load all the assigned entries to this widget
     *
     * @param ?string $name Name of the dashboard widget you want to load the dashboard entries for
     *
     * @return $this
     */
    public function loadDashboardEntries($name = '');

    /**
     * Reset the current position of the internal dashboard entries pointer
     *
     * @return false|BaseDashboard
     */
    public function rewindEntries();
}
