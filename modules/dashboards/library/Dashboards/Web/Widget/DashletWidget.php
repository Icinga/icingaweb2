<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

class DashletWidget extends BaseHtmlElement
{
    protected $dashlets;

    protected $tag = 'div';

    protected $defaultAttributes;

    public function __construct($dashlets)
    {
        $this->dashlets = $dashlets;
    }

    protected function assemble()
    {
        $this->defaultAttributes = [
            'class' => 'container dashlet-sortable icinga-module module-monitoring',
            'data-icinga-url' => Url::fromPath($this->dashlets->url)->addParams(['view' => 'compact'])
            ];
        $this->add($this->title($this->dashlets->name));
    }

    protected function title($title)
    {
        return Html::tag('h1', null, $title);
    }
}
