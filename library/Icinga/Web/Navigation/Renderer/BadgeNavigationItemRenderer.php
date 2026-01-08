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
     * @param   NavigationItem|null  $item
     *
     * @return  string
     */
    public function render(?NavigationItem $item = null)
    {
        if ($item === null) {
            $item = $this->getItem();
        }

        $cssClass = '';
        if ($item->getCssClass() !== null) {
            $cssClass = ' ' . $item->getCssClass();
        }

        $item->setCssClass('badge-nav-item' . $cssClass);
        $this->setEscapeLabel(false);
        $label = $this->view()->escape($item->getLabel());
        $item->setLabel($this->renderBadge() . $label);
        $html = parent::render($item);
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
            if ($count > 1000000) {
                $count = round($count, -6) / 1000000 . 'M';
            } elseif ($count > 1000) {
                $count = round($count, -3) / 1000 . 'k';
            }

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
