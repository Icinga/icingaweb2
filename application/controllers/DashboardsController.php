<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Forms\Dashboard\HomeForm;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Forms\Dashboard\PaneForm;
use Icinga\Forms\Dashboard\RemoveHomeForm;
use Icinga\Forms\Dashboard\RemoveDashletForm;
use Icinga\Forms\Dashboard\RemovePaneForm;
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
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Handles all kind of dashboard request actions
 *
 * See {@see Dashboard} for more information about dashboards
 */
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
        $pane = $this->getParam('pane');

        // If we don't load all dashboard homes here, the cog icon won't be rendered in the dashboard tabs
        $this->dashboard->load(DashboardHome::DEFAULT_HOME, $pane, true);

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome || ! $activeHome->hasEntries()) {
            $this->addTitleTab(t('Welcome'));

            // Setup dashboard introduction form
            $welcomeForm = new WelcomeForm($this->dashboard);
            $welcomeForm->on(WelcomeForm::ON_SUCCESS, function () use ($welcomeForm) {
                $this->redirectNow($welcomeForm->getRedirectUrl());
            })->handleRequest($this->getServerRequest());

            $this->content->getAttributes()->add('class', 'welcome-view');
            $this->dashboard->addHtml($welcomeForm);
        }

        $this->createTabs();

        $this->addContent($this->dashboard);
    }

    /**
     * Display all the dashboards assigned to a Home set in the `home` request param
     *
     * If no pane param is submitted, the default pane is displayed (usually the first one)
     */
    public function homeAction()
    {
        $this->dashboard->load($this->params->getRequired('home'), $this->getParam('pane'));

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome->getEntries()) {
            $this->addTitleTab($activeHome->getTitle());
        }

        // Not to render the cog icon before the above tab
        $this->createTabs();

        $this->addContent($this->dashboard);
    }

    public function newHomeAction()
    {
        $this->dashboard->load();

        $homeForm = new HomeForm($this->dashboard);
        $homeForm->on(HomeForm::ON_SUCCESS, function () use ($homeForm) {
            $this->getResponse()->setHeader('X-Icinga-Extra-Updates', '#menu');

            $params = ['home' => $homeForm->getPopulatedValue('title')];

            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
        })->handleRequest($this->getServerRequest());

        $this->setTitle(t('Add new Dashboard Home'));
        $this->addContent($homeForm);
    }

    public function editHomeAction()
    {
        $home = $this->params->getRequired('home');

        $this->dashboard->load($home);

        $homeForm = (new HomeForm($this->dashboard))
            ->on(HomeForm::ON_SUCCESS, function () {
                $params = clone $this->params;
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
            })
            ->handleRequest($this->getServerRequest());

        $homeForm->load($this->dashboard->getActiveHome());

        $this->setTitle(t('Update Home'));
        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $home = $this->params->getRequired('home');

        $this->dashboard->load($home);

        $homeForm = (new RemoveHomeForm($this->dashboard))
            ->on(RemoveHomeForm::ON_SUCCESS, function () {
                $this->getResponse()->setHeader('X-Icinga-Extra-Updates', '#menu');

                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings'));
            })
            ->handleRequest($this->getServerRequest());

        $this->setTitle(t('Remove Home'));
        $this->addContent($homeForm);
    }

    public function newPaneAction()
    {
        $home = $this->params->getRequired('home');

        $this->dashboard->load($home, null, true);

        $paneForm = (new PaneForm($this->dashboard))
            ->on(PaneForm::ON_SUCCESS, function () {
                $params = $this->params->without('pane');
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
            })
            ->handleRequest($this->getServerRequest());

        $this->setTitle(t('Add new Dashboard'));
        $this->addContent($paneForm);
    }

    public function editPaneAction()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');

        $this->dashboard->load($home, $pane, true);

        if (! $this->dashboard->getActiveHome()->hasEntry($pane)) {
            $this->httpNotFound(t('Pane "%s" not found'), $pane);
        }

        $paneForm = (new PaneForm($this->dashboard))
            ->on(PaneForm::ON_SUCCESS, function () {
                $params = $this->params->without('pane');
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
            })
            ->handleRequest($this->getServerRequest());

        $paneForm->load($this->dashboard->getActiveHome()->getEntry($pane));

        $this->setTitle(t('Update Pane'));
        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $home = $this->params->getRequired('home');
        $paneParam = $this->params->getRequired('pane');

        $this->dashboard->load($home, $paneParam);

        if (! $this->dashboard->getActiveHome()->hasEntry($paneParam)) {
            $this->httpNotFound(t('Pane "%s" not found'), $paneParam);
        }

        $paneForm = new RemovePaneForm($this->dashboard);
        $paneForm->populate(['org_name' => $paneParam]);
        $paneForm->on(RemovePaneForm::ON_SUCCESS, function () {
            $params = $this->params->without('pane');
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
        })->handleRequest($this->getServerRequest());

        $this->setTitle(t('Remove Pane'));
        $this->addContent($paneForm);
    }

    public function newDashletAction()
    {
        $home = $this->params->getRequired('home');

        $this->dashboard->load($home, $this->getParam('pane'), true);

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->populate($this->getRequest()->getPost());
        $dashletForm->on(DashletForm::ON_SUCCESS, function () {
            $params = $this->params->without('dashlet');
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
        })->handleRequest($this->getServerRequest());

        if (isset($this->getRequest()->getPost()['btn_next'])) {
            $this->setTitle(t('Add Dashlet To Dashboard'));
        } else {
            $this->setTitle(t('Select Dashlets'));
        }

        $this->addContent($dashletForm);
    }

    public function editDashletAction()
    {
        $pane = $this->validateDashletParams();
        $dashlet = $pane->getEntry($this->getParam('dashlet'));

        $dashletForm = (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () {
                $params = $this->params->without('dashlet');
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
            })
            ->handleRequest($this->getServerRequest());

        $dashletForm->getElement('submit')->setLabel(t('Update Dashlet'));

        $dashletForm->load($dashlet);

        $this->setTitle(t('Edit Dashlet'));
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $this->validateDashletParams();

        $removeForm = (new RemoveDashletForm($this->dashboard))
            ->on(RemoveDashletForm::ON_SUCCESS, function () {
                $params = $this->params->without('dashlet');
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
            })
            ->handleRequest($this->getServerRequest());

        $this->setTitle(t('Remove Dashlet'));
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

        $highlightHome = $this->params->get('home');
        $highlightPane = $this->params->get('pane');
        if (! $highlightHome) {
            $highlightHome = DashboardHome::DEFAULT_HOME;
        }

        $this->dashboard->load($highlightHome, $highlightPane, true);

        $redirect = false;
        $orgHome = null;
        $orgPane = null;
        if ($originals && isset($originals['originalHome'])) {
            /** @var DashboardHome $orgHome */
            $orgHome = $this->dashboard->getEntry($originals['originalHome']);
            $orgHome->loadDashboardEntries($originals['originalPane'] ?? null);

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

            $home->loadDashboardEntries();
            foreach ($value as $pane => $indexOrValues) {
                if (! $home->hasEntry($pane) && (! $orgHome || ! $orgHome->hasEntry($pane))) {
                    Notification::error(sprintf(t('Dashboard pane "%s" not found'), $pane));
                    break;
                }

                $pane = $home->hasEntry($pane) ? $home->getEntry($pane) : $orgHome->getEntry($pane);
                /** @var Pane $pane */
                $pane->loadDashboardEntries();

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

                    $redirect = $orgHome && $pane->getName() === $highlightPane;

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

        $params = clone $this->params;
        if ($duplicatedError || $redirect) {
            /**
             * Even though the drop action couldn't be performed successfully from our server, Sortable JS has
             * already dropped the draggable element though, so we need to redirect here to undo it.
             *
             * When the requested pane is being moved to another home we have to also redirect here in order to
             * update state history correctly, because we can't load this pane from the requested home anymore
             */
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params->without('pane')));
        }

        $this->createTabs();
        $this->getTabs()->setRefreshUrl(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->setParams($params));
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
        })->handleRequest($this->getServerRequest());

        $this->addContent($setupForm);
    }

    public function settingsAction()
    {
        $highlightHome = $this->params->get('home');
        $highlightPane = $this->params->get('pane');
        if (! $highlightHome && $highlightPane) {
            // A Dashboard Pane is always requested with home param unless it's part of the "Default Home",
            // so we just assume here that the user is originating from the base route!!
            $highlightHome = DashboardHome::DEFAULT_HOME;
        }

        $this->dashboard->load($highlightHome, $highlightPane, true);

        $this->createTabs();

        $activeHome = $this->dashboard->getActiveHome();
        // We can't grant access the user to the dashboard manager if there aren't any dashboards to manage
        if (! $activeHome || (! $activeHome->hasEntries() && count($this->dashboard->getEntries()) === 1)) {
            $this->redirectNow(Dashboard::BASE_ROUTE);
        }

        $this->dashboard->activate('dashboard_settings');

        $this->addControl(new Link(
            [new Icon('plus'), t('Add new Home')],
            Url::fromPath(Dashboard::BASE_ROUTE . '/new-home'),
            [
                'class'               => ['button-link', 'add-home'],
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));

        $this->content->getAttributes()->add('class', 'dashboard-manager');
        $this->controls->getAttributes()->add('class', ['separated', 'dashboard-manager-controls']);

        $this->addContent(new Settings($this->dashboard));
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        $tabs = $this->dashboard->getTabs();
        $activeHome = $this->dashboard->getActiveHome();
        if ($activeHome
            && (
                ! $activeHome->isDefaultHome()
                || $activeHome->hasEntries()
                || count($this->dashboard->getEntries()) > 1
            )
        ) {
            $params = [];
            if (! $activeHome->isDefaultHome()) {
                $params['home'] = $activeHome->getName();
            }

            if (($activePane = $activeHome->getActivePane())) {
                $params['pane'] = $activePane->getName();
            }

            $tabs->extend(new DashboardSettings($params));
        }

        return $tabs;
    }

    private function validateDashletParams()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');
        $dashlet = $this->params->getRequired('dashlet');

        $this->dashboard->load($home, $pane, true);

        if (! $this->dashboard->getActiveHome()->hasEntry($pane)) {
            $this->httpNotFound(t('Pane "%s" not found'), $pane);
        }

        $pane = $this->dashboard->getActiveHome()->getEntry($pane);
        if (! $pane->hasEntry($dashlet)) {
            $this->httpNotFound(t('Dashlet "%s" not found'), $dashlet);
        }

        return $pane;
    }
}
