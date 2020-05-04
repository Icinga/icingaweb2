<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

class DashletWidget extends BaseHtmlElement
{
    /** @var object $dashlet of the dashboard */
    protected $dashlet;

    protected $defaultAttributes = ['class' => 'container dashlet-sortable'];

    protected $tag = 'div';

    /**
     * Create a new dashlet widget
     *
     * @param $dashlet
     */
    public function __construct($dashlet)
    {
        $this->dashlet = $dashlet;
    }

    protected function assemble()
    {
        $this->getAttributes()->add([
            'draggable' => 'true',
            'data-icinga-url' => Url::fromPath($this->dashlet->url)->addParams(['view' => 'compact']),
            'style' => 'width: ' . $this->dashlet->style_width . '%',
            'data-icinga-dashlet-id' => $this->dashlet->id,
            'data-icinga-dashlet-col' => $this->dashlet->style_width
        ]);

        $this->add($this->title($this->dashlet->name));
    }

    protected function title($title)
    {
        return Html::tag('h1', null, $title);
    }
}
