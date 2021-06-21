<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Widget\Dashboard;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
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
        $home = $this->dashboard->getActiveHome();
        $tbody = new HtmlElement('tbody', null);

        if (! empty($home)) {
            $tableRow = new HtmlElement(
                'tr',
                null,
                new HtmlElement('th', [
                    'colspan'   => '2',
                    'style'     => 'text-align: left; padding: 0.5em; background-color: #0095bf;'
                ], new Link(
                    $home->getLabel(),
                    sprintf('dashboard/rename-home?home=%s', $home->getName()),
                    [
                        'title' => sprintf(t('Edit home %s'), $home->getName())
                    ]
                ))
            );

            if ($home->getDisabled()) {
                $tableRow->add(new HtmlElement('td', [
                    'style' => 'text-align: center; width: 15px;'
                ], new Icon('ban', ['style' => 'color: red; font-size: 1.5em;'])));

                return $tbody->add($tableRow);
            }

            $tbody->add($tableRow);
        }

        if (empty($home->getPanes())) {
            $tbody->add(new HtmlElement(
                'tr',
                null,
                new HtmlElement('td', ['colspan' => '3'], t('Currently there is no dashboard available.'))
            ));
        } else {
            foreach ($home->getPanes() as $pane) {
                $tableRow = new HtmlElement('tr', null);
                $th = new HtmlElement('th', [
                    'colspan'   => '2',
                    'style'     => 'text-align: left; padding: 0.5em;'
                ]);
                $th->add(new Link(
                    $pane->getTitle(),
                    sprintf(
                        'dashboard/rename-pane?home=%s&pane=%s',
                        $home->getName(),
                        $pane->getName()
                    ),
                    [
                        'title' => sprintf(t('Edit pane %s'), $pane->getName())
                    ]
                ));

                $tableRow->add($th);
                if ($pane->getDisabled()) {
                    $tableRow->add(new HtmlElement('td', ['style' => 'text-align: right; width: 1%;'], new Icon(
                        'ban',
                        ['style' => 'font-size: 1.5em; color: red; position: relative;']
                    )));

                    $tbody->add($tableRow);
                    continue;
                }

                if (empty($pane->getDashlets())) {
                    $tableRow->add(new HtmlElement(
                        'tr',
                        null,
                        new HtmlElement('td', ['colspan' => '3'], t('No dashlets added to dashboard'))
                    ));
                } else {
                    /** @var \Icinga\Web\Dashboard\Dashlet $dashlet */
                    foreach ($pane->getDashlets() as $dashlet) {
                        $tr = new HtmlElement('tr', null, new HtmlElement(
                            'td',
                            null,
                            new Link(
                                $dashlet->getTitle(),
                                sprintf(
                                    'dashboard/update-dashlet?home=%s&pane=%s&dashlet=%s',
                                    $home->getName(),
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

                        if ($dashlet->getDisabled()) {
                            $tr->add(new HtmlElement('td', [
                                'style' => 'text-align: right;'
                            ], new Icon('ban', ['style' => 'font-size: 1.5em; color: red; position: relative;'])));
                        }

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
