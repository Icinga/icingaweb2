<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Widget\Dashboard;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Link;

class Settings extends BaseHtmlElement
{
    /** @var Dashboard */
    private $dashboard;

    protected $tag = 'table';

    protected $defaultAttributes = [
        'class'             => 'avp action',
        'data-base-target'  => '_next'
    ];

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function tableHeader()
    {
        $thead = new HtmlElement('thead', null, new HtmlElement(
            'tr',
            null,
            [
                new HtmlElement(
                    'th',
                    ['style' => 'width: 18em;'],
                    new HtmlElement('strong', null, t('Dashlet Name'))
                ),
                new HtmlElement(
                    'th',
                    null,
                    new HtmlElement('strong', null, t('Url'))
                ),
            ]
        ));

        return $thead;
    }

    public function tableBody()
    {
        $tbody = new HtmlElement('tbody', null);
        foreach ($this->dashboard->getDashboardHomeItems() as $item) {
            foreach ($this->dashboard->getPanes() as $pane) {
                if ($pane->getDisabled() || $pane->getParentId() !== $item->getAttribute('homeId')) {
                    continue;
                }

                $tableRow = new HtmlElement('tr', null);
                $th = new HtmlElement('th', [
                    'colspan'   => '2',
                    'style'     => 'text-align: left; padding: 0.5em;'
                ]);
                if ($pane->isUserWidget()) {
                    $th->add(new Link(
                        $pane->getName(),
                        sprintf(
                            'dashboard/rename-pane?home=%s&pane=%s',
                            $this->dashboard->getHomeByName($pane->getParentId()),
                            $pane->getName()
                        ),
                        [
                            'title' => sprintf(t('Edit pane %s'), $pane->getName())
                        ]
                    ));
                } else {
                    $th->add($pane->getName());
                }

                $tableRow->add($th);
                $th = new HtmlElement('th', null);
                $th->add(new Link(
                    new HtmlElement('i', [
                        'aria-hidden'   => 'true',
                        'class'         => 'icon-trash',
                        'style'         => 'float: right'
                    ]),
                    sprintf(
                        'dashboard/remove-pane?home=%s&pane=%s',
                        $this->dashboard->getHomeByName($pane->getParentId()),
                        $pane->getName()
                    ),
                    [
                        'title' => sprintf(t('Remove pane %s'), $pane->getName()),
                    ]
                ));

                $tableRow->add($th);

                if (empty($pane->getDashlets())) {
                    $tableRow->add(new HtmlElement(
                        'tr',
                        null,
                        new HtmlElement('td', ['colspan' => '3'], t('No dashlets added to dashboard'))
                    ));
                } else {
                    foreach ($pane->getDashlets() as $dashlet) {
                        if ($dashlet->getDisabled()) {
                            continue;
                        }
                        $tr = new HtmlElement('tr', null, new HtmlElement(
                            'td',
                            null,
                            new Link(
                                $dashlet->getTitle(),
                                sprintf(
                                    'dashboard/update-dashlet?home=%s&pane=%s&dashlet=%s',
                                    $this->dashboard->getHomeByName($pane->getParentId()),
                                    $pane->getName(),
                                    $dashlet->getName()
                                ),
                                [
                                    'title' => sprintf(t('Edit dashlet %s'), $dashlet->getTitle())
                                ]
                            )
                        ));
                        $tr->add(new HtmlElement('td', [
                            'style' => ('
                                table-layout: fixed; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                            ')
                        ], new Link(
                            $dashlet->getUrl()->getRelativeUrl(),
                            $dashlet->getUrl()->getRelativeUrl(),
                            ['title' => sprintf(t('Show dashlet %s'), $dashlet->getTitle())]
                        )));
                        $tr->add(new HtmlElement('td', null, new Link(
                            new HtmlElement('i', [
                                'aria-hidden'   => 'true',
                                'class'         => 'icon-trash',
                                'style'         => 'float: right',
                                'title'         => sprintf(
                                    t('Remove dashlet %s from pane %s'),
                                    $dashlet->getTitle(),
                                    $pane->getTitle()
                                )
                            ]),
                            sprintf(
                                'dashboard/remove-dashlet?home=%s&pane=%s&dashlet=%s',
                                $this->dashboard->getHomeByName($pane->getParentId()),
                                $pane->getName(),
                                $dashlet->getName()
                            )
                        )));

                        $tableRow->add($tr);
                    }
                }

                $tbody->add($tableRow);
            }
        }

        return $tbody;
    }

    protected function assemble()
    {
        $this->add(new HtmlElement('h1', null, t('Dashboard Settings')));

        $this->add($this->tableHeader());
        $this->add($this->tableBody());
    }
}
