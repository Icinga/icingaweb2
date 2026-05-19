<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Navigation;

use Icinga\Web\Url;

/**
 * A dashboard pane
 */
class DashboardPane extends NavigationItem
{
    /**
     * This pane's dashlets
     *
     * @var array
     */
    protected $dashlets;

    protected $disabled;

    /**
     * Set this pane's dashlets
     *
     * @param   array   $dashlets
     *
     * @return  $this
     */
    public function setDashlets(array $dashlets)
    {
        $this->dashlets = $dashlets;
        return $this;
    }

    /**
     * Return this pane's dashlets
     *
     * @param   bool    $ordered    Whether to order the dashlets first
     *
     * @return  array
     */
    public function getDashlets($ordered = true)
    {
        if ($this->dashlets === null) {
            return [];
        }

        if ($ordered) {
            $dashlets = $this->dashlets;
            ksort($dashlets);
            return $dashlets;
        }

        return $this->dashlets;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setUrl(Url::fromPath('dashboard', ['pane' => $this->getName()]));
    }

    /**
     * Set disabled state for pane
     *
     * @param bool $disabled
     */
    public function setDisabled($disabled = true)
    {
        $this->disabled = (bool) $disabled;
    }

    /**
     * Get disabled state for pane
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
}
