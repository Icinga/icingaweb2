<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Forms\Dashboard\HomePaneForm;
use Icinga\Forms\Dashboard\RemoveDashletForm;
use Icinga\Forms\Dashboard\RemoveHomePaneForm;
use Icinga\Forms\Dashboard\WelcomeForm;
use Icinga\Model\ModuleDashlet;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Settings;
use Icinga\Web\Dashboard\Setup\SetupNewDashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;
use ipl\Html\HtmlElement;
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
        $this->dashboard->load();
    }

    public function indexAction()
    {
        $this->createTabs();

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome || ! $activeHome->hasPanes()) {
            $this->getTabs()->add('dashboard', [
                'active' => true,
                'title'  => t('Welcome'),
                'url'    => Url::fromRequest()
            ]);

            // Setup dashboard introduction form
            $welcomeForm = new WelcomeForm($this->dashboard);
            $welcomeForm->on(WelcomeForm::ON_SUCCESS, function () use ($welcomeForm) {
                $this->redirectNow($welcomeForm->getRedirectUrl());
            })->handleRequest(ServerRequest::fromGlobals());

            $this->dashboard->setWelcomeForm($welcomeForm);
        } elseif (empty($activeHome->getPanes(true))) {
            // TODO(TBD): What to do when the user has only disabled dashboards? Should we render the welcome screen?
        } elseif (($pane = $this->getParam('pane'))) {
            $this->getTabs()->activate($pane);
        }

        $this->content = $this->dashboard;
    }

    /**
     * Display all the dashboards assigned to a Home set in the `home` request param
     *
     * If no pane param is submitted, the default pane is displayed (usually the first one)
     */
    public function homeAction()
    {
        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        $this->createTabs();

        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome || empty($activeHome->getPanes(true))) {
            $this->getTabs()->add($home, [
                'active' => true,
                'title'  => $home,
                'url'    => Url::fromRequest()
            ]);
        } elseif (($pane = $this->getParam('pane'))) {
            $this->dashboard->activate($pane);
        }

        $this->content = $this->dashboard;
    }

    public function renameHomeAction()
    {
        $this->setTitle(t('Update Home'));

        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        $homeForm = (new HomePaneForm($this->dashboard))
            ->on(HomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow('__CLOSE__');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());
        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $this->setTitle(t('Remove Home'));

        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        $homeForm = (new RemoveHomePaneForm($this->dashboard))
            ->on(RemoveHomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow('__CLOSE__');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($homeForm);
    }

    public function editPaneAction()
    {
        $this->setTitle(t('Update Pane'));

        $pane = $this->params->getRequired('pane');
        $home = $this->params->getRequired('home');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $pane));
        }

        $paneForm = (new HomePaneForm($this->dashboard))
            ->on(HomePaneForm::ON_SUCCESS, function () {
                $this->redirectNow('__CLOSE__');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getPane($pane));

        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $this->setTitle(t('Remove Pane'));

        $home = $this->params->getRequired('home');
        $paneParam = $this->params->getRequired('pane');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($paneParam)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $paneParam));
        }

        $paneForm = new RemoveHomePaneForm($this->dashboard);
        $paneForm->populate(['org_name' => $paneParam]);
        $paneForm->on(RemoveHomePaneForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        })->handleRequest(ServerRequest::fromGlobals());

        $paneForm->getElement('btn_remove')->setLabel(t('Remove Pane'));
        $paneForm->prependHtml(HtmlElement::create('h1', null, sprintf(
            t('Please confirm removal of dashboard pane "%s"'),
            $paneParam
        )));

        $this->addContent($paneForm);
    }

    public function newDashletAction()
    {
        $this->setTitle(t('Add Dashlet To Dashboard'));

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->populate($this->getRequest()->getPost());
        $dashletForm->on(DashletForm::ON_SUCCESS, function () {
            $this->redirectNow('__CLOSE__');
        })->handleRequest(ServerRequest::fromGlobals());

        $params = $this->getAllParams();
        if ($this->getParam('url')) {
            $params['url'] = rawurldecode($this->getParam('url'));
        }

        $dashletForm->populate($params);

        $this->addContent($dashletForm);
    }

    public function editDashletAction()
    {
        $this->setTitle(t('Edit Dashlet'));

        $pane = $this->validateDashletParams();
        $dashlet = $pane->getDashlet($this->getParam('dashlet'));

        $dashletForm = (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () {
                $this->redirectNow('__CLOSE__');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->getElement('submit')->setLabel(t('Update Dashlet'));

        $dashletForm->load($dashlet);
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $this->validateDashletParams();
        $this->setTitle(t('Remove Dashlet'));

        $removeForm = (new RemoveDashletForm($this->dashboard))
            ->on(RemoveDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('__CLOSE__');
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
        if (! $this->getRequest()->isApiRequest()) {
            $this->httpBadRequest('No API request');
        }

        if (! preg_match('/([^;]*);?/', $this->getRequest()->getHeader('Content-Type'), $matches)
            || $matches[1] !== 'application/json') {
            $this->httpBadRequest('No JSON content');
        }

        $dashboards = $this->getRequest()->getPost();
        $widgetType = array_pop($dashboards);

        foreach ($dashboards as $key => $panes) {
            $home = $widgetType === 'Homes' ? $panes : $key;
            if (! $this->dashboard->hasHome($home)) {
                $this->httpNotFound(sprintf(t('Dashboard home "%s" not found'), $home));
            }

            $home = $this->dashboard->getHome($home);
            if ($widgetType === 'Homes') {
                $home->setPriority($key);
                $this->dashboard->manageHome($home);

                continue;
            }

            $home->setActive();
            $home->loadPanesFromDB();

            foreach ($panes as $innerKey => $value) {
                $pane = $widgetType === 'Dashboards' ? $value : $innerKey;
                if (! $home->hasPane($pane)) {
                    $this->httpNotFound(sprintf(t('Dashboard pane "%s" not found'), $pane));
                }

                $pane = $home->getPane($pane);
                if ($widgetType === 'Dashboards') {
                    $pane->setPriority($innerKey);
                    $home->managePanes($pane);
                } else {
                    foreach ($value as $order => $dashlet) {
                        if (! $pane->hasDashlet($dashlet)) {
                            $this->httpNotFound(sprintf(t('Dashlet "%s" not found'), $dashlet));
                        }

                        $dashlet = $pane->getDashlet($dashlet);
                        $dashlet->setPriority($order);
                        $pane->manageDashlets($dashlet);
                    }
                }
            }
        }

        exit;
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

            $this->getResponse()->setHeader('X-Icinga-Title', t('Configure Dashlets'), true);
        } else {
            $this->setTitle(t('Add Dashlet'));
        }

        $query = ModuleDashlet::on(Dashboard::getConn());

        $setupForm = new SetupNewDashboard($this->dashboard);
        $setupForm->initDashlets(Dashboard::getModuleDashlets($query));
        $setupForm->on(SetupNewDashboard::ON_SUCCESS, function () use ($setupForm) {
            if ($setupForm->getPopulatedValue('btn_cancel')) {
                $this->redirectNow('__CLOSE__');
            }

            $this->redirectNow($setupForm->getRedirectUrl());
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($setupForm);
    }

    public function settingsAction()
    {
        $this->createTabs();
        // TODO(yh): This may raise an exception when the given tab name doesn't exist.
        //      But as ipl::Tabs() doesn't offer the possibility to check this beforehand, just ignore it for now!!
        $this->dashboard->activate('dashboard_settings');

        $this->addControl(new ActionLink(
            t('Add new Home'),
            Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet'),
            'plus',
            [
                'class'               => 'add-home',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]
        ));

        $this->content = new Settings($this->dashboard);
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        $tabs = $this->dashboard->getTabs();
        $activeHome = $this->dashboard->getActiveHome();
        if ($activeHome && $activeHome->hasPanes()) {
            $tabs->extend(new DashboardSettings());
        }

        return $tabs;
    }

    private function validateDashletParams()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');
        $dashlet = $this->params->getRequired('dashlet');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf(t('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            $this->httpNotFound(sprintf(t('Pane "%s" not found'), $pane));
        }

        $pane = $this->dashboard->getActiveHome()->getPane($pane);
        if (! $pane->hasDashlet($dashlet)) {
            $this->httpNotFound(sprintf(t('Dashlet "%s" not found'), $dashlet));
        }

        return $pane;
    }
}
