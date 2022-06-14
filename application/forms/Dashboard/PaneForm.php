<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Util\DBUtils;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;

class PaneForm extends BaseDashboardForm
{
    public function load(BaseDashboard $dashboard): void
    {
        $this->populate([
            'org_title' => $dashboard->getTitle(),
            'title'     => $dashboard->getTitle(),
            'org_name'  => $dashboard->getName()
        ]);
    }

    protected function assemble()
    {
        $this->addElement('hidden', 'org_name', ['required' => false]);
        $this->addElement('hidden', 'org_title', ['required' => false]);

        $this->addElement('text', 'title', [
            'required'    => true,
            'label'       => t('Title'),
            'placeholder' => t('Create new Dashboard'),
            'description' => $this->isUpdating()
                ? t('Edit the title of this pane.')
                : t('Add new dashboard to this home.')
        ]);

        $homes = $this->dashboard->getEntryKeyTitleArr();
        $activeHome = $this->dashboard->getActiveHome();
        $populatedHome = $this->getPopulatedValue('home', $activeHome->getName());

        $this->addElement('select', 'home', [
            'required'     => true,
            'class'        => 'autosubmit',
            'value'        => $populatedHome,
            'multiOptions' => array_merge([self::CREATE_NEW_HOME => self::CREATE_NEW_HOME], $homes),
            'label'        => t('Assign to Home'),
            'description'  => sprintf(
                t('Select a dashboard home you want to %s the dashboard to.'),
                $this->isUpdating() ? 'move' : 'assign'
            ),
        ]);

        if (empty($homes) || $this->getPopulatedValue('home') === self::CREATE_NEW_HOME) {
            $this->addElement('text', 'new_home', [
                'required'    => true,
                'label'       => t('Dashboard Home'),
                'placeholder' => t('Enter dashboard home title'),
                'description' => t('Enter a title for the new dashboard home.'),
            ]);
        }

        $formControls = $this->createFormControls();
        $formControls->addHtml(
            $this->registerSubmitButton($this->isUpdating() ? t('Update Pane') : t('Add Pane'))
        );

        if ($this->isUpdating()) {
            $removeTargetUrl = (clone $this->requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-pane');
            $formControls->addHtml($this->createRemoveButton($removeTargetUrl, t('Remove Pane')));
        }

        $formControls->addHtml($this->createCancelButton());
        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        $conn = DBUtils::getConn();
        $dashboard = $this->dashboard;

        $selectedHome = $this->getPopulatedValue('home');
        if (! $selectedHome || $selectedHome === self::CREATE_NEW_HOME) {
            $selectedHome = $this->getPopulatedValue('new_home');
        }

        $currentHome = new DashboardHome($selectedHome);
        if ($dashboard->hasEntry($currentHome->getName())) {
            /** @var DashboardHome $currentHome */
            $currentHome = clone $dashboard->getEntry($currentHome->getName());
            if ($currentHome->getName() !== $dashboard->getActiveHome()->getName()) {
                $currentHome->loadDashboardEntries();
            }
        }

        if ($this->isUpdating()) {
            $orgHome = $dashboard->getEntry($this->requestUrl->getParam('home'));
            $orgPane = $orgHome->getEntry($this->getValue('org_name'));

            $currentPane = clone $orgPane;
            $currentPane
                ->setHome($currentHome)
                ->setTitle($this->getValue('title'));

            $diff = array_filter(array_diff_assoc($currentPane->toArray(), $orgPane->toArray()));
            if (empty($diff)) {
                return;
            }

            if ($orgHome->getName() !== $currentHome->getName() && $currentHome->hasEntry($currentPane->getName())) {
                Notification::error(sprintf(
                    t('Failed to move a pane: Dashboard Home "%s" has already a Pane called "%s"'),
                    $currentHome->getTitle(),
                    $currentPane->getTitle()
                ));

                return;
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($currentPane, $orgHome);
                // We have to update all the dashlet ids too sha1(username + home + pane + dashlet)
                $currentPane->manageEntry($currentPane->getEntries());

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();

                Logger::error(
                    'Unable to update pane "%s". An unexpected error occurred: %s',
                    $currentPane->getTitle(),
                    $err
                );

                Notification::error(t('Failed to successfully update the pane. Please check the logs for details!'));

                return;
            }

            $this->requestSucceeded = true;

            Notification::success(sprintf(t('Updated pane "%s" successfully'), $currentPane->getTitle()));
        } else {
            $pane = new Pane($this->getPopulatedValue('title'));
            if ($currentHome->hasEntry($pane->getName())) {
                Notification::error(sprintf(
                    t('Failed to add pane: Dashboard Home "%s" has already a Pane called "%s"'),
                    $currentHome->getTitle(),
                    $pane->getTitle()
                ));

                return;
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($pane);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();

                Logger::error(
                    'Unable to add pane "%s". An unexpected error occurred: %s',
                    $pane->getTitle(),
                    $err
                );

                Notification::error(t('Failed to successfully add the pane. Please check the logs for details!'));

                return;
            }

            $this->requestSucceeded = true;

            Notification::success(sprintf(t('Added pane "%s" successfully'), $pane->getTitle()));
        }
    }
}
