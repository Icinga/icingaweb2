<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Widget\Tabs;

/**
 * Tabextension interface that allows to extend a tabbar with reusable components
 *
 * Tabs can be either extended by creating a `Tabextension` and calling the `apply()` method
 * or by calling the `\Icinga\Web\Widget\Tabs` `extend()` method and providing
 * a tab extension
 *
 * @see \Icinga\Web\Widget\Tabs::extend()
 */
interface Tabextension
{
    /**
     * Apply this tabextension to the provided tabs
     *
     * @param Tabs $tabs The tabbar to modify
     */
    public function apply(Tabs $tabs);
}
