<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Web\Url;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Sql\Select;
use function ipl\Stdlib\get_php_type;

class DashboardSetting extends BaseHtmlElement
{
    use Database;

    /** @var iterable $dashboards from the database */
    protected $dashboards;

    protected $defaultAttributes = ['class' => 'content'];

    protected $tag = 'div';

    /**
     * Create a new dashboards and dashlets setting
     *
     * @param iterable $dashboards The dashboards from a database
     *
     * @throws InvalidArgumentException If $dashboards is not iterable
     */
    public function __construct($dashboards)
    {
        if (!is_iterable($dashboards)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($dashboards)
            ));
        }

        $this->dashboards = $dashboards;
    }

    public function settingsAction()
    {
        $this->add(Html::tag('h1', null, t('Dashboard Settings')));

        $table = Html::tag('table', [
            'class' => 'avp action',
            'data-base-target' => '_next'
        ]);

        $table->add(Html::tag('thead', null, Html::tag('tr', null, [
            Html::tag('th', [
                'style' => 'width: 18em;'
            ], Html::tag('strong', null, t('Dashlet Name'))),
            Html::tag('th', null, Html::tag('strong', null, 'Url')),
            Html::tag('th', [
                'style' => 'width: 1.48em;'
            ])])));

        $tbody = Html::tag('tbody');

        foreach ($this->dashboards as $dashboard) {
            $tableRow1 = Html::tag('tr');

            $tableRow1->add([
                Html::tag('th', [
                    'colspan' => '2',
                    'style' => 'text-align: left; padding: 0.5em;'
                ], $dashboard->name),
                Html::tag('th', null, [
                    Html::tag('a', [
                        'href' => Url::fromPath('dashboards/dashlets/delete', [
                            'dashboardId' => $dashboard->id
                        ]),
                        'title' => 'Edit Dashboard ' . $dashboard->name
                    ], Html::tag('i', [
                        'class' => 'icon-trash',
                        'aria-hidden' => true
                    ]))
                ])
            ]);

            $tbody->add($tableRow1);

            $select = (new Select())
                ->from('dashlet')
                ->columns(['*'])
                ->where(['dashboard_id = ?' => $dashboard->id]);

            $dashlets = $this->getDb()->select($select);

            foreach ($dashlets as $dashlet) {
                $tableRow2 = Html::tag('tr');

                $tableRow2->add([Html::tag('td', $dashlet->name, [
                    Html::tag('a', [
                        'href' => Url::fromPath('dashboards'),
                    ], $dashlet->name)
                ]), Html::tag('td', [
                    'style' => 'table-layout: fixed; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'
                ], Html::tag('a', [
                    'href' => $dashlet->url,
                ], $dashlet->url)),
                    Html::tag('td', [
                        Html::tag('a', [
                            'href' => Url::fromPath('dashboards/dashlets/edit', [
                                'dashletId' => $dashlet->id
                            ])
                        ], Html::tag('i', [
                            'class' => 'icon-edit',
                            'aria-hidden' => true
                        ])),
                        Html::tag('a', [
                            'href' => Url::fromPath('dashboards/dashlets/remove', [
                                'dashletId' => $dashlet->id
                            ])
                        ], Html::tag('i', [
                            'class' => 'icon-trash',
                            'aria-hidden' => true
                        ]))
                    ])
                ]);

                $tbody->add($tableRow2);
            }
        }

        $table->add($tbody);

        return $table;
    }

    protected function assemble()
    {
        $this->add($this->settingsAction());
    }
}
