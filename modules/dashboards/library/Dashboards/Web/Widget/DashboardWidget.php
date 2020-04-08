<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use ipl\Html\BaseHtmlElement;

class DashboardWidget extends BaseHtmlElement
{
    protected $dashlets;

    protected $tag = 'div';
    protected $defaultAttributes = ['class' => 'dashboard content'];

    public function __construct($dashlets)
    {
        $this->dashlets = $dashlets;
    }

    public function assemble()
    {
        foreach ($this->dashlets as $dashlet) {
            $this->add(new DashletWidget($dashlet));
        }
    }
}
