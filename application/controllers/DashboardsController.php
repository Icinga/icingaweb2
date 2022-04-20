<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Forms\Dashboard\HomePaneForm;
use Icinga\Forms\Dashboard\NewHomePaneForm;
use Icinga\Forms\Dashboard\RemoveDashletForm;
use Icinga\Forms\Dashboard\RemoveHomePaneForm;
use Icinga\Forms\Dashboard\SetupNewDashboardForm;
use Icinga\Forms\Dashboard\WelcomeForm;
use Icinga\Util\Json;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Dashboard\Settings;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tabextension\DashboardSettings;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class DashboardsController extends CompatController
{
    /** @var Dashboard */
    protected $dashboard;

    public function init()
    {
        parent::init();

        $this->dashboard = new Dashboard();
        $this->dashboard->setUser($this->Auth()->getUser());
        $this->dashboard->setTabs($this->getTabs());
    }

    public function indexAction()
    {
        $this->dashboard->load(DashboardHome::DEFAULT_HOME);

        $this->createTabs();

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome || ! $activeHome->hasEntries()) {
            $this->addTitleTab(t('Welcome'));

            // Setup dashboard introduction form
            $welcomeForm = new WelcomeForm($this->dashboard);
            $welcomeForm->on(WelcomeForm::ON_SUCCESS, function () use ($welcomeForm) {
                $this->redirectNow($welcomeForm->getRedirectUrl());
            })->handleRequest(ServerRequest::fromGlobals());

            $this->content->getAttributes()->add('class', 'welcome-view');
            $this->dashboard->setWelcomeForm($welcomeForm);
        } else {
            $pane = $this->getParam('pane');
            if (! $pane) {
                $pane = $this->dashboard->getActivePane()->getName();
            }

            $this->dashboard->activate($pane);
        }

        $this->addContent($this->dashboard);
    }

    /**
     * Display all the dashboards assigned to a Home set in the `home` request param
     *
     * If no pane param is submitted, the default pane is displayed (usually the first one)
     */
    public function homeAction()
    {
        $this->dashboard->load($this->params->getRequired('home'));

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome->getEntries()) {
            $this->addTitleTab($activeHome->getTitle());
        }

        // Not to render the cog icon before the above tab
        $this->createTabs();

        if ($activeHome->hasEntries()) {
            $pane = $this->getParam('pane');
            if (! $pane) {
                $pane = $this->dashboard->getActivePane()->getName();
            }

            $this->dashboard->activate($pane);
        }

        $this->addContent($this->dashboard);
    }

    public function newHomeAction()
    {
        $this->setTitle(t('Add new Dashboard Home'));
        $this->dashboard->load();

        $paneForm = (new NewHomePaneForm($this->dashboard))
            ->on(NewHomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($paneForm);
    }

    public function editHomeAction()
    {
        $this->setTitle(t('Update Home'));
        $this->dashboard->load();

        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        $homeForm = (new HomePaneForm($this->dashboard))
            ->on(HomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());
        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $this->setTitle(t('Remove Home'));
        $this->dashboard->load();

        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        $homeForm = (new RemoveHomePaneForm($this->dashboard))
            ->on(RemoveHomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($homeForm);
    }

    public function newPaneAction()
    {
        $this->setTitle(t('Add new Dashboard'));
        $this->dashboard->load();

        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        $paneForm = (new NewHomePaneForm($this->dashboard))
            ->on(NewHomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($paneForm);
    }

    public function editPaneAction()
    {
        $this->setTitle(t('Update Pane'));
        $this->dashboard->load();

        $pane = $this->params->getRequired('pane');
        $home = $this->params->getRequired('home');

        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        if (! $this->dashboard->getActiveHome()->hasEntry($pane)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $pane));
        }

        $paneForm = (new HomePaneForm($this->dashboard))
            ->on(HomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getEntry($pane));

        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $this->setTitle(t('Remove Pane'));
        $this->dashboard->load();

        $home = $this->params->getRequired('home');
        $paneParam = $this->params->getRequired('pane');

        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        if (! $this->dashboard->getActiveHome()->hasEntry($paneParam)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $paneParam));
        }

        $paneForm = new RemoveHomePaneForm($this->dashboard);
        $paneForm->populate(['org_name' => $paneParam]);
        $paneForm->on(RemoveHomePaneForm::ON_SUCCESS, function () {
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($paneForm);
    }

    public function newDashletAction()
    {
        if (isset($this->getRequest()->getPost()['btn_next'])) {
            $this->setTitle(t('Add Dashlet To Dashboard'));
        } else {
            $this->setTitle(t('Select Dashlets'));
        }

        $this->dashboard->load();

        $home = $this->params->getRequired('home');
        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->populate($this->getRequest()->getPost());
        $dashletForm->on(DashletForm::ON_SUCCESS, function () {
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($dashletForm);
    }

    public function editDashletAction()
    {
        $this->setTitle(t('Edit Dashlet'));
        $this->dashboard->load();

        $pane = $this->validateDashletParams();
        $dashlet = $pane->getEntry($this->getParam('dashlet'));

        $dashletForm = (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->getElement('submit')->setLabel(t('Update Dashlet'));

        $dashletForm->load($dashlet);
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $this->setTitle(t('Remove Dashlet'));
        $this->dashboard->load();

        $this->validateDashletParams();

        $removeForm = (new RemoveDashletForm($this->dashboard))
            ->on(RemoveDashletForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($removeForm);
    }

    /**
     * Handles all widgets drag and drop requests
     */
    public function reorderWidgetsAction()
    {
        $this->assertHttpMethod('post');
        $dashboards = $this->getRequest()->getPost();
        if (! isset($dashboards['dashboardData'])) {
            $this->httpBadRequest(t('Invalid request data'));
        }

        $dashboards = Json::decode($dashboards['dashboardData'], true);
        $originals = $dashboards['originals'];
        unset($dashboards['originals']);

        $this->dashboard->load();

        $orgHome = null;
        $orgPane = null;
        if ($originals && isset($originals['originalHome'])) {
            /** @var DashboardHome $orgHome */
            $orgHome = $this->dashboard->getEntry($originals['originalHome']);
            $orgHome->setActive()->loadDashboardEntries();

            if (isset($originals['originalPane'])) {
                $orgPane = $orgHome->getEntry($originals['originalPane']);
                $orgHome->setEntries([$orgPane->getName() => $orgPane]);
            }
        }

        $duplicatedError = false;
        foreach ($dashboards as $home => $value) {
            if (! $this->dashboard->hasEntry($home)) {
                Notification::error(sprintf(t('Dashboard home "%s" not found'), $home));
                break;
            }

            $home = $this->dashboard->getEntry($home);
            /** @var DashboardHome $home */
            if (! is_array($value)) {
                $this->dashboard->reorderWidget($home, (int) $value);

                Notification::success(sprintf(t('Updated dashboard home "%s" successfully'), $home->getTitle()));
                break;
            }

            $home->setActive()->loadDashboardEntries();
            foreach ($value as $pane => $indexOrValues) {
                if (! $home->hasEntry($pane) && (! $orgHome || ! $orgHome->hasEntry($pane))) {
                    Notification::error(sprintf(t('Dashboard pane "%s" not found'), $pane));
                    break;
                }

                $pane = $home->hasEntry($pane) ? $home->getEntry($pane) : $orgHome->getEntry($pane);
                /** @var Pane $pane */
                if (! is_array($indexOrValues)) {
                    if ($orgHome && $orgHome->hasEntry($pane->getName()) && $home->hasEntry($pane->getName())) {
                        Notification::error(sprintf(
                            t('Dashboard "%s" already exists within "%s" home'),
                            $pane->getTitle(),
                            $home->getTitle()
                        ));

                        $duplicatedError = true;
                        break;
                    }

                    // Perform DB updates
                    $home->reorderWidget($pane, (int) $indexOrValues, $orgHome);
                    if ($orgHome) {
                        // In order to properly update the dashlets id (user + home + pane + dashlet)
                        $pane->manageEntry($pane->getEntries());
                    }

                    Notification::success(sprintf(
                        t('%s dashboard pane "%s" successfully'),
                        $orgHome ? 'Moved' : 'Updated',
                        $pane->getTitle()
                    ));
                    break;
                }

                foreach ($indexOrValues as $dashlet => $index) {
                    if (! $pane->hasEntry($dashlet) && (! $orgPane || ! $orgPane->hasEntry($dashlet))) {
                        Notification::error(sprintf(t('Dashlet "%s" not found'), $dashlet));
                        break;
                    }

                    if ($orgPane && $orgPane->hasEntry($dashlet) && $pane->hasEntry($dashlet)) {
                        Notification::error(sprintf(
                            t('Dashlet "%s" already exists within "%s" dashboard pane'),
                            $dashlet,
                            $pane->getTitle()
                        ));

                        $duplicatedError = true;
                        break;
                    }

                    $dashlet = $pane->hasEntry($dashlet) ? $pane->getEntry($dashlet) : $orgPane->getEntry($dashlet);
                    $pane->reorderWidget($dashlet, (int) $index, $orgPane);

                    Notification::success(sprintf(
                        t('%s dashlet "%s" successfully'),
                        $orgPane ? 'Moved' : 'Updated',
                        $dashlet->getTitle()
                    ));
                }
            }
        }

        if ($duplicatedError) {
            // Even though the drop action couldn't be performed successfully from our server, Sortable JS has
            // already dropped the draggable element though, so we need to redirect here to undo it.
            $this->redirectNow(Dashboard::BASE_ROUTE . '/settings');
        }

        $this->createTabs();
        $this->dashboard->activate('dashboard_settings');
        $this->sendMultipartUpdate();
    }

    /**
     * Provides a mini wizard which guides a new user through the dashboard creation
     * process and helps them get a first impression of Icinga Web 2.
     */
    public function setupDashboardAction()
    {
        if (isset($this->getRequest()->getPost()['btn_next'])) {
            // Set compact view to prevent the controls from being
            // rendered in the modal view when redirecting
            $this->view->compact = true;

            $this->setTitle(t('Configure Dashlets'));
        } else {
            $this->setTitle(t('Add Dashlet'));
        }

        $this->dashboard->load();

        $setupForm = new SetupNewDashboardForm($this->dashboard);
        $setupForm->on(SetupNewDashboardForm::ON_SUCCESS, function () use ($setupForm) {
            $this->redirectNow($setupForm->getRedirectUrl());
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($setupForm);
    }

    public function settingsAction()
    {
        $this->dashboard->load();
        $this->createTabs();

        $activeHome = $this->dashboard->getActiveHome();
        // We can't grant access the user to the dashboard manager if there aren't any dashboards to manage
        if (! $activeHome || (! $activeHome->hasEntries() && count($this->dashboard->getEntries()) === 1)) {
            $this->redirectNow(Dashboard::BASE_ROUTE);
        }

        $this->dashboard->activate('dashboard_settings');

        $this->addControl(new ActionLink(
            t('Add new Home'),
            Url::fromPath(Dashboard::BASE_ROUTE . '/new-home'),
            'plus',
            [
                'class'               => 'add-home',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));

        $this->content->getAttributes()->add('class', 'dashboard-manager');
        $this->controls->getAttributes()->add('class', 'dashboard-manager-controls');

        $this->addContent(new Settings($this->dashboard));
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        $tabs = $this->dashboard->getTabs();
        $activeHome = $this->dashboard->getActiveHome();
        if ($activeHome && ($activeHome->getName() !== DashboardHome::DEFAULT_HOME || $activeHome->hasEntries())) {
            $tabs->extend(new DashboardSettings());
        }

        return $tabs;
    }

    private function validateDashletParams()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');
        $dashlet = $this->params->getRequired('dashlet');

        if (! $this->dashboard->hasEntry($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        // TODO: Shouldn't be necessary. load() should get the name already and
        //       the form should otherwise ensure it has the required data
        $this->dashboard->activateHome($this->dashboard->getEntry($home));

        if (! $this->dashboard->getActiveHome()->hasEntry($pane)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $pane));
        }

        $pane = $this->dashboard->getActiveHome()->getEntry($pane);
        if (! $pane->hasEntry($dashlet)) {
            $this->httpNotFound(sprintf(t('Dashlet "%s" not found'), $dashlet));
        }

        return $pane;
    }
}
