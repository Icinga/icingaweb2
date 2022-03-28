<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

/**
 * Sortable interface that allows to reorder the provided dashboard entry
 * and update the database accordingly
 */
interface Sortable
{
    /**
     * Insert the dashboard entry at the given position within this dashboard entries
     *
     * @param BaseDashboard $dashboard
     * @param $position
     * @param Sortable|null $origin
     *
     * @return $this
     */
    public function reorderWidget(BaseDashboard $dashboard, $position, Sortable $origin = null);
}
