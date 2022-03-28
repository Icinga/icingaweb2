<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class DashboardList extends ItemListControl
{
    /** @var Pane */
    protected $pane;

    protected $defaultAttributes = ['class' => 'dashboard-list-control'];

    public function __construct(Pane $pane)
    {
        $this->pane = $pane;

        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-pane', function () {
                return $this->pane->getName();
            });
    }

    protected function getHtmlId()
    {
        return bin2hex($this->pane->getUuid());
    }

    protected function getCollapsibleControlClass()
    {
        return 'dashlets-list-info';
    }

    protected function createItemList()
    {
        $pane = $this->pane;
        $this->getAttributes()->set('data-toggle-element', '.dashlets-list-info');
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-pane')
            ->setParams(['home' => $pane->getHome()->getName(), 'pane' => $pane->getName()]);

        $this->assembleHeader($url, $pane->getTitle());

        $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list']);
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams([
            'home' => $pane->getHome()->getName(),
            'pane' => $pane->getName()
        ]);

        foreach ($pane->getEntries() as $dashlet) {
            $list->addHtml(new DashletListItem($dashlet, true));
        }

        return $list;
    }

    protected function createActionLink()
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams([
            'home' => $this->pane->getHome()->getName(),
            'pane' => $this->pane->getName()
        ]);

        return new ActionLink(t('Add Dashlet'), $url, 'plus', ['class' => 'add-dashlet']);
    }
}
