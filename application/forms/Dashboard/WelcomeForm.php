<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Application\Modules;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Util\DBUtils;
use Icinga\Web\Notification;
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

        $shouldDisabled = empty(Modules\DashletManager::getSystemDefaults());
        $this->addElement('submit', 'btn_use_defaults', [
            'label'    => t('Use System Defaults'),
            'disabled' => $shouldDisabled ?: null,
            'title'    => $shouldDisabled
                ? t('It could not be found any system defaults on your system. Please make sure to enable'
                    .' either icingadb or monitoring module and try it later!')
                : null
        ]);
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('btn_use_defaults')) {
            $home = $this->dashboard->getEntry(DashboardHome::DEFAULT_HOME);
            $conn = DBUtils::getConn();
            $conn->beginTransaction();

            try {
                // Default Home might have been disabled, so we have to update it first
                $this->dashboard->manageEntry($home);
                $home->manageEntry(Modules\DashletManager::getSystemDefaults(), null, true);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();

                Logger::error('Unable to apply the system defaults into the DB. An error occurred: %s', $err);

                Notification::error(t('Failed to successfully save the data. Please check the logs for details.'));

                return;
            }

            Notification::success(t('Imported system defaults successfully.'));
        }
    }
}
