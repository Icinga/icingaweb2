<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that allows to add the current URL as menu entry
 *
 * Displayed as a dropdown field in the tabs
 */
class MenuAction implements Tabextension
{
    /**
     * Applies the menu actions to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'menu-entry',
            array(
                'icon'      => 'menu',
                'label'     => t('Add To Menu'),
                'url'       => Url::fromPath('navigation/add'),
                'urlParams' => array(
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl())
                )
            )
        );
    }
}
