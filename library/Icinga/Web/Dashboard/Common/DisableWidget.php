<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

trait DisableWidget
{
    /**
     * A flag whether this widget is disabled
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * Set whether this widget should be disabled
     *
     * @param bool $disable
     */
    public function disable(bool $disable = true)
    {
        $this->disabled = $disable;

        return $this;
    }

    /**
     * Get whether this widget is disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }
}
