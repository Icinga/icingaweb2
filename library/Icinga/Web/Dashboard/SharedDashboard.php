<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Widget\Dashboard;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Orm\Query;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class SharedDashboard extends BaseHtmlElement
{
    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'            => 'common-table table-row-selectable',
        'data-base-target' => '_next',
    ];

    /** @var Dashboard */
    protected $dashboard;

    /**
     * A list of shared @see Pane to be rendered
     *
     * @var Pane[]
     */
    protected $panes = [];

    public function __construct(Dashboard $dashboard, Query $query)
    {
        $this->dashboard = $dashboard;
        $this->panes = $this->dashboard->getActiveHome()->getSharedPanes($query);
    }

    protected function assembleHeader()
    {
        $thead = HtmlElement::create('thead');

        $theadRow = HtmlElement::create('tr');

        $theadRow->addHtml(HtmlElement::create('th', null, t('Name')));
        $theadRow->addHtml(HtmlElement::create('th', null, t('Owner')));
        $theadRow->addHtml(HtmlElement::create('th', null, t('Acceptance')));
        $theadRow->addHtml(HtmlElement::create('th', null, t('Last Modified')));
        $theadRow->addHtml(HtmlElement::create('th', null, t('Current Status')));

        $thead->addHtml($theadRow);

        return $thead;
    }

    protected function assembleBody()
    {
        $body = new HtmlElement('tbody');
        foreach ($this->panes as $pane) {
            $row = HtmlElement::create('tr');
            $pane->loadMembers();

            $owner = $pane->getOwner();
            if ($owner === $this->dashboard->getAuthUser()->getUsername()) {
                $owner = t('It\'s you');
            }

            $urlParams = [];
            if ($pane->hasMembers()) {
                $urlParams['home'] = $pane->getHome()->getName();
            }

            $urlParams['pane'] = $pane->getName();
            $targetUrl = Url::fromPath(Dashboard::BASE_ROUTE . '/take-share')->setParams($urlParams);
            $link = new Link($pane->getTitle(), $targetUrl);
            $attributes = ! $this->dashboard->getAuthUser()->hasWriteAccess()
                ? ['disabled' => 'disabled']
                : null;

            $row->addHtml(HtmlElement::create('td', $attributes, $link));
            $row->addHtml(HtmlElement::create('td', null, $owner));
            $row->addHtml(HtmlElement::create('td', null, count($pane->getMembers())));
            $row->addHtml(HtmlElement::create('td', null, $pane->getMtime() ?: $pane->getCtime()));
            $row->addHtml(HtmlElement::create(
                'td',
                null,
                ! $pane->hasMembers() ? t('Not used') : t('Dashboard ') . $pane->getHome()->getLabel()
            ));

            $body->addHtml($row);
        }

        if (empty($this->panes)) {
            $body->addHtml(HtmlElement::create('td', null, t('No shared dashboard found')));
        }

        return $body;
    }

    protected function assemble()
    {
        $this->addHtml($this->assembleHeader());
        $this->addHtml($this->assembleBody());
    }
}
