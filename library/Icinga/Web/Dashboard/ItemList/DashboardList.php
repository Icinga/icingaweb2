<?php

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class DashboardList extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'dashboard-item-list'];

    protected $tag = 'ul';

    /** @var Pane */
    protected $pane;

    public function __construct(Pane $pane)
    {
        $this->pane = $pane;

        $this->getAttributes()->add('class', $pane->getName());
    }

    protected function assemble()
    {
        // TODO: How should disabled dashboard panes look like?
        $wrapper = HtmlElement::create('div', [
            'class'                 => 'dashboard-list-control collapsible',
            'data-toggle-element'   => '.dashlets-list-info'
        ]);

        $wrapper->addHtml(HtmlElement::create('div', ['class' => 'dashlets-list-info'], [
            new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
        ]));

        $header = HtmlElement::create('h1', ['class' => 'collapsible-header'], $this->pane->getTitle());
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-pane')->setParams([
            'home'  => $this->pane->getHome()->getName(),
            'pane'  => $this->pane->getName()
        ]);

        $header->addHtml(new Link(t('Edit'), $url, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $wrapper->addHtml($header);

        foreach ($this->pane->getDashlets() as $dashlet) {
            $this->addHtml(new DashletListItem($dashlet, true));
        }

        $wrapper->addHtml(new ActionLink(
            t('Add Dashlet'),
            Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet')->addParams(['pane' => $this->pane->getName()]),
            'plus',
            [
                'class'                 => 'add-dashlet',
                'data-icinga-modal'     => true,
                'data-no-icinga-ajax'   => true
            ]
        ));

        $this->addWrapper($wrapper);
    }
}
