<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            return array();
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
        $this->setUrl(Url::fromPath('dashboard', array('pane' => $this->getName())));
    }

    /**
     * {@inheritdoc}
     */
    public function merge(NavigationItem $item)
    {
        parent::merge($item);

        $this->setDashlets(array_merge(
            $this->getDashlets(false),
            $item->getDashlets(false)
        ));
    }
}
