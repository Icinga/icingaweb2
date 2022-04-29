<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class DashboardHomeList extends ItemListControl
{
    /** @var DashboardHome */
    protected $home;

    protected $defaultAttributes = ['class' => 'home-list-control'];

    public function __construct(DashboardHome $home)
    {
        $this->home = $home;
        $this->home->loadDashboardEntries();

        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-home', function () {
                return $this->home->getName();
            });
    }

    protected function getHtmlId(): string
    {
        return $this->home->getUuid();
    }

    protected function getCollapsibleControlClass(): string
    {
        return 'dashboard-list-info';
    }

    protected function createItemList(): BaseHtmlElement
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-home')
            ->setParams(['home' => $this->home->getName()]);

        $this->assembleHeader($url, $this->home->getTitle());

        $list = HtmlElement::create('ul', ['class' => 'dashboard-item-list']);
        // List all dashboard panes
        foreach ($this->home->getEntries() as $pane) {
            $pane->setHome($this->home); // In case it's not set

            $list->addHtml(new DashboardList($pane));
        }

        return $list;
    }

    protected function createActionLink(): BaseHtmlElement
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-pane');
        $url->setParams(['home' => $this->home->getName()]);

        return new ActionLink(t('Add Dashboard'), $url, 'plus', ['class' => 'add-dashboard']);
    }
}
