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
     * @return  array
     */
    public function getDashlets()
    {
        return $this->dashlets ?: array();
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
            $this->getDashlets(),
            $item->getDashlets()
        ));
    }
}
