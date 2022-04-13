<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Setup;

use Icinga\Forms\Dashboard\BaseDashboardForm;
use Icinga\Forms\Dashboard\BaseSetupDashboard;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\ItemList\DashletListMultiSelect;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SetupNewDashboard extends BaseSetupDashboard
{
    protected function init()
    {
        parent::init();

        $this->setRedirectUrl((string) Url::fromPath(Dashboard::BASE_ROUTE));
        $this->setAction($this->getRedirectUrl() . '/setup-dashboard');
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('submit')) {
            $conn = Dashboard::getConn();
            $pane = new Pane($this->getPopulatedValue('pane'));
            $home = $this->dashboard->getEntry(DashboardHome::DEFAULT_HOME);

            $conn->beginTransaction();

            try {
                $this->dashboard->manageEntry($home);
                $home->manageEntry($pane);

                $this->dumpArbitaryDashlets(false);

                if (($name = $this->getPopulatedValue('dashlet')) && ($url = $this->getPopulatedValue('url'))) {
                    if ($this->duplicateCustomDashlet) {
                        Notification::error(sprintf(
                            t('Failed to create new dahlets. Dashlet "%s" exists within the selected one'),
                            $name
                        ));

                        return;
                    }

                    $dashlet = new Dashlet($name, $url, $pane);
                    $pane->manageEntry($dashlet);
                }

                $pane->manageEntry(self::$moduleDashlets);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }

            Notification::success(t('Added new dashlet(s) successfully'));
        }
    }
}
