<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

interface OverridingWidget
{
    /**
     * Set whether this widget overrides another widget
     *
     * @param  bool $override
     *
     * @return $this
     */
    public function override(bool $override);

    /**
     * Get whether this widget overrides another widget
     *
     * @return bool
     */
    public function isOverriding();
}
