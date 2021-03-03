<?php

namespace Icinga\Forms\Dashboard;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class AvailableDashlets extends BaseHtmlElement
{
    protected $modules;

    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'            => 'common-table table-row-selectable',
        'data-base-target' => '_next',
    ];

    public function __construct($modules)
    {
        $this->modules = $modules;
    }

    protected function tableHeader()
    {
        $thead = Html::tag('thead');

        $theadRow = Html::tag('tr');

        $theadRow->add(Html::tag('th', 'Module'));
        $theadRow->add(Html::tag('th', 'Name'));

        $thead->add($theadRow);

        return $thead;
    }

    protected function tableBody()
    {
        $tbody = Html::tag('tbody');

        foreach ($this->modules as $module => $dashlets) {
            foreach ($dashlets as $key => $dashlet) {
                $row = Html::tag('tr');

                $dashletLink = new Link($module, Url::fromPath('dashboard/home')->addParams(['dashlet' => $key]));

                $row->add(Html::tag('td', $dashletLink));
                $row->add(Html::tag('td', $key));

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