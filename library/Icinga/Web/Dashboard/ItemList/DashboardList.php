<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class DashboardList extends ItemListControl
{
    /** @var Pane */
    protected $pane;

    protected $defaultAttributes = ['class' => 'dashboard-list-control'];

    public function __construct(Pane $pane)
    {
        $this->pane = $pane;
        if (! $this->pane->hasEntries()) {
            $this->pane->loadDashboardEntries();
        }

        $this->getAttributes()
            ->registerAttributeCallback('data-icinga-pane', function () {
                return $this->pane->getName();
            });
    }

    protected function getHtmlId(): string
    {
        return bin2hex($this->pane->getUuid());
    }

    protected function shouldExpandByDefault(): bool
    {
        return $this->pane->getHome()->isActive() && $this->pane->isActive();
    }

    protected function createItemList(): BaseHtmlElement
    {
        $pane = $this->pane;
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-pane')->setParams([
            'home' => $pane->getHome()->getName(),
            'pane' => $pane->getName()
        ]);

        if ($this->pane->isActive()) {
            $url->addParams(['highlighted' => true]);
        }

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

    protected function createActionLink(): BaseHtmlElement
    {
        $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet');
        $url->setParams([
            'home' => $this->pane->getHome()->getName(),
            'pane' => $this->pane->getName()
        ]);

        return new Link(
            [new Icon('plus'), t('Add Dashlet')],
            $url,
            ['class' => ['button-link', 'add-dashlet']]
        );
    }
}
