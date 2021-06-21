<?php

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Exception\InvalidPropertyException;
use Icinga\Web\Widget\Dashboard;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class SettingSortBox extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    private $activeHome;

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function assemble()
    {
        $sortControls = $this->dashboard->getHomeKeyNameArray(false);

        if (Url::fromRequest()->getParam('home')) {
            $this->activeHome = Url::fromRequest()->getParam('home');
        }

        $this->addElement(
            'select',
            'sort_dashboard_home',
            [
                'class'          => 'autosubmit',
                'required'       => true,
                'label'          => t('Dashboard Home'),
                'multiOptions'   => $sortControls,
                'value'          => $this->activeHome?: current($sortControls),
                'description'    => t('Select a dashboard home you want to see the dashboards from.')
            ]
        );
    }

    public function onSuccess()
    {
        // Do nothing
        parent::onSuccess();
    }

    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            $class = get_class($this);
            $message = "Access to undefined property $class::$name";

            throw new InvalidPropertyException($message);
        }

        return $this->$name;
    }
}
