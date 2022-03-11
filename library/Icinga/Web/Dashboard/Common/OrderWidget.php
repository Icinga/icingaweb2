<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

trait OrderWidget
{
    /**
     * The priority order of this widget
     *
     * @var int
     */
    private $order = 0;

    /**
     * Set the priority order of this widget
     *
     * @param int $order
     *
     * @return $this
     */
    public function setPriority(int $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the priority order of this widget
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->order;
    }
}
