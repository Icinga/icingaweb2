<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Icinga\Web\Url;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class DashletDetails extends HtmlDocument
{
    /** @var object $dashlet The dashlet from the selected dashboard */
    protected $dashlet;

    /** @var object $dashboard Dashboard from which the dashlet is deleted */
    protected $dashboard;

    /**
     * Create a dashlet details to display it in detail in the dashboard setting
     *
     * @param $dashlet
     *
     * @param $dashboard
     */
    public function __construct($dashlet, $dashboard)
    {
        $this->dashlet = $dashlet;
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $this->add(Html::tag('tr', null, [Html::tag('td', $this->dashlet->name, [
            Html::tag('a', [
                'href' => Url::fromPath('dashboards'),
            ], $this->dashlet->name)
        ]), Html::tag('td', [
            'style' => 'table-layout: fixed; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'
        ], Html::tag('a', [
            'href' => $this->dashlet->url,
        ], $this->dashlet->url)),
            Html::tag('td', [
                Html::tag('a', [
                    'href' => Url::fromPath('dashboards/dashlets/edit', [
                        'dashletId' => $this->dashlet->id,
                        'dashboardId' => $this->dashboard->id
                    ])
                ], Html::tag('i', [
                    'class' => 'icon-edit',
                    'aria-hidden' => true
                ])),
                Html::tag('a', [
                    'href' => Url::fromPath('dashboards/dashlets/remove', [
                        'dashletId' => $this->dashlet->id,
                        'dashboardId' => $this->dashboard->id
                    ])
                ], Html::tag('i', [
                    'class' => 'icon-trash',
                    'aria-hidden' => true
                ]))
            ])
        ]));
    }
}
