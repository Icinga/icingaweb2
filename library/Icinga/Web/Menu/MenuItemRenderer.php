<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Application\Icinga;
use Icinga\Web\Menu;
use Icinga\Web\Url;
use Icinga\Web\View;
use Icinga\Data\ConfigObject;

/**
 * Default MenuItemRenderer class
 */
class MenuItemRenderer
{
    /**
     * The view this menu item is being rendered to
     *
     * @var View|null
     */
    protected $view = null;

    /**
     * The link target
     *
     * @var string
     */
    protected $target = null;

    /**
     * Create a new instance of MenuItemRenderer
     *
     * Is is possible to configure the link target using the option 'target'
     *
     * @param ConfigObject|null $configuration
     */
    public function __construct(ConfigObject $configuration = null)
    {
        if ($configuration !== null) {
            $this->target = $configuration->get('target', null);
        }
    }

    /**
     * Get the view this menu item is being rendered to
     *
     * @return View
     */
    protected function getView()
    {
        if ($this->view === null) {
            $this->view = Icinga::app()->getViewRenderer()->view;
        }
        return $this->view;
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
        $attributes = isset($this->target) ? sprintf(' target="%s"', $this->getView()->escape($this->target)) : '';

        if ($menu->getIcon() && strpos($menu->getIcon(), '.') === false) {
            return sprintf(
                '<a href="%s"%s><i aria-hidden="true" class="icon-%s"></i>%s</a>',
                $menu->getUrl() ? : '#',
                $attributes,
                $menu->getIcon(),
                $this->getView()->escape($menu->getTitle())
            );
        }

        return sprintf(
            '<a href="%s"%s>%s%s<span></span></a>',
            $menu->getUrl() ? : '#',
            $attributes,
            $menu->getIcon()
                ? '<img aria-hidden="true" src="' . Url::fromPath($menu->getIcon()) . '" class="icon" /> '
                : '',
            $this->getView()->escape($menu->getTitle())
        );
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
}
