<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\NavigationItem;

/**
 * Abstract base class for a NavigationItem with a status badge
 */
abstract class BadgeNavigationItemRenderer extends NavigationItemRenderer
{
    const STATE_OK = 'ok';
    const STATE_CRITICAL = 'critical';
    const STATE_WARNING = 'warning';
    const STATE_PENDING = 'pending';
    const STATE_UNKNOWN = 'unknown';

    /**
     * The tooltip text for the badge
     *
     * @var string
     */
    protected $title;

    /**
     * The state identifier being used
     *
     * The state identifier defines the background color of the badge.
     *
     * @var string
     */
    protected $state;

    /**
     * Set the tooltip text for the badge
     *
     * @param   string  $title
     *
     * @return  $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Return the tooltip text for the badge
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the state identifier to use
     *
     * @param   string  $state
     *
     * @return  $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Return the state identifier to use
     *
     * @return  string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Return the amount of items represented by the badge
     *
     * @return  int
     */
    abstract public function getCount();

    /**
     * Render the given navigation item as HTML anchor with a badge
     *
     * @param   NavigationItem  $item
     *
     * @return  string
     */
    public function render(NavigationItem $item = null)
    {
        if ($item === null) {
            $item = $this->getItem();
        }
        $item->setCssClass('badge-nav-item');
        $this->setEscapeLabel(false);
        $label = $this->view()->escape($item->getLabel());
        if (($icon = $item->getIcon()) !== null) {
            $label = $this->view()->icon($icon) . $label;
            $item->setIcon(null);
        }
        $item->setLabel($this->renderBadge() . $label);
        $html = parent::render($item);
        if ($icon) {
            $item->setIcon(true);
        }
        return $html;
    }

    /**
     * Render the badge
     *
     * @return  string
     */
    protected function renderBadge()
    {
        if ($count = $this->getCount()) {
            $view = $this->view();
            return sprintf(
                '<span title="%s" class="badge state-%s">%s</span>',
                $view->escape($this->getTitle()),
                $view->escape($this->getState()),
                $count
            );
        }

        return '';
    }
}
