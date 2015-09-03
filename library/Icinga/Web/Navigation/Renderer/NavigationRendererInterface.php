<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

use Icinga\Web\View;

/**
 * Interface for navigation renderer
 */
interface NavigationRendererInterface
{
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
    const CSS_CLASS_DROPDOWN = 'dropdown';

    /**
     * CSS class for dropdown's trigger
     */
    const CSS_CLASS_DROPDOWN_TOGGLE = 'dropdown-toggle';

    /**
     * CSS class for the ul element
     *
     * @var string
     */
    const CSS_CLASS_NAV = 'nav';

    /**
     * CSS class for the ul element w/ dropdown layout
     *
     * @var string
     */
    const CSS_CLASS_NAV_DROPDOWN = 'dropdown-menu';

    /**
     * CSS class for the ul element w/ tabs layout
     *
     * @var string
     */
    const CSS_CLASS_NAV_TABS = 'nav-tabs';

    /**
     * Icon for the dropdown's trigger
     *
     * @var string
     */
    const DROPDOWN_TOGGLE_ICON = 'menu';

    /**
     * Heading rank
     *
     * @var int
     */
    const HEADING_RANK = 2;

    /**
     * Flag for major navigation
     *
     * With this flag the outer navigation element will be nav instead of div
     *
     * @var int
     */
    const NAV_MAJOR = 1;

    /**
     * Flag for disabling the outer navigation element
     *
     * @var int
     */
    const NAV_DISABLE = 2;

    /**
     * Get the CSS class for the outer element
     *
     * @return string|null
     */
    public function getCssClass();

    /**
     * Set the CSS class for the outer element
     *
     * @param   string  $class
     *
     * @return  $this
     */
    public function setCssClass($class);

    /**
     * Get the heading
     *
     * @return string
     */
    public function getHeading();

    /**
     * Set the heading
     *
     * @param   string  $heading
     *
     * @return  $this
     */
    public function setHeading($heading);

    /**
     * Render navigation to HTML
     *
     * @return string
     */
    public function render();
}

