<?php

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class DashboardHomeList extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'home-item-list'];

    protected $tag = 'ul';

    /** @var DashboardHome */
    protected $home;

    public function __construct(DashboardHome $home)
    {
        $this->home = $home;
        $this->home->setActive();
        $this->home->loadDashboardsFromDB();

        $this->getAttributes()->add('class', $home->getName());
    }

    protected function assemble()
    {
        $wrapper = HtmlElement::create('div', [
            'class'                 => 'home-list-control collapsible',
            'data-toggle-element'   => '.dashboard-list-info'
        ]);

        $wrapper->addHtml(HtmlElement::create('div', ['class' => 'dashboard-list-info'], [
            new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
        ]));

        $header = HtmlElement::create('h1', ['class' => 'collapsible-header home'], $this->home->getLabel());
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/rename-home')->setParams(['home' => $this->home->getName()]);

        $header->addHtml(new Link(t('Edit'), $url, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $wrapper->addHtml($header);

        // List all dashboard panes
        foreach ($this->home->getPanes() as $pane) {
            $pane->setHome($this->home); // In case it's not set

            $this->addHtml(new DashboardList($pane));
        }

        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams(['home' => $this->home->getName()]);

        $wrapper->addHtml(new ActionLink(t('Add Dashboard'), $url, 'plus', [
            'class'                 => 'add-dashboard',
            'data-icinga-modal'     => true,
            'data-no-icinga-ajax'   => true
        ]));

        $this->addWrapper($wrapper);
    }
}
