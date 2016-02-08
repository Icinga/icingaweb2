<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

/**
 * Interface for navigation renderers
 */
interface NavigationRendererInterface
{
    /**
     * CSS class for items
     *
     * @var string
     */
    const CSS_CLASS_ITEM = 'nav-item';

    /**
     * CSS class for active items
     *
     * @var string
     */
    const CSS_CLASS_ACTIVE = 'active';

    /**
     * CSS class for dropdown items
     *
     * @var string
     */
    const CSS_CLASS_DROPDOWN = 'dropdown-nav-item';

    /**
     * CSS class for a dropdown item's trigger
     *
     * @var string
     */
    const CSS_CLASS_DROPDOWN_TOGGLE = 'dropdown-toggle';

    /**
     * CSS class for the ul element
     *
     * @var string
     */
    const CSS_CLASS_NAV = 'nav';

    /**
     * CSS class for the ul element with dropdown layout
     *
     * @var string
     */
    const CSS_CLASS_NAV_DROPDOWN = 'dropdown-nav';

    /**
     * CSS class for the ul element with tabs layout
     *
     * @var string
     */
    const CSS_CLASS_NAV_TABS = 'tab-nav';

    /**
     * Icon for a dropdown item's trigger
     *
     * @var string
     */
    const DROPDOWN_TOGGLE_ICON = 'menu';

    /**
     * Default tag for the outer element the navigation will be wrapped with
     *
     * @var string
     */
    const OUTER_ELEMENT_TAG = 'div';

    /**
     * The heading's rank
     *
     * @var int
     */
    const HEADING_RANK = 1;

    /**
     * Set the tag for the outer element the navigation is wrapped with
     *
     * @param   string  $tag
     *
     * @return  $this
     */
    public function setElementTag($tag);

    /**
     * Return the tag for the outer element the navigation is wrapped with
     *
     * @return  string
     */
    public function getElementTag();

    /**
     * Set the CSS class to use for the outer element
     *
     * @param   string  $class
     *
     * @return  $this
     */
    public function setCssClass($class);

    /**
     * Get the CSS class used for the outer element
     *
     * @return  string
     */
    public function getCssClass();

    /**
     * Set the navigation's heading text
     *
     * @param   string  $heading
     *
     * @return  $this
     */
    public function setHeading($heading);

    /**
     * Return the navigation's heading text
     *
     * @return  string
     */
    public function getHeading();

    /**
     * Return the navigation rendered to HTML
     *
     * @return  string
     */
    public function render();
}
