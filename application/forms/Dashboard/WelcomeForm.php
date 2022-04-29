<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use ipl\Html\Form;
use ipl\Web\Url;

class WelcomeForm extends Form
{
    /** @var Dashboard */
    protected $dashboard;

    protected $defaultAttributes = ['class' => 'icinga-controls'];

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
        $this->setRedirectUrl((string) Url::fromPath(Dashboard::BASE_ROUTE));
    }

    public function hasBeenSubmitted()
    {
        return parent::hasBeenSubmitted() || $this->getPressedSubmitElement();
    }

    protected function assemble()
    {
        $this->addElement('submit', 'btn_customize_dashlets', [
            'label'               => t('Add Dashlets Now'),
            'href'                => Url::fromPath(Dashboard::BASE_ROUTE . '/setup-dashboard'),
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]);

        $this->addElement('submit', 'btn_use_defaults', ['label' => t('Use System Defaults')]);
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('btn_use_defaults')) {
            $home = $this->dashboard->getEntry(DashboardHome::DEFAULT_HOME);
            $conn = Dashboard::getConn();
            $conn->beginTransaction();

            try {
                // Default Home might have been disabled, so we have to update it first
                $this->dashboard->manageEntry($home);
                $home->manageEntry($this->dashboard->getSystemDefaults(), null, true);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }
        }
    }
}
