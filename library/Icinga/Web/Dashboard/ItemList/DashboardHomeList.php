<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Link;

class DashboardHomeList extends ItemListControl
{
    /** @var DashboardHome */
    protected $home;

    public function __construct(DashboardHome $home)
    {
        $this->home = $home;
        $this->home->setActive();
        $this->home->loadPanesFromDB();

        $this->setCollapsibleControlClass('dashboard-list-info');
        $this->getAttributes()
            ->registerAttributeCallback('class', function () {
                return 'home-list-control collapsible widget-sortable';
            })
            ->registerAttributeCallback('data-toggle-element', function () {
                return '.dashboard-list-info';
            })
            ->registerAttributeCallback('data-icinga-home', function () {
                return $this->home->getName();
            })
            ->registerAttributeCallback('id', function () {
                return 'home_' . $this->home->getPriority();
            });
    }

    protected function assemble()
    {
        // TODO: How should disabled homes look like?
        parent::assemble();

        $header = HtmlElement::create('h1', ['class' => 'collapsible-header home'], $this->home->getLabel());
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/rename-home')->setParams([
            'home' => $this->home->getName()
        ]);

        $header->addHtml(new Link(t('Edit'), $url, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $this->addHtml($header);

        $list = HtmlElement::create('ul', ['class' => 'dashboard-item-list']);
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams(['home' => $this->home->getName()]);

        // List all dashboard panes
        foreach ($this->home->getPanes() as $pane) {
            $pane->setHome($this->home); // In case it's not set

            $list->addHtml(new DashboardList($pane));
        }

        $this->addHtml($list);
        $this->addHtml(new ActionLink(t('Add Dashboard'), $url, 'plus', [
            'class'               => 'add-dashboard',
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));
    }
}
