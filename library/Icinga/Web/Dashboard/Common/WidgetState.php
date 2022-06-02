<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

trait WidgetState
{
    /**
     * A flag whether this widget is currently being loaded
     *
     * @var bool
     */
    protected $active = false;

    /**
     * A flag whether this widget has been disabled (affects only default home)
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Set whether this widget should be disabled
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get whether this widget has been disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Set whether this widget is currently being loaded
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive(bool $active = true): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get whether this widget is currently being loaded
     *
     * This indicates which dashboard tab is currently open if this widget type is a Dashboard Pane
     * or whether the Dashboard Home in the navigation bar is active/focused
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
