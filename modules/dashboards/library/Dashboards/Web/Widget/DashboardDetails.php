<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Icinga\Web\Url;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;

class DashboardDetails extends HtmlDocument
{
    /** @var object $dashboard The dashboard that will be display in the Dashboard setting */
    protected $dashboard;

    /**
     * Create a dashboard details for displaying in the dashboard Setting
     *
     * @param $dashboard
     */
    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $this->add(Html::tag('tr', null, [
            Html::tag('th', [
                'colspan' => '2',
                'style' => 'text-align: left; padding: 0.5em;'
            ], $this->dashboard->name),
            Html::tag('th', null, [
                Html::tag('a', [
                    'href' => Url::fromPath('dashboards/dashlets/delete', [
                        'dashboardId' => $this->dashboard->id
                    ]),
                ], Html::tag('i', [
                    'class' => 'icon-trash',
                    'aria-hidden' => true
                ]))
            ])
        ]));
    }
}
