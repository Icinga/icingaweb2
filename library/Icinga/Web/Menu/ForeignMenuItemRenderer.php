<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;
use Icinga\Web\Url;

/**
 * A menu item with a link that surpasses the regular navigation link behavior
 */
class ForeignMenuItemRenderer extends MenuItemRenderer
{
    protected $attributes = array(
        'target' => '_self'
    );
}
