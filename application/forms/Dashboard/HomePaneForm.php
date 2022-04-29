<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Web\Url;

class HomePaneForm extends BaseDashboardForm
{
    protected function assemble()
    {
        $titleDesc = t('Edit the title of this dashboard home');
        $buttonLabel = t('Update Home');
        $removeButtonLabel = t('Remove Home');

        $activeHome = $this->dashboard->getActiveHome();
        $requestUrl = Url::fromRequest();
        $removeTargetUrl = (clone $requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-home');

        $this->addElement('hidden', 'org_name', ['required' => false]);
        $this->addElement('hidden', 'org_title', ['required' => false]);

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/edit-pane') {
            $titleDesc = t('Edit the title of this dashboard pane');
            $buttonLabel = t('Update Pane');
            $removeButtonLabel = t('Remove Pane');
        }

        $this->addElement('text', 'title', [
            'required'    => true,
            'label'       => t('Title'),
            'description' => $titleDesc
        ]);

        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/edit-pane') {
            $removeTargetUrl = (clone $requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-pane');

            $homes = $this->dashboard->getEntryKeyTitleArr();
            $populatedHome = $this->getPopulatedValue('home', $activeHome->getName());

            $this->addElement('select', 'home', [
                'class'        => 'autosubmit',
                'required'     => true,
                'value'        => $populatedHome,
                'multiOptions' => array_merge([self::CREATE_NEW_HOME => self::CREATE_NEW_HOME], $homes),
                'label'        => t('Assign to Home'),
                'description'  => t('Select a dashboard home you want to move the dashboard to.'),
            ]);

            if (empty($homes) || $this->getPopulatedValue('home') === self::CREATE_NEW_HOME) {
                $this->addElement('text', 'new_home', [
                    'required'    => true,
                    'label'       => t('Dashboard Home'),
                    'placeholder' => t('Enter dashboard home title'),
                    'description' => t('Enter a title for the new dashboard home.'),
                ]);
            }
        }

        $formControls = $this->createFormControls();
        $formControls->add([
            $this->registerSubmitButton($buttonLabel),
            $this->createRemoveButton($removeTargetUrl, $removeButtonLabel),
            $this->createCancelButton()
        ]);

        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/edit-pane') {
            $orgHome = $this->dashboard->getEntry($requestUrl->getParam('home'));

            $selectedHome = $this->getPopulatedValue('home');
            if (! $selectedHome || $selectedHome === self::CREATE_NEW_HOME) {
                $selectedHome = $this->getPopulatedValue('new_home');
            }

            $currentHome = new DashboardHome($selectedHome);
            if ($this->dashboard->hasEntry($currentHome->getName())) {
                /** @var DashboardHome $currentHome */
                $currentHome = clone $this->dashboard->getEntry($currentHome->getName());
                $activeHome = $this->dashboard->getActiveHome();
                if ($currentHome->getName() !== $activeHome->getName()) {
                    $currentHome->loadDashboardEntries();
                }
            }

            $currentPane = clone $orgHome->getEntry($this->getValue('org_name'));
            $currentPane
                ->setHome($currentHome)
                ->setTitle($this->getValue('title'));

            if ($orgHome->getName() !== $currentHome->getName() && $currentHome->hasEntry($currentPane->getName())) {
                Notification::error(sprintf(
                    t('Failed to move dashboard "%s": Dashbaord pane already exists within the "%s" dashboard home'),
                    $currentPane->getTitle(),
                    $currentHome->getTitle()
                ));

                return;
            }

            if ($currentHome->getName() === $orgHome->getName()) {
                // There is no dashboard home diff so clear all the dashboard pane
                // of the org home
                $orgHome->setEntries([]);
            }

            $conn = Dashboard::getConn();
            $conn->beginTransaction();

            try {
                $this->dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($currentPane, $orgHome);
                // We have to update all the dashlet ids too sha1(username + home + pane + dashlet)
                $currentPane->manageEntry($currentPane->getEntries());

                $conn->commitTransaction();
            } catch (\Exception $err) {
                Logger::error($err);
                $conn->rollBackTransaction();
            }

            Notification::success(sprintf(t('Updated dashboard pane "%s" successfully'), $currentPane->getTitle()));
        } else {
            $home = $this->dashboard->getActiveHome();
            $home->setTitle($this->getValue('title'));

            $this->dashboard->manageEntry($home);
            Notification::success(sprintf(t('Updated dashboard home "%s" successfully'), $home->getTitle()));
        }
    }

    public function load(BaseDashboard $dashboard)
    {
        $this->populate([
            'org_title' => $dashboard->getTitle(),
            'title'     => $dashboard->getTitle(),
            'org_name'  => $dashboard->getName()
        ]);
    }
}
