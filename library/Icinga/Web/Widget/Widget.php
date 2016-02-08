<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\View;
use Zend_View_Abstract;

/**
 * Abstract class for reusable view elements that can be
 * rendered to a view
 *
 */
interface Widget
{
    /**
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @param \Zend_View_Abstract $view
     * @return string
     */
    // public function render(Zend_View_Abstract $view);
}
