<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Forms\Dashboard\HomePaneForm;
use Icinga\Forms\Dashboard\HomeViewSwitcher;
use Icinga\Forms\Dashboard\TakeShareForm;
use Icinga\Web\Controller\BaseDashboardController;
use Icinga\Web\Dashboard\Pane;
use Icinga\Model\Pane as PaneModel;
use Icinga\Web\Dashboard\ProvidedDashlets;
use Icinga\Web\Dashboard\SharedDashboard;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

/**
 * Handle creation, removal and displaying of dashboards, panes and dashlets
 *
 * @see Dashboard for more information about dashboards
 */
class DashboardsController extends BaseDashboardController
{
    /**
     * Display the dashboard with the pane set in the 'pane' request parameter belongs
     *
     * to the 'Default Home' If no pane is submitted, the default pane is displayed
     *
     * (usually the first pane) unless the 'Default home' has been disabled
     */
    public function indexAction()
    {
        $this->createTabs();
        $activeHome = $this->dashboard->getActiveHome();
        if (! $activeHome || ! $activeHome->hasPanes()) {
            $this->getTabs()->add('dashboard', [
                'active'    => true,
                'title'     => $this->translate('Dashboard'),
                'url'       => Url::fromRequest()
            ]);
        } else {
            if (empty($activeHome->getPanes(true))) {
                $this->getTabs()->add('dashboard', [
                    'active'    => true,
                    'title'     => $this->translate('Dashboard'),
                    'url'       => Url::fromRequest()
                ]);
            } else {
                if ($this->getParam('pane')) {
                    $pane = $this->getParam('pane');
                    $this->getTabs()->activate($pane);
                }
            }
        }

        $this->content = $this->dashboard;
    }

    /**
     * Display all the dashboards belongs to a Home set in the 'home' request parameter
     *
     * If no pane is submitted, the default pane is displayed (usually the first one)
     */
    public function homeAction()
    {
        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        $this->createTabs();
        $activeHome = $this->dashboard->getActiveHome();
        if ($home === DashboardHome::AVAILABLE_DASHLETS || $home === DashboardHome::SHARED_DASHBOARDS) {
            $this->getTabs()->add($home, [
                'active'    => true,
                'label'     => $home,
                'url'       => Url::fromRequest()
            ]);

            $limitControl = $this->createLimitControl();
            if ($home === DashboardHome::AVAILABLE_DASHLETS) {
                $this->addControl($limitControl);
                $this->addContent(new ProvidedDashlets($activeHome->loadProvidedDashlets()));
            } else {
                $this->assertPermission('application/manage/dashboards');

                $user = $this->dashboard->getAuthUser();
                $query = PaneModel::on(DashboardHome::getConn())->with([
                    'dashboard_home',
                    'dashboard_member',
                    'dashboard_member.dashboard_user'
                ]);

                $filter = Filter::all();
                $filter->add(Filter::equal('dashboard.dashboard_member.type', Dashboard::SHARED));
                $filter->add(Filter::equal(
                    'dashboard.dashboard_member.dashboard_user.name',
                    $user->getUsername()
                ));
                $query->filter($filter);
                $query->getSelectBase()->groupBy('dashboard.id');

                $paginationControl = $this->createPaginationControl($query);
                $sortControl = $this->createSortControl($query, [
                    'dashboard.name'                        => t('Name'),
                    'dashboard_member.dashboard_user.name'  => t('Owner'),
                    'dashboard_member.ctime'                => t('Created at'),
                    'dashboard_member.mtime'                => t('Last modified')
                ]);

                $this->addControl($paginationControl);
                $this->addControl($sortControl);
                $this->addControl($limitControl);
                $this->addContent(new SharedDashboard($this->dashboard, $query));
            }
        } else {
            if (! $activeHome || empty($activeHome->getPanes(true))) {
                $this->getTabs()->add($home, [
                    'active'    => true,
                    'title'     => $this->translate($this->getParam('home')),
                    'url'       => Url::fromRequest()
                ]);
            }

            if ($activeHome && $this->getParam('pane')) {
                $pane = $this->getParam('pane');
                $this->dashboard->activate($pane);
            }

            $this->content = $this->dashboard;
        }
    }

    public function renameHomeAction()
    {
        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        $this->getTabs()->add('rename-home', [
            'active'    => true,
            'title'     => $this->translate('Update Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $homeForm = new HomePaneForm($this->dashboard, $this->Auth());
        $homeForm->on(HomePaneForm::ON_SUCCESS, function () use ($home) {
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());
        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $home = $this->params->getRequired('home');
        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        $this->getTabs()->add('remove-home', [
            'active'    => true,
            'label'     => $this->translate('Remove Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        if ($this->dashboard->getActiveHome()->getType() === Dashboard::SYSTEM) {
            $this->assertPermission('application/manage/dashboards');
        }

        $homeForm = (new HomePaneForm($this->dashboard, $this->Auth()))
            ->setAction((string)Url::fromRequest())
            ->on(HomePaneForm::ON_SUCCESS, function () {
                $firstHome = $this->dashboard->rewindHomes();
                $urlParam = $firstHome ? ['home' => $firstHome->getName()] : [];

                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams($urlParam));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());
        $this->addContent($homeForm);
    }

    public function newDashboardAction()
    {
        if ($this->getRequest()->isApiRequest()) {
            $this->httpBadRequest('No API request');
        }

        $this->getTabs()->add('New Dashboard', [
            'active'    => true,
            'title'     => $this->translate('New Dashboard'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $paneForm = new HomePaneForm($this->dashboard, $this->Auth());
        $paneForm->on(HomePaneForm::ON_SUCCESS, function () use ($paneForm) {
            $home = [
                'home'  => $paneForm->getValue('home'),
                'pane'  => $paneForm->getValue('pane')
            ];
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/home')->addParams($home));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($paneForm);
    }

    public function renamePaneAction()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            $this->httpNotFound(sprintf($this->translate('Pane "%s" not found'), $pane));
        }

        $this->getTabs()->add('update-pane', [
            'active'    => true,
            'title'     => $this->translate('Update Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $paneForm = new HomePaneForm($this->dashboard, $this->Auth());
        $paneForm->on(HomePaneForm::ON_SUCCESS, function () use ($paneForm, $home) {
            if ($this->dashboard->hasHome($paneForm->getValue('home'))) {
                $home = $paneForm->getValue('home');
            }

            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getPane($pane));
        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $home = $this->params->getRequired('home');
        $paneParam = $this->params->getRequired('pane');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($paneParam)) {
            $this->httpNotFound(sprintf($this->translate('Pane "%s" not found'), $paneParam));
        }

        $this->getTabs()->add('remove-pane', [
            'active'    => true,
            'label'     => $this->translate('Remove Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $pane = $this->dashboard->getActiveHome()->getPane($paneParam);
        if ($pane->getType() === Dashboard::SYSTEM) {
            $this->assertPermission('application/manage/dashboards');
        }

        $paneForm = (new HomePaneForm($this->dashboard, $this->Auth()))
            ->on(HomePaneForm::ON_SUCCESS, function () use ($home) {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getPane($paneParam));
        $this->addContent($paneForm);
    }

    public function newDashletAction()
    {
        $this->getTabs()->add('new-dashlet', [
            'active'    => true,
            'label'     => $this->translate('New Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashletForm = new DashletForm($this->dashboard, $this->Auth());
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/home')->addParams([
                'home'  => $dashletForm->getValue('home'),
                'pane'  => $dashletForm->getValue('pane'),
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        if ($this->getParam('url')) {
            $params = $this->getAllParams();
            $params['url'] = rawurldecode($this->getParam('url'));
            $dashletForm->populate($params);
        }

        $this->addContent($dashletForm);
    }

    public function updateDashletAction()
    {
        $pane = $this->validateDashletParams();

        $this->getTabs()->add('update-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Update Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashlet = $this->getParam('dashlet');
        $dashlet = $pane->getDashlet($dashlet);

        $dashletForm = new DashletForm($this->dashboard, $this->Auth());
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $home = $dashletForm->getPopulatedValue('home', $this->getParam('home'));

            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->load($dashlet, $this->getParam('home'));
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $pane = $this->validateDashletParams();
        $home = $this->getParam('home');
        if ($pane->getDashlet($this->getParam('dashlet'))->getType() === Dashboard::SYSTEM) {
            $this->assertPermission('application/manage/dashboards');
        }

        $this->getTabs()->add('remove-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Remove Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashletForm = (new DashletForm($this->dashboard, $this->Auth()))
            ->on(DashletForm::ON_SUCCESS, function () use ($home) {
                $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($dashletForm);
    }

    public function takeShareAction()
    {
        $dashboard = $this->dashboard;
        $dashboard->activateHome($dashboard->getHome(DashboardHome::SHARED_DASHBOARDS));
        $panes = $dashboard->getActiveHome()->getSharedPanes();
        $home = $this->getParam('home');
        $pane = $this->getParam('pane');

        if ($home && ! $dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        if ($pane && ! array_key_exists($pane, $panes)) {
            $this->httpNotFound(sprintf($this->translate('Pane "%s" not found'), $pane));
        }

        $this->getTabs()->add('take-share', [
            'active'    => true,
            'label'     => $this->translate('Take Share'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $form = new TakeShareForm($dashboard, $panes);
        $form->on(TakeShareForm::ON_SUCCESS, function () use ($form) {
            $this->redirectNow('__CLOSE__');
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }

    /**
     * Setting dialog
     */
    public function settingsAction()
    {
        $this->createTabs();
        $controlForm = new HomeViewSwitcher($this->dashboard);

        $controlForm->on(HomeViewSwitcher::ON_SUCCESS, function () use ($controlForm) {
            $activeHome = $this->dashboard->getActiveHome();
            $home = $controlForm->getPopulatedValue('sort_dashboard_home');
            $home = $home ?: ($activeHome ? $activeHome->getName() : null);

            $this->redirectNow(Url::fromPath(Dashboard::BASE_ROUTE . '/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $settingsForm = new Dashboard\Settings($this->dashboard);
        $settingsForm->on(Dashboard\Settings::ON_SUCCESS, function () {
            $this->redirectNow(Url::fromRequest());
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addControl($controlForm);
        $this->addContent($settingsForm);
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        $homeParam = $this->getParam('home');
        if (
            $this->dashboard->hasHome($homeParam)
            && $homeParam !== DashboardHome::AVAILABLE_DASHLETS
            && $homeParam !== DashboardHome::SHARED_DASHBOARDS
        ) {
            $home = $this->dashboard->getHome($homeParam);
        } else {
            $home = $this->dashboard->rewindHomes();
        }

        $urlParam = [];
        if (! empty($home) && ! $home->isDisabled()) {
            $urlParam = ['home' => $home->getName()];
        }

        return $this->dashboard->getTabs()->extend(new DashboardSettings($urlParam));
    }

    /**
     * Check for required params
     *
     * @return Pane
     */
    private function validateDashletParams()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');
        $dashlet = $this->params->getRequired('dashlet');

        if (! $this->dashboard->hasHome($home)) {
            $this->httpNotFound(sprintf($this->translate('Home "%s" not found'), $home));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            $this->httpNotFound(sprintf($this->translate('Pane "%s" not found'), $pane));
        }

        $pane = $this->dashboard->getActiveHome()->getPane($pane);

        if (! $pane->hasDashlet($dashlet)) {
            $this->httpNotFound(sprintf($this->translate('Dashlet "%s" not found'), $dashlet));
        }

        return $pane;
    }
}
