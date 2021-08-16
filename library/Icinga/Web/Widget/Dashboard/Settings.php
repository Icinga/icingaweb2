<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
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
        $thead = new HtmlElement('thead', null, HtmlElement::create(
            'tr',
            null,
            [
                HtmlElement::create(
                    'th',
                    null,
                    new HtmlElement('strong', null, Text::create(t('Dashlet Name')))
                ),
                new HtmlElement(
                    'th',
                    null,
                    new HtmlElement('strong', null, Text::create(t('Url')))
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
                HtmlElement::create('th', [
                    'class'     => 'dashboard-th home-th',
                    'colspan'   => '2',
                ], new Link(
                    $home->getLabel(),
                    sprintf('%s/rename-home?home=%s', DashboardHome::BASE_PATH, $home->getName()),
                    [
                        'title' => sprintf(t('Edit home %s'), $home->getName())
                    ]
                ))
            );

            $tbody->add($tableRow);
        }

        if (empty($home->getPanes())) {
            $tbody->add(new HtmlElement(
                'tr',
                null,
                HtmlElement::create('td', ['colspan' => '3'], t('Currently there is no dashboard available.'))
            ));
        } else {
            foreach ($home->getPanes() as $pane) {
                $tableRow = new HtmlElement('tr', null);
                $th = HtmlElement::create('th', [
                    'colspan'   => '2',
                    'class'     => 'dashboard-th pane-th'
                ]);
                $th->add(new Link(
                    $pane->getTitle(),
                    sprintf(
                        '%s/rename-pane?home=%s&pane=%s',
                        DashboardHome::BASE_PATH,
                        $home->getName(),
                        $pane->getName()
                    ),
                    [
                        'title' => sprintf(t('Edit pane %s'), $pane->getName())
                    ]
                ));

                $tableRow->add($th);

                if (empty($pane->getDashlets())) {
                    $tableRow->add(new HtmlElement(
                        'tr',
                        null,
                        HtmlElement::create('td', ['colspan' => '3'], t('No dashlets added to dashboard'))
                    ));
                } else {
                    /** @var Dashlet $dashlet */
                    foreach ($pane->getDashlets() as $dashlet) {
                        $tr = new HtmlElement('tr', null, new HtmlElement(
                            'td',
                            null,
                            new Link(
                                $dashlet->getTitle(),
                                sprintf(
                                    '%s/update-dashlet?home=%s&pane=%s&dashlet=%s',
                                    DashboardHome::BASE_PATH,
                                    $home->getName(),
                                    $pane->getName(),
                                    $dashlet->getName()
                                ),
                                ['title' => sprintf(t('Edit dashlet %s'), $dashlet->getTitle())]
                            )
                        ));

                        $tr->add(HtmlElement::create('td', ['class' => 'dashlet-class-td'], new Link(
                            $dashlet->getUrl()->getRelativeUrl(),
                            $dashlet->getUrl()->getRelativeUrl(),
                            ['title' => sprintf(t('Show dashlet %s'), $dashlet->getTitle())]
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
        $this->add(new HtmlElement('h1', null, Text::create(t('Dashboard Settings'))));

        $this->add($this->tableHeader());
        $this->add($this->tableBody());
    }
}
