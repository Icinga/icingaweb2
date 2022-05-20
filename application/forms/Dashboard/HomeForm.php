<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Notification;

class HomeForm extends BaseDashboardForm
{
    public function load(BaseDashboard $dashboard)
    {
        $this->populate(['title' => $dashboard->getTitle()]);
    }

    protected function assemble()
    {
        $this->addElement('text', 'title', [
            'required'    => true,
            'label'       => t('Title'),
            'placeholder' => t('Create new Dashboard Home'),
            'description' => $this->isUpdating() ? t('Edit the title of this dashboard home') : t('Add new dashboard.')
        ]);

        $formControls = $this->createFormControls();
        $formControls->addHtml($this->registerSubmitButton($this->isUpdating() ? t('Update Home') : t('Add Home')));

        if ($this->isUpdating()) {
            $removeTargetUrl = (clone $this->requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-home');
            $formControls->addHtml($this->createRemoveButton($removeTargetUrl, t('Remove Home')));
        }

        $formControls->addHtml($this->createCancelButton());
        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        if ($this->isUpdating()) {
            $home = $this->dashboard->getActiveHome();
            $home->setTitle($this->getPopulatedValue('title'));

            $this->dashboard->manageEntry($home);

            Notification::success(sprintf(t('Updated dashboard home "%s" successfully'), $home->getTitle()));
        } else {
            $home = new DashboardHome($this->getPopulatedValue('title'));
            if ($this->dashboard->hasEntry($home->getName())) {
                Notification::error(sprintf(t('Dashboard home "%s" already exists'), $home->getName()));
                return;
            }

            $this->dashboard->manageEntry($home);

            Notification::success(sprintf(t('Added dashboard home "%s" successfully'), $home->getName()));
        }
    }
}
