<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Exception;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

class DashboardsWidget extends BaseHtmlElement
{
    protected $dashlets;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'dashboard content'];

    public function __construct($dashlets)
    {
        $this->dashlets = $dashlets;
    }

    protected function assemble()
    {
        foreach ($this->dashlets as $dashlet) {
            try {
                $this->add(Html::tag('div', [
                    'class' => 'container dashlet-sortable icinga-module module-monitoring',
                    'draggable' => 'true',
                    'data-icinga-dashlet-col' => $dashlet->style_width,
                    'style' => 'width: ' . $dashlet->style_width . '%',
                    'data-icinga-url' => Url::fromPath($dashlet->url)->addParams(['view' => 'compact']),
                    'data-icinga-dashlet-id' => $dashlet->id
                ])->prepend($this->title($dashlet->title))
                );
            } catch (Exception $e) {
                echo 'Deshlets url could not be found' . $e->getMessage();
            }
        }

        $this->add(Html::tag('button', ['id' => 'Click-me'], 'Click me'));
    }

    protected function title($title)
    {
        return Html::tag('h1', null, $title);
    }

}
