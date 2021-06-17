<?php

namespace Icinga\Web\Dashboard;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class AvailableDashlets extends BaseHtmlElement
{
    protected $moduleDashlets;

    protected $properties = [];

    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'            => 'common-table table-row-selectable',
        'data-base-target' => '_next',
    ];

    public function __construct($dashlets)
    {
        $this->moduleDashlets = $dashlets;
    }

    protected function tableHeader()
    {
        $thead = HtmlElement::create('thead');

        $theadRow = HtmlElement::create('tr');

        $theadRow->add(HtmlElement::create('th', null, 'Module'));
        $theadRow->add(HtmlElement::create('th', null, 'Name'));

        $thead->add($theadRow);

        return $thead;
    }

    protected function tableBody()
    {
        $tbody = Html::tag('tbody');

        foreach ($this->moduleDashlets as $module => $dashlets) {
            foreach ($dashlets as $key => $dashlet) {
                $row = HtmlElement::create('tr', [
                    'data-icinga-request-url'   => Url::fromPath('dashboard/new-pane'),
                    'data-icinga-url'           => $dashlet['url']
                ]);

                $dashletLink = new Link($module, $dashlet['url']);
                $row->add(HtmlElement::create('td', null, $dashletLink));
                $row->add(HtmlElement::create('td', null, $key));

                $row->add(HtmlElement::create('td', [
                    'style' => 'text-align: center; width: 10px'
                ], HtmlElement::create('a', [
                    'href' => '#'
                ], HtmlElement::create('i', [
                    'class' => 'pinned-dashlets icon-pin',
                ]))));

                $tbody->add($row);
            }
        }

        return $tbody;
    }

    protected function assemble()
    {
        $this->add($this->tableHeader());
        $this->add($this->tableBody());
    }
}
