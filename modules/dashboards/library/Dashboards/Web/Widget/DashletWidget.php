<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Exception;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;

class DashletWidget extends BaseHtmlElement
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
                    'data-icinga-url' => Url::fromPath($dashlet->url)->addParams(['view' => 'compact'])
                    ])->prepend($this->title($dashlet->name))
                );
            } catch (Exception $e) {
                throw new Exception("Can't access to dashlet table" . $e->getMessage());
            }
        }
    }

    protected function title($title)
    {
        return Html::tag('h1', null, $title);
    }
}
