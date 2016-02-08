<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

abstract class BadgeMenuItemRenderer extends MenuItemRenderer
{
    const STATE_OK       = 'ok';
    const STATE_CRITICAL = 'critical';
    const STATE_WARNING  = 'warning';
    const STATE_PENDING  = 'pending';
    const STATE_UNKNOWN  = 'unknown';

    /**
     * Defines the color of the badge
     *
     * @return string
     */
    abstract public function getState();

    /**
     * The amount of items to display in the badge
     *
     * @return int
     */
    abstract public function getCount();

    /**
     * The tooltip title
     *
     * @return string
     */
    abstract public function getTitle();

    /**
     * Renders the html content of a single menu item
     *
     * @param Menu $menu
     *
     * @return string
     */
    public function render(Menu $menu)
    {
        return '<div class="clearfix">' . $this->renderBadge() . $this->createLink($menu) . '</div>';
    }

    /**
     * Render the badge
     *
     * @return string
     */
    protected function renderBadge()
    {
        if ($count = $this->getCount()) {
            $view = $this->getView();
            return sprintf(
                '<span title="%s" class="badge pull-right state-%s">%s</span>',
                $view->escape($this->getTitle()),
                $view->escape($this->getState()),
                $count
            );
        }
        return '';
    }
}
