<?php

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
        return $this->renderBadge() . $this->createLink($menu);
    }

    /**
     * Render the badge
     *
     * @return string
     */
    protected function renderBadge()
    {
        if ($count = $this->getCount()) {
            return sprintf(
                '<div title="%s" class="badge-container"><span class="badge badge-%s">%s</span></div>',
                $this->getView()->escape($this->getTitle()),
                $this->getView()->escape($this->getState()),
                $count
            );
        }
        return '';
    }
}
