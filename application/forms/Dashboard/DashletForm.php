<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Exception;
use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Util\DBUtils;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;

class DashletForm extends SetupNewDashboardForm
{
    protected function init(): void
    {
        parent::init();

        $this->setAction((string) $this->requestUrl);
    }

    public function load(BaseDashboard $dashboard): void
    {
        /** @var Dashlet $dashboard */
        $this->populate([
            'org_home'    => $this->requestUrl->getParam('home'),
            'org_pane'    => $dashboard->getPane()->getName(),
            'org_dashlet' => $dashboard->getName(),
            'dashlet'     => $dashboard->getTitle(),
            'url'         => $dashboard->getUrl()->getRelativeUrl(),
            'description' => $dashboard->getDescription(),
        ]);
    }

    protected function assembleNextPageDashboardPart()
    {
        $homes = $this->dashboard->getEntryKeyTitleArr();
        $activeHome = $this->dashboard->getActiveHome();
        $currentHome = $this->requestUrl->getParam('home', reset($homes));
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
                $panes = $activeHome->loadDashboardEntries()->getEntryKeyTitleArr();
            }
        } elseif ($this->dashboard->hasEntry($populatedHome)) {
            $this->dashboard->loadDashboardEntries($populatedHome);

            $panes = $this->dashboard->getEntry($populatedHome)->getEntryKeyTitleArr();
        }

        $this->addElement('hidden', 'org_pane', ['required' => false]);
        $this->addElement('hidden', 'org_home', ['required' => false]);
        $this->addElement('hidden', 'org_dashlet', ['required' => false]);

        if ($this->isUpdating()) {
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
            'description'  => t('Select a dashboard home you want to add the pane to.')
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

        $populatedPane = $this->getPopulatedValue('pane', $this->requestUrl->getParam('pane'));
        if (! $populatedPane || ! in_array($populatedPane, $panes)) {
            $populatedPane = reset($panes);
        }

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
    }

    protected function assemble()
    {
        if ($this->isUpdating() || $this->getPopulatedValue('btn_next') || $this->hasBeenSent()) {
            $this->assembleNextPage();

            if ($this->isUpdating()) {
                $targetUrl = (clone $this->requestUrl)->setPath(Dashboard::BASE_ROUTE . '/remove-dashlet');
                $removeButton = $this->createRemoveButton($targetUrl, t('Remove Dashlet'));

                $formControls = $this->createFormControls();
                $formControls->addHtml(
                    $this->registerSubmitButton(t('Update Dashlet')),
                    $removeButton,
                    $this->createCancelButton()
                );

                $this->addHtml($formControls);
            } else {
                $formControls = $this->createFormControls();
                $formControls->addHtml(
                    $this->registerSubmitButton(t('Add to Dashboard')),
                    $this->createCancelButton()
                );

                $this->addHtml($formControls);
            }
        } else {
            // Just setup the initial view of the modal view
            parent::assemble();
        }
    }

    protected function onSuccess()
    {
        $conn = DBUtils::getConn();
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

        $currentHome = new DashboardHome($selectedHome);
        $currentPane = new Pane($selectedPane);

        if ($dashboard->hasEntry($currentHome->getName())) {
            $currentHome = clone $dashboard->getEntry($currentHome->getName());
            $activatePane = $currentHome->hasEntry($selectedPane)
            && $currentHome->getActivePane()->getName() !== $selectedPane
                ? $selectedPane
                : null;

            if ($currentHome->getName() !== $dashboard->getActiveHome()->getName() || $activatePane) {
                $currentHome->loadDashboardEntries($activatePane);
            }

            if ($currentHome->hasEntry($currentPane->getName())) {
                $currentPane = clone $currentHome->getActivePane();
            }
        }

        if (! $this->isUpdating()) {
            $customDashlet = null;
            $countDashlets = $currentPane->countEntries();

            if (($dashlet = $this->getPopulatedValue('dashlet')) && ($url = $this->getPopulatedValue('url'))) {
                $customDashlet = new Dashlet($dashlet, $url, $currentPane);
                $customDashlet->setDescription($this->getPopulatedValue('description'));

                if ($currentPane->hasEntry($customDashlet->getName()) || $this->customDashletAlreadyExists) {
                    if ($this->customDashletAlreadyExists) {
                        $message = sprintf(
                            t('Failed to create custom Dashlet! The selected module Dashlet(s) contains Dashlet "%s"'),
                            $customDashlet->getTitle()
                        );
                    } else {
                        $message = sprintf(
                            t('Dashboard pane "%s" has already a Dashlet called "%s"'),
                            $currentPane->getTitle(),
                            $customDashlet->getTitle()
                        );
                    }

                    Notification::error($message);

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

                $this->dumpArbitaryDashlets(false);
                // Avoid the hassle of iterating through the module dashlets each time to check if exits,
                // even though the current pane doesn't have any entries
                if (! $this->getPopulatedValue('new_pane') && $currentPane->hasEntries()) {
                    foreach ($this->moduleDashlets as $_ => $dashlets) {
                        /** @var Dashlet $dashlet */
                        foreach ($dashlets as $dashlet) {
                            if ($currentPane->hasEntry($dashlet->getName())) {
                                Notification::error(sprintf(
                                    t('Pane "%s" has already a Dashlet called "%s"'),
                                    $currentPane->getTitle(),
                                    $dashlet->getTitle()
                                ));

                                return;
                            }

                            $currentPane->manageEntry($dashlet);
                        }
                    }
                } else {
                    $currentPane->manageEntry($this->moduleDashlets);
                }

                $conn->commitTransaction();
            } catch (Exception $err) {
                $conn->rollBackTransaction();

                Logger::error('Unable to add new Dashlet(s). An unexpected error occurred: %s', $err);

                Notification::error(
                    t('Failed to successfully add new Dashlet(s). Please check the logs for details!')
                );

                return;
            }

            $countDashlets = $currentPane->countEntries() - $countDashlets;
            $dashlet = $currentPane->getEntries();
            $dashlet = end($dashlet);

            $this->requestSucceeded = true;

            Notification::success(sprintf(
                tp('Added Dashlet "%s" successfully', 'Added %d Dashlets successfully', $countDashlets),
                $countDashlets === 1 ? $dashlet->getTitle() : $countDashlets
            ));
        } else {
            $orgHome = $dashboard->getEntry($this->getValue('org_home'));
            $orgPane = $orgHome->getEntry($this->getValue('org_pane'));
            if ($orgHome->getActivePane()->getName() !== $orgPane->getName()) {
                $orgHome->loadDashboardEntries($orgPane->getName());

                $orgPane = $orgHome->getActivePane();
            }

            $orgDashlet = $orgPane->getEntry($this->getValue('org_dashlet'));
            $currentPane->setHome($currentHome);

            if (! $currentHome->hasEntry($currentPane->getName())) {
                /**
                 * When the user is going to move the Dashlet into a new pane in a different home, it might be possible
                 * that the original Home contains a Pane with the same name and in {@see DashboardHome::manageEntry()}
                 * this would be interpreted as if we wanted to move the Pane from the original Home. Therefore, we need
                 * to explicitly reset all dashboard entries of the org Home here.
                 */
                $orgHome->setEntries([]);
            }

            /** @var Dashlet $currentDashlet */
            $currentDashlet = clone $orgDashlet;
            $currentDashlet
                ->setPane($currentPane)
                ->setUrl($this->getValue('url'))
                ->setTitle($this->getValue('dashlet'))
                ->setDescription($this->getValue('description'));

            if ($currentPane->hasEntry($currentDashlet->getName())
                && (
                    $currentHome->getName() !== $orgHome->getName()
                    || $orgPane->getName() !== $currentPane->getName()
                )
            ) {
                Notification::error(sprintf(
                    t('Failed to move a Dashlet: Pane "%s" has already a Dashlet called "%s"'),
                    $currentPane->getTitle(),
                    $currentDashlet->getTitle()
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

            $conn->beginTransaction();

            try {
                $dashboard->manageEntry($currentHome);
                $currentHome->manageEntry($currentPane, $orgHome);
                $currentPane->manageEntry($currentDashlet, $orgPane);

                $conn->commitTransaction();
            } catch (Exception $err) {
                $conn->rollBackTransaction();

                Logger::error(
                    'Unable to update Dashlet "%s". An unexpected error occurred: %s',
                    $currentDashlet->getTitle(),
                    $err
                );

                Notification::error(t('Failed to update the Dashlet. Please check the logs for details!'));

                return;
            }

            $this->requestSucceeded = true;

            Notification::success(sprintf(t('Updated Dashlet "%s" successfully'), $currentDashlet->getTitle()));
        }
    }
}
