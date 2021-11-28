<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Widget\Dashboard;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class HomeViewSwitcher extends CompatForm
{
    use FormUid;

    /** @var Dashboard */
    private $dashboard;

    protected $defaultAttributes = [
        'class' => 'icinga-form icinga-controls',
        'name'  => 'home-mode-switcher'
    ];

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $sortControls = $this->dashboard->getHomeKeyNameArray(false);
        $activeHome = Url::fromRequest()->getParam('home');

        $this->addElement(
            'select',
            'sort_dashboard_home',
            [
                'class'          => 'autosubmit',
                'required'       => true,
                'label'          => t('Dashboard Home'),
                'multiOptions'   => $sortControls,
                'value'          => $activeHome ?: current($sortControls),
                'description'    => t('Select a dashboard home you want to see the dashboards from.')
            ]
        );

        $this->addHtml($this->createUidElement());
    }

    protected function onSuccess()
    {
        // Do nothing
    }
}
