<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;
use Icinga\Data\ConfigObject;

/**
 * Summary badge adding up all badges in the sub-menus that have the same state
 */
class SummaryMenuItemRenderer extends BadgeMenuItemRenderer
{
    /**
     * Set of summarized problems from submenus
     *
     * @var array
     */
    protected $titles = array();

    /**
     * The amount of problems
     *
     * @var int
     */
    protected $count = 0;

    /**
     * The state that should be summarized
     *
     * @var string
     */
    protected $state;

    /**
     * The amount of problems
     */
    public function __construct(ConfigObject $configuration)
    {
        $this->state = $configuration->get('state', self::STATE_CRITICAL);
    }

    /**
     * Renders the html content of a single menu item and summarized sub-menus
     *
     * @param Menu $menu
     *
     * @return string
     */
    public function render(Menu $menu)
    {
        /** @var $submenu Menu */
        foreach ($menu->getSubMenus() as $submenu) {
            $renderer = $submenu->getRenderer();
            if ($renderer instanceof BadgeMenuItemRenderer) {
                if ($renderer->getState() === $this->state) {
                    $this->titles[] = $renderer->getTitle();
                    $this->count += $renderer->getCount();
                }
            }
        }
        return $this->renderBadge() . $this->createLink($menu);
    }

    /**
     * The amount of items to display in the badge
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Defines the color of the badge
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * The tooltip title
     *
     * @return string
     */
    public function getTitle()
    {
        return implode(', ', $this->titles);
    }
}
