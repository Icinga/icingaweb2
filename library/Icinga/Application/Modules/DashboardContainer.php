<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Modules;

/**
 * Container for module dashboards
 */
class DashboardContainer extends NavigationItemContainer
{
    /**
     * This dashboard's dashlets
     *
     * @var array
     */
    protected $dashlets = [];

    /**
     * Set this dashboard's dashlets
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
     * Return this dashboard's dashlets
     *
     * @return  array
     */
    public function getDashlets()
    {
        uasort($this->dashlets, function (array $x, array $y) {
            return $x['priority'] - $y['priority'];
        });

        return $this->dashlets;
    }

    /**
     * Add a new dashlet
     *
     * @param   string  $name
     * @param   string  $url
     * @param   int     $priority
     *
     * @return  $this
     */
    public function add($name, $url, $priority = null)
    {
        $this->dashlets[$name] = [
            'url'       => $url,
            'priority'  => $priority
        ];
        return $this;
    }
}
