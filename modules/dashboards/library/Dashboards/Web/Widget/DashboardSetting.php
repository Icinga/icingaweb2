<?php

namespace Icinga\Module\Dashboards\Web\Widget;

use Icinga\Module\Dashboards\Common\Database;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Sql\Select;
use function ipl\Stdlib\get_php_type;

class DashboardSetting extends BaseHtmlElement
{
    use Database;

    /** @var iterable|null $dashboards public dashboards the database */
    protected $dashboards;

    /** @var iterable|null $userDashboards private dashboards */
    protected $userDashboards;

    protected $defaultAttributes = ['class' => 'content setting'];

    protected $tag = 'div';

    /**
     * Create a new dashboards and dashlets setting
     *
     * @param iterable|null $dashboards All public dashboards from the database
     *
     * @param iterable|null $userDashboards Private dashboards
     *
     * @throws InvalidArgumentException If $dashboards|$userDashboards are not iterable
     */
    public function __construct($dashboards = null, $userDashboards = null)
    {
        if (!is_iterable($dashboards)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($dashboards)
            ));
        }

        if (!is_iterable($userDashboards)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects parameter 1 to be iterable, got %s instead',
                __METHOD__,
                get_php_type($userDashboards)
            ));
        }

        $this->dashboards = $dashboards;
        $this->userDashboards = $userDashboards;
    }

    /**
     * @inheritDoc
     *
     * ipl/Html lacks a call to {@link BaseHtmlElement::ensureAssembled()} here. This override is subject to remove once
     * ipl/Html incorporates this fix.
     */
    public function isEmpty()
    {
        $this->ensureAssembled();

        return parent::isEmpty();
    }

    /**
     * Display the dashboards and dashlets setting
     *
     * @return \ipl\Html\HtmlElement
     */
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

        if (!empty($this->dashboards)) {
            foreach ($this->dashboards as $dashboard) {
                $tbody->add(new DashboardDetails($dashboard));

                $select = (new Select())
                    ->from('dashlet')
                    ->columns(['*'])
                    ->where(['dashboard_id = ?' => $dashboard->id]);

                $dashlets = $this->getDb()->select($select);

                foreach ($dashlets as $dashlet) {
                    $tbody->add(new DashletDetails($dashlet, $dashboard));
                }
            }
        }

        if (!empty($this->userDashboards)) {
            foreach ($this->userDashboards as $userDashboard) {
                $tbody->add(new DashboardDetails($userDashboard));

                $select = (new Select())
                    ->from('user_dashlet')
                    ->columns(['*'])
                    ->where(['user_dashboard_id = ?' => $userDashboard->id]);

                $userDashlets = $this->getDb()->select($select);

                foreach ($userDashlets as $userDashlet) {
                    $tbody->add(new DashletDetails($userDashlet, $userDashboard));
                }
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
