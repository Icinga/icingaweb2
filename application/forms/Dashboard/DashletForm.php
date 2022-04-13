<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Exception;
use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

class DashletForm extends BaseSetupDashboard
{
    protected function assembleNextPage()
    {
        if (! $this->getPopulatedValue('btn_next')) {
            return;
        }

        $this->dumpArbitaryDashlets();
        $this->assembleNexPageDashletPart();
    }

    protected function assemble()
    {
        if ($this->isUpdatingADashlet() || $this->getPopulatedValue('btn_next')) {
            $requestUrl = Url::fromRequest();

            $homes = $this->dashboard->getEntryKeyTitleArr();
            $activeHome = $this->dashboard->getActiveHome();
            $currentHome = $requestUrl->getParam('home', reset($homes));
            $populatedHome = $this->getPopulatedValue('home', $currentHome);

            $panes = [];
            if ($currentHome === $populatedHome && $populatedHome !== self::CREATE_NEW_HOME) {
                if (! $currentHome || ! $activeHome) {
                    // Home param isn't passed through, so let's try to load based on the first home
                    $firstHome = $this->dashboard->rewindEntries();
                    if ($firstHome) {
                        $this->dashboard->loadDashboardEntries($firstHome->getName());

                        $panes = $firstHome->getEntryKeyTitleArr();
                    }
                } else {
                    $panes = $activeHome->getEntryKeyTitleArr();
                }
            } elseif ($this->dashboard->hasEntry($populatedHome)) {
                $this->dashboard->loadDashboardEntries($populatedHome);

                $panes = $this->dashboard->getActiveHome()->getEntryKeyTitleArr();
            }

            $this->addElement('hidden', 'org_pane', ['required' => false]);
            $this->addElement('hidden', 'org_home', ['required' => false]);
            $this->addElement('hidden', 'org_dashlet', ['required' => false]);

            if ($this->isUpdatingADashlet()) {
                $this->assembleDashletElements();

                $this->addHtml(new HtmlElement('hr'));
            }

            $this->addElement('select', 'home', [
                'class'        => 'autosubmit',
                'required'     => true,
                'disabled'     => empty($homes) ?: null,
                'value'        => $populatedHome,
                'multiOptions' => array_merge([self::CREATE_NEW_HOME => self::CREATE_NEW_HOME], $homes),
                'label'        => t('Select Home'),
                'description'  => t('Select a dashboard home you want to add the dashboard pane to.')
            ]);

            if (empty($homes) || $populatedHome === self::CREATE_NEW_HOME) {
                $this->addElement('text', 'new_home', [
                    'required'    => true,
                    'label'       => t('Home Title'),
                    'placeholder' => t('Enter dashboard home title'),
                    'description' => t('Enter a title for the new dashboard home.')
                ]);
            }

            $populatedPane = $this->getPopulatedValue('pane');
            // Pane element's values are depending on the home element's value
            if ($populatedPane !== self::CREATE_NEW_PANE && ! in_array($populatedPane, $panes)) {
                $this->clearPopulatedValue('pane');
            }

            $populatedPane = $this->getPopulatedValue('pane', $requestUrl->getParam('pane', reset($panes)));
            $disable = empty($panes) || $populatedHome === self::CREATE_NEW_HOME;
            $this->addElement('select', 'pane', [
                'class'        => 'autosubmit',
                'required'     => true,
                'disabled'     => $disable ?: null,
                'value'        => ! $disable ? $populatedPane : self::CREATE_NEW_PANE,
                'multiOptions' => array_merge([self::CREATE_NEW_PANE => self::CREATE_NEW_PANE], $panes),
                'label'        => t('Select Dashboard'),
                'description'  => t('Select a dashboard you want to add the dashlet to.'),
            ]);

            if ($disable || $this->getPopulatedValue('pane') === self::CREATE_NEW_PANE) {
                $this->addElement('text', 'new_pane', [
                    'required'    => true,
                    'label'       => t('Dashboard Title'),
                    'placeholder' => t('Enter dashboard title'),
                    'description' => t('Enter a title for the new dashboard.'),
                ]);
            }

            if ($this->isUpdatingADashlet()) {
                $targetUrl = (clone $requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-dashlet');
                $removeButton = $this->createRemoveButton($targetUrl, t('Remove Dashlet'));

                $formControls = $this->createFormControls();
                $formControls->add([
                    $this->registerSubmitButton(t('Add to Dashboard')),
                    $removeButton,
                    $this->createCancelButton()
                ]);

                $this->addHtml($formControls);
            } else {
                $this->assembleNextPage();

                $formControls = $this->createFormControls();
                $formControls->add([
                    $this->registerSubmitButton(t('Add to Dashboard')),
                    $this->createCancelButton()
                ]);

                $this->addHtml($formControls);
            }
        } else {
            parent::assemble();
        }
    }

    protected function onSuccess()
    {
        $conn = Dashboard::getConn();
        $dashboard = $this->dashboard;

        $selectedHome = $this->getPopulatedValue('home');
        if (! $selectedHome || $selectedHome === self::CREATE_NEW_HOME) {
            $selectedHome = $this->getPopulatedValue('new_home');
        }

        $selectedPane = $this->getPopulatedValue('pane');
        // If "pane" element is disabled, there will be no populated value for it
        if (! $selectedPane || $selectedPane === self::CREATE_NEW_PANE) {
            $selectedPane = $this->getPopulatedValue('new_pane');
        }

        if (! $this->isUpdatingADashlet()) {
            $currentHome = new DashboardHome($selectedHome);
            if ($dashboard->hasEntry($currentHome->getName())) {
                $currentHome = clone $dashboard->getEntry($currentHome->getName());
                if ($currentHome->getName() !== $dashboard->getActiveHome()->getName()) {
                    $currentHome->setActive();
                    $currentHome->loadDashboardEntries();
                }
            }

            $currentPane = new Pane($selectedPane);
            if ($currentHome->hasEntry($currentPane->getName())) {
                $currentPane = clone $currentHome->getEntry($currentPane->getName());
            }

            $customDashlet = null;
            if (($dashlet = $this->getPopulatedValue('dashlet')) && ($url = $this->getPopulatedValue('url'))) {
                $customDashlet = new Dashlet($dashlet, $url, $currentPane);

                if ($currentPane->hasEntry($customDashlet->getName()) || $this->duplicateCustomDashlet) {
                    Notification::error(sprintf(
                        t('Dashlet "%s" already exists within the "%s" dashboard pane'),
                        $customDashlet->getTitle(),
                        $currentPane->getTitle()
                    ));

                    return;
                }
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($currentPane);

                if ($customDashlet) {
                    $currentPane->manageEntry($customDashlet);
                }

                // Avoid the hassle of iterating through the module dashlets each time to check if exits,
                // even though the current pane doesn't have any entries
                if (! $this->getPopulatedValue('new_pane') && $currentPane->hasEntries()) {
                    $this->dumpArbitaryDashlets(false);

                    foreach (self::$moduleDashlets as $_ => $dashlets) {
                        /** @var Dashlet $dashlet */
                        foreach ($dashlets as $dashlet) {
                            if ($currentPane->hasEntry($dashlet->getName()) || $this->duplicateCustomDashlet) {
                                Notification::error(sprintf(
                                    t('Dashlet "%s" already exists within the "%s" dashboard pane'),
                                    $dashlet->getTitle(),
                                    $currentPane->getTitle()
                                ));

                                return;
                            }

                            $currentPane->manageEntry($dashlet);
                        }
                    }
                } else {
                    $currentPane->manageEntry(self::$moduleDashlets);
                }

                $conn->commitTransaction();
            } catch (Exception $err) {
                Logger::error($err);
                $conn->rollBackTransaction();

                throw $err;
            }

            Notification::success(t('Created dashlet(s) successfully'));
        } else {
            $orgHome = $dashboard->getEntry($this->getValue('org_home'));
            $orgPane = $orgHome->getEntry($this->getValue('org_pane'));
            $orgDashlet = $orgPane->getEntry($this->getValue('org_dashlet'));

            $currentHome = new DashboardHome($selectedHome);
            if ($dashboard->hasEntry($currentHome->getName())) {
                $currentHome = clone $dashboard->getEntry($currentHome->getName());
                $activeHome = $dashboard->getActiveHome();
                if ($currentHome->getName() !== $activeHome->getName()) {
                    $currentHome->setActive();
                    $currentHome->loadDashboardEntries();
                }
            }

            $currentPane = new Pane($selectedPane);
            if ($currentHome->hasEntry($currentPane->getName())) {
                $currentPane = clone $currentHome->getEntry($currentPane->getName());
            }

            $currentPane->setHome($currentHome);
            // When the user wishes to create a new dashboard pane, we have to explicitly reset the dashboard panes
            // of the original home, so that it isn't considered as we want to move the pane even though it isn't
            // supposed to when the original home contains a dashboard with the same name
            // @see DashboardHome::managePanes() for details
            $selectedPane = $this->getPopulatedValue('pane');
            if ((! $selectedPane || $selectedPane === self::CREATE_NEW_PANE)
                && ! $currentHome->hasEntry($currentPane->getName())) {
                $orgHome->setEntries([]);
            }

            $currentDashlet = clone $orgDashlet;
            $currentDashlet
                ->setPane($currentPane)
                ->setUrl($this->getValue('url'))
                ->setTitle($this->getValue('dashlet'));

            if ($orgPane->getName() !== $currentPane->getName()
                && $currentPane->hasEntry($currentDashlet->getName())) {
                Notification::error(sprintf(
                    t('Failed to move dashlet "%s": Dashlet already exists within the "%s" dashboard pane'),
                    $currentDashlet->getTitle(),
                    $currentPane->getTitle()
                ));

                return;
            }

            $paneDiff = array_filter(array_diff_assoc($currentPane->toArray(), $orgPane->toArray()));
            $dashletDiff = array_filter(
                array_diff_assoc($currentDashlet->toArray(), $orgDashlet->toArray()),
                function ($val) {
                    return $val !== null;
                }
            );

            // Prevent meaningless updates when there weren't any changes,
            // e.g. when the user just presses the update button without changing anything
            if (empty($dashletDiff) && empty($paneDiff)) {
                return;
            }

            if (empty($paneDiff)) {
                // No dashboard diff means the dashlet is still in the same pane, so just
                // reset the dashlets of the original pane
                $orgPane->setEntries([]);
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($currentPane, $orgHome);
                $currentPane->manageEntry($currentDashlet, $orgPane);

                $conn->commitTransaction();
            } catch (Exception $err) {
                Logger::error($err);
                $conn->rollBackTransaction();

                throw $err;
            }

            Notification::success(sprintf(t('Updated dashlet "%s" successfully'), $currentDashlet->getTitle()));
        }
    }

    public function load(BaseDashboard $dashboard)
    {
        $home = Url::fromRequest()->getParam('home');
        /** @var Dashlet $dashboard */
        $this->populate(array(
            'org_home'    => $home,
            'org_pane'    => $dashboard->getPane()->getName(),
            'org_dashlet' => $dashboard->getName(),
            'dashlet'     => $dashboard->getTitle(),
            'url'         => $dashboard->getUrl()->getRelativeUrl()
        ));
    }
}
