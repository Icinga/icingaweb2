<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Notification;

class HomeForm extends BaseDashboardForm
{
    public function load(BaseDashboard $dashboard): void
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
            $home = $this->dashboard->getActiveEntry();
            if ($home->getTitle() === $this->getPopulatedValue('title')) {
                return;
            }

            $home->setTitle($this->getPopulatedValue('title'));

            try {
                $this->dashboard->manageEntry($home);
            } catch (\Exception $err) {
                Logger::error(
                    'Unable to update Dashboard Home "%s". An unexpected error occurred: %s',
                    $home->getTitle(),
                    $err
                );

                Notification::error(
                    t('Failed to successfully update the Dashboard Home. Please check the logs for details!')
                );

                return;
            }

            Notification::success(sprintf(t('Updated Dashboard Home "%s" successfully'), $home->getTitle()));
        } else {
            $home = new DashboardHome($this->getPopulatedValue('title'));
            if ($this->dashboard->hasEntry($home->getName())) {
                Notification::error(sprintf(t('Dashboard Home "%s" already exists'), $home->getTitle()));
                return;
            }

            try {
                $this->dashboard->manageEntry($home);

                $this->requestSucceeded = true;

                Notification::success(sprintf(t('Added Dashboard Home "%s" successfully'), $home->getTitle()));
            } catch (\Exception $err) {
                Logger::error(
                    'Unable to add Dashboard Home "%s". An unexpected error occurred: %s',
                    $home->getTitle(),
                    $err
                );

                Notification::error(
                    t('Failed to successfully add the Dashboard Home. Please check the logs for details!')
                );

                return;
            }
        }
    }
}
