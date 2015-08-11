<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

class ProblemMenuItemRenderer extends MenuItemRenderer
{
    /**
     * Set of summarized problems from submenus
     *
     * @var array
     */
    protected $summary = array();

    /**
     * Renders the html content of a single menu item and summarizes submenu problems
     *
     * @param Menu $menu
     *
     * @return string
     */
    public function render(Menu $menu)
    {
        if ($menu->getParent() !== null && $menu->hasSubMenus()) {
            /** @var $submenu Menu */
            foreach ($menu->getSubMenus() as $submenu) {
                $renderer = $submenu->getRenderer();
                if (method_exists($renderer, 'getSummary')) {
                    if ($renderer->getSummary() !== null) {
                        $this->summary[] =  $renderer->getSummary();
                    }
                }
            }
        }
        return $this->getBadge() . $this->createLink($menu);
    }

    /**
     * Get the problem badge
     *
     * @return string
     */
    protected function getBadge()
    {
        if (count($this->summary) > 0) {
            $problems = 0;
            $titles = array();

            foreach ($this->summary as $summary) {
                $problems += $summary['problems'];
                $titles[] = $summary['title'];
            }

            return sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-critical">%s</span></div>',
                implode(', ', $titles),
                $problems
            );
        }
        return '';
    }
}
