<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Link;

class DashboardList extends ItemListControl
{
    /** @var Pane */
    protected $pane;

    public function __construct(Pane $pane)
    {
        $this->pane = $pane;

        $this->setCollapsibleControlClass('dashlets-list-info');
        $this->getAttributes()
            ->registerAttributeCallback('class', function () {
                return 'dashboard-list-control collapsible widget-sortable';
            })
            ->registerAttributeCallback('data-toggle-element', function () {
                return '.dashlets-list-info';
            })
            ->registerAttributeCallback('data-icinga-pane', function () {
                return $this->pane->getHome()->getName() . '|' . $this->pane->getName();
            })
            ->registerAttributeCallback('id', function () {
                return 'pane_' . $this->pane->getPriority();
            });
    }

    protected function assemble()
    {
        // TODO: How should disabled dashboard panes look like?
        parent::assemble();

        $header = HtmlElement::create('h1', ['class' => 'collapsible-header'], $this->pane->getTitle());
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-pane')->setParams([
            'home' => $this->pane->getHome()->getName(),
            'pane' => $this->pane->getName()
        ]);

        $header->addHtml(new Link(t('Edit'), $url, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $this->addHtml($header);

        $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list']);
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams([
            'home' => $this->pane->getHome(),
            'pane' => $this->pane->getName()
        ]);

        foreach ($this->pane->getDashlets() as $dashlet) {
            $list->addHtml(new DashletListItem($dashlet, true));
        }

        $this->addHtml($list);
        $this->addHtml(new ActionLink(t('Add Dashlet'), $url, 'plus', [
            'class'               => 'add-dashlet',
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));
    }
}
