<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

class DashletWidget extends BaseHtmlElement
{
    /** @var object $dashlet of the dashboard */
    protected $dashlet;

    protected $defaultAttributes = ['class' => 'container'];

    protected $tag = 'div';

    /**
     * Create a new dashlet widget
     * @param $dashlet
     */
    public function __construct($dashlet)
    {
        $this->dashlet = $dashlet;
    }

    protected function assemble()
    {
        $this->getAttributes()->add([
            'data-icinga-url' => Url::fromPath($this->dashlet->url)->addParams(['view' => 'compact'])
        ]);
        $this->add($this->title($this->dashlet->name));
    }

    protected function title($title)
    {
        return Html::tag('h1', null, $title);
    }
}
