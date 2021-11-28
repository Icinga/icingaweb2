<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

class Settings extends CompatForm
{
    use FormUid;

    /** @var Dashboard */
    protected $dashboard;

    protected $defaultAttributes = [
        'class' => 'icinga-form icinga-controls',
        'name'  => 'settings-widget'
    ];

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assembleHeader()
    {
        return new HtmlElement('thead', null, HtmlElement::create(
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
    }

    protected function assembleBody()
    {
        $home = $this->dashboard->getActiveHome();
        $tbody = new HtmlElement('tbody', null);

        if (! empty($home)) {
            $tableRow = new HtmlElement('tr', null, HtmlElement::create('th', [
                'class'     => 'dashboard-th home-th',
                'colspan'   => '2',
            ], new Link(
                $home->getLabel(),
                sprintf('%s/rename-home?home=%s', Dashboard::BASE_ROUTE, $home->getName()),
                [
                    'title' => sprintf(t('Edit home %s'), $home->getName())
                ]
            )));

            if ($home->isDisabled()) {
                $tableRow->addHtml(HtmlElement::create('td', [
                    'class' => 'disabled-icon-td',
                ], new Icon('ban', ['class' => "ban-icon"])));

                return $tbody->addHtml($tableRow);
            }

            $tbody->addHtml($tableRow);
        }

        if ($home && empty($home->getPanes())) {
            $tbody->addHtml(new HtmlElement(
                'tr',
                null,
                HtmlElement::create('td', ['colspan' => '3'], t('Currently there is no dashboard available.'))
            ));
        } elseif($home) {
            $count = 0;
            foreach ($home->getPanes() as $pane) {
                $tableRow = new HtmlElement('tr', null);
                $th = HtmlElement::create('th', [
                    'colspan'   => '2',
                    'class'     => 'dashboard-th pane-th'
                ]);
                $th->addHtml(new Link(
                    $pane->getTitle(),
                    sprintf(
                        '%s/rename-pane?home=%s&pane=%s',
                        Dashboard::BASE_ROUTE,
                        $home->getName(),
                        $pane->getName()
                    ),
                    [
                        'title' => sprintf(t('Edit pane %s'), $pane->getName())
                    ]
                ));

                $tableRow->addHtml($th);
                if ($pane->isDisabled()) {
                    $tableRow->addHtml(HtmlElement::create('td', [
                        'class' => 'disabled-icon-td'
                    ], new Icon('ban', ['class' => 'ban-icon'])));

                    $tbody->addHtml($tableRow);

                    continue;
                }

                if ($count > 0) {
                    $tableRow->addHtml(HtmlElement::create('td', [
                        'class'             => 'icon-col text-right',
                        'data-base-target'  => '_self'
                    ], HtmlElement::create('button', [
                        'name'          => 'pane_newpos',
                        'class'         => 'link-button icon-only animated move-up',
                        'type'          => 'submit',
                        'value'         => $pane->getName() . '|' . ($count - 1),
                        'title'         => t('Move up dashboard pane'),
                        'aria-label'    => sprintf(t('Move dashboard pane %s upwards'), $pane->getTitle())
                    ], new Icon('arrow-up', ['class' => 'up-small']))));
                }

                if ($count + 1 < count($home->getPanes())) {
                    $tableRow->addHtml(HtmlElement::create('td', [
                        'class'             => 'icon-col text-right',
                        'data-base-target'  => '_self'
                    ], HtmlElement::create('button', [
                        'name'          => 'pane_newpos',
                        'class'         => 'link-button icon-only animated move-down',
                        'type'          => 'submit',
                        'value'         => $pane->getName() . '|' . ($count + 1),
                        'title'         => t('Move down dashboard pane'),
                        'aria-label'    => sprintf(t('Move dashboard pane %s downwards'), $pane->getTitle())
                    ], new Icon('arrow-down', ['class' => 'down-small']))));
                }

                if (empty($pane->getDashlets())) {
                    $tableRow->addHtml(new HtmlElement(
                        'tr',
                        null,
                        HtmlElement::create('td', ['colspan' => '3'], t('No dashlets added to dashboard'))
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
                                    '%s/update-dashlet?home=%s&pane=%s&dashlet=%s',
                                    Dashboard::BASE_ROUTE,
                                    $home->getName(),
                                    $pane->getName(),
                                    $dashlet->getName()
                                ),
                                ['title' => sprintf(t('Edit dashlet %s'), $dashlet->getTitle())]
                            )
                        ));

                        $tr->addHtml(HtmlElement::create('td', ['class' => 'dashlet-class-td'], new Link(
                            $dashlet->getUrl()->getRelativeUrl(),
                            $dashlet->getUrl()->getRelativeUrl(),
                            ['title' => sprintf(t('Show dashlet %s'), $dashlet->getTitle())]
                        )));

                        if ($dashlet->isDisabled()) {
                            $tr->addHtml(HtmlElement::create('td', [
                                'class' => 'disabled-icon-td'
                            ], new Icon('ban', ['class' => 'ban-icon'])));
                        }

                        $tableRow->addHtml($tr);
                    }
                }

                $count++;
                $tbody->addHtml($tableRow);
            }
        }

        return $tbody;
    }

    protected function assemble()
    {
        $this->getAttributes()->add('style', 'max-width: 100%; width: 100%;');
        $this->addHtml(new HtmlElement('h1', null, Text::create(t('Dashboard Settings'))));

        $table = HtmlElement::create('table', [
            'class'             => 'avp action',
            'data-base-target'  => '_next'
        ]);

        $table->addHtml($this->assembleHeader());
        $table->addHtml($this->assembleBody());

        $this->addHtml($table);
        $this->addHtml($this->createUidElement());
    }

    protected function onSuccess()
    {
        $posData = $this->getPopulatedValue('pane_newpos');
        if ($posData) {
            list($pane, $position) = explode('|', $posData, 2);

            $home = $this->dashboard->getHome(Url::fromRequest()->getParam('home'));
            $cloned = clone $home;
            $this->dashboard->manageHome($cloned->setPanes([]));
            $cloned->setPanes($home->getPanes());
            $cloned->reorderPane($cloned->getPane($pane), (int) $position);

            Notification::success(t('Successfully update'));
        }
    }
}
