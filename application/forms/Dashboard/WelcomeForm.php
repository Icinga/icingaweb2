<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class WelcomeForm extends CompatForm
{
    /** @var Dashboard */
    protected $dashboard;

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
        $element = $this->createElement('submit', 'btn_use_defaults', ['label' => t('Use System Defaults')]);
        $this->registerElement($element)->decorate($element);

        $this->addElement('submit', 'btn_customize_dashlets', [
            'label'               => t('Add Dashlets Now'),
            'href'                => Url::fromPath(Dashboard::BASE_ROUTE . '/setup-dashboard'),
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]);

        $this->getElement('btn_customize_dashlets')->setWrapper($element->getWrapper());
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('btn_use_defaults')) {
            $home = $this->dashboard->getEntry(DashboardHome::DEFAULT_HOME);
            $conn = Dashboard::getConn();
            $conn->beginTransaction();

            try {
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
