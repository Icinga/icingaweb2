<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Web\Widget\Dashboard;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class SettingSortBox extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function assemble()
    {
        $homes = $this->dashboard->getHomes();
        $sortControls = [];
        $active = null;
        if (Url::fromRequest()->hasParam('home')) {
            $active = Url::fromRequest()->getParam('home');
            $sortControls[$active] = $active;
        }

        foreach ($homes as $item) {
            if ($active === $item->getName()) {
                continue;
            }
            $sortControls[$item->getName()] = $item->getName();
        }

        $this->addElement(
            'select',
            'sort_dashboard_home',
            [
                'class'          => 'autosubmit',
                'required'       => true,
                'label'          => t('Dashboard Home'),
                'multiOptions'   => $sortControls ?: ['no' => 'No home available'],
                'description'    => t('Select a dashboard home you want to see the dashboards from.')
            ]
        );
    }

    public function onSuccess()
    {
        // Do nothing
        parent::onSuccess();
    }
}
