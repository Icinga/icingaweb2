<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Application\Icinga;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\View;

/**
 * NavigationItemRenderer
 */
class NavigationItemRenderer
{
    /**
     * View
     *
     * @var View
     */
    protected $view;

    /**
     * Return the view
     *
     * @return  View
     */
    public function view()
    {
        if ($this->view === null) {
            $this->setView(Icinga::app()->getViewRenderer()->view);
        }

        return $this->view;
    }

    /**
     * Set the view
     *
     * @param   View    $view
     *
     * @return  $this
     */
    public function setView(View $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Render the given navigation item as HTML anchor
     *
     * @param   NavigationItem  $item
     *
     * @return  string
     */
    public function render(NavigationItem $item)
    {
        $label = $this->view()->escape($item->getLabel());
        if (($icon = $item->getIcon()) !== null) {
            $label = $this->view()->icon($icon) . $label;
        }

        if (($url = $item->getUrl()) !== null) {
            $content = sprintf(
                '<a%s href="%s">%s</a>',
                $this->view()->propertiesToString($item->getAttributes()),
                $this->view()->url($url, $item->getUrlParameters()),
                $label
            );
        } else {
            $content = sprintf(
                '<%1$s%2$s>%3$s</%1$s>',
                $item::LINK_ALTERNATIVE,
                $this->view()->propertiesToString($item->getAttributes()),
                $label
            );
        }

        return $content;
    }
}
