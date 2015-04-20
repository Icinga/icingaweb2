<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Application\Icinga;
use Icinga\Web\Menu;
use Icinga\Web\Url;
use Icinga\Web\View;

/**
 * Default MenuItemRenderer class
 */
class MenuItemRenderer
{
    /**
     * Contains <a> element specific attributes
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * View
     *
     * @var View|null
     */
    protected $view;

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
     * Get the view
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view === null) {
            $this->view = Icinga::app()->getViewRenderer()->view;
        }
        return $this->view;
    }

    /**
     * Renders the html content of a single menu item
     *
     * @param Menu $menu
     *
     * @return string
     */
    public function render(Menu $menu)
    {
        return $this->createLink($menu);
    }

    /**
     * Creates a menu item link element
     *
     * @param Menu $menu
     *
     * @return string
     */
    public function createLink(Menu $menu)
    {
        if ($menu->getIcon() && strpos($menu->getIcon(), '.') === false) {
            return sprintf(
                '<a href="%s"%s><i aria-hidden="true" class="icon-%s"></i>%s</a>',
                $menu->getUrl() ? : '#',
                $this->getAttributes(),
                $menu->getIcon(),
                $this->getView()->escape($menu->getTitle())
            );
        }

        return sprintf(
            '<a href="%s"%s>%s%s<span></span></a>',
            $menu->getUrl() ? : '#',
            $this->getAttributes(),
            $menu->getIcon() ? '<img aria-hidden="true" src="' . Url::fromPath($menu->getIcon()) . '" class="icon" /> ' : '',
            $this->getView()->escape($menu->getTitle())
        );
    }

    /**
     * Returns <a> element specific attributes if present
     *
     * @return string
     */
    protected function getAttributes()
    {
        $attributes = '';
        $view = $this->getView();
        foreach ($this->attributes as $attribute => $value) {
            $attributes .= ' ' . $view->escape($attribute) . '="' . $view->escape($value) . '"';
        }
        return $attributes;
    }
}
