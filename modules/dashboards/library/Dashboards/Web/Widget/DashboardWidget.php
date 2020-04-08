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

    /**
     * @inheritDoc
     *
     * ipl/Html lacks a call to {@link BaseHtmlElement::ensureAssembled()} here. This override is subject to remove once
     * ipl/Html incorporates this fix.
     */
    public function isEmpty()
    {
        $this->ensureAssembled();

        return parent::isEmpty();
    }

    public function assemble()
    {
        foreach ($this->dashlets as $dashlet) {
            $this->add(new DashletWidget($dashlet));
        }
    }
}
