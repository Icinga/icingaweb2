<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Widget\Tabs;
use Icinga\Web\Url;

/**
 * Tabextension that adds the basket command
 *
 * @TODO: Baskets are not supported in the codebase yet (Feature #4537)
 */
class BasketAction implements Tabextension
{
    /**
     * Applies the dashboard actions to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'basket',
            array(
                'title'     => 'URL Basket',
                'url'       => Url::fromPath('basket/add'),
                'urlParams' => array(
                    'url' => Url::fromRequest()->getRelativeUrl()
                )
            )
        );
    }
}
