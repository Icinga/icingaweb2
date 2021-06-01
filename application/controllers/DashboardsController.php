<?php

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Icinga;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Forms\Dashboard\AvailableDashlets;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Forms\Dashboard\HomeAndPaneForm;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class DashboardsController extends CompatController
{
    /** @var Dashboard */
    private $dashboard;

    public function init()
    {
        $this->dashboard = new Dashboard();
        $this->dashboard->setUser($this->Auth()->getUser());
        $this->dashboard->setTabs($this->getTabs());
        $this->dashboard->load();
    }

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

        if (! $this->dashboard || ! $activeHome || ! $activeHome->hasPanes()) {
            $this->getTabs()->add('dashboard', [
                'active'    => true,
                'title'     => $this->translate('Dashboard'),
                'url'       => Url::fromRequest()
            ]);
        } else {
            $panes = array_filter($activeHome->getPanes(), function ($pane) {
                return ! $pane->getDisabled();
            });

            if (empty($panes)) {
                $this->getTabs()->add('dashboard', [
                    'active'    => true,
                    'title'     => $this->translate('Dashboard'),
                    'url'       => Url::fromRequest()
                ]);

                $this->content = $this->dashboard;
            } else {
                if ($this->getParam('pane')) {
                    $pane = $this->getParam('pane');
                    $this->getTabs()->activate($pane);
                }

                $this->content = $this->dashboard;
            }
        }
    }

    public function homeAction()
    {
        $home = $this->params->getRequired('home');
        $activeHome = $this->dashboard->getActiveHome();

        $this->createTabs();

        if ($home === Dashboard::AVAILABLE_DASHLETS || $home === Dashboard::SHARED_DASHBOARDS) {
            $this->getTabs()->add($home, [
                'active'    => true,
                'label'     => $home,
                'url'       => Url::fromRequest()
            ]);

            if ($home === Dashboard::AVAILABLE_DASHLETS) {
                $moduleManager = Icinga::app()->getModuleManager();
                $dashlets = [];

                foreach ($moduleManager->getLoadedModules() as $module) {
                    if ($this->dashboard->getUser()->can($moduleManager::MODULE_PERMISSION_NS . $module->getName())) {
                        if (empty($module->getDashletHomes())) {
                            continue;
                        }

                        $dashlets[$module->getName()] = $module->getDashletHomes();
                    }
                }

                $this->addContent(new AvailableDashlets($dashlets));
            }
        } else {
            $panes = array_filter($activeHome->getPanes(), function ($pane) {
                return ! $pane->getDisabled();
            });

            if (empty($panes)) {
                $this->getTabs()->add('home', [
                    'active'    => true,
                    'title'     => $this->translate($this->getParam('home')),
                    'url'       => Url::fromRequest()
                ]);
            }

            if ($this->getParam('pane')) {
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
            throw new HttpNotFoundException($this->translate('Home not found'));
        }

        $this->getTabs()->add('rename-home', [
            'active'    => true,
            'title'     => $this->translate('Update Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $homeForm = new HomeAndPaneForm($this->dashboard);
        $homeForm->on(HomeAndPaneForm::ON_SUCCESS, function () use ($homeForm, $home) {
            if ($this->dashboard->hasHome($homeForm->getValue('name'))) {
                $home = $homeForm->getValue('name');
            }

            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());

        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $home = $this->params->getRequired('home');

        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException($this->translate('Home not found'));
        }

        $this->getTabs()->add('remove-home', [
            'active'    => true,
            'label'     => $this->translate('Remove Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $homeForm = (new HomeAndPaneForm($this->dashboard))
            ->setAction((string)Url::fromRequest())
            ->on(HomeAndPaneForm::ON_SUCCESS, function () use ($home) {
                // Since the navigation menu is not loaded that fast, we need to unset
                // the just deleted home from this array as well.
                $this->dashboard->unsetHome($home);

                $urlParam = [];
                $firstHome = $this->dashboard->rewindHomes();

                if (! empty($firstHome)) {
                    $urlParam = ['home' => $firstHome->getName()];
                }

                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams($urlParam));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getActiveHome());
        $this->addContent($homeForm);
    }

    public function renamePaneAction()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');

        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException($this->translate('Home not found'));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            throw new HttpNotFoundException($this->translate('Pane not found'));
        }

        $this->getTabs()->add('update-pane', [
            'active'    => true,
            'title'     => $this->translate('Update Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $paneForm = new HomeAndPaneForm($this->dashboard);
        $paneForm->on(HomeAndPaneForm::ON_SUCCESS, function () use ($paneForm, $home) {
            if ($this->dashboard->hasHome($paneForm->getValue('name'))) {
                $home = $paneForm->getValue('name');
            }

            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getPane($pane));
        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');

        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException($this->translate('Home not found'));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            throw new HttpNotFoundException($this->translate('Pane not found'));
        }

        $this->getTabs()->add('remove-pane', [
            'active'    => true,
            'label'     => $this->translate('Remove Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $paneForm = (new HomeAndPaneForm($this->dashboard))
            ->on(HomeAndPaneForm::ON_SUCCESS, function () use ($home) {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getActiveHome()->getPane($pane));
        $this->addContent($paneForm);
    }

    public function newDashletAction()
    {
        $this->getTabs()->add('new-dashlet', [
            'active'    => true,
            'label'     => $this->translate('New Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $this->redirectNow(Url::fromPath('dashboard/home')->addParams([
                'home'  => $dashletForm->getValue('home'),
                'pane'  => $dashletForm->paneName,
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
        $pane = $this->validateParams();

        $this->getTabs()->add('update-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Update Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashlet = $this->getParam('dashlet');
        $dashlet = $pane->getDashlet($dashlet);

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $home = $dashletForm->getValue('home');
            if (! $dashletForm->homeCreated) {
                $home = $this->getParam('home');
            }

            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->load($dashlet, $this->getParam('home'));
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $this->validateParams();
        $home = $this->getParam('home');

        $this->getTabs()->add('remove-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Remove Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $dashletForm = (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () use ($home) {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($dashletForm);
    }

    /**
     * Setting dialog
     */
    public function settingsAction()
    {
        $this->createTabs();

        $controlForm = new Dashboard\SettingSortBox($this->dashboard);

        $controlForm->on(Dashboard\SettingSortBox::ON_SUCCESS, function () use ($controlForm) {
            $home = $controlForm->getPopulatedValue('sort_dashboard_home');
            if (! $home) {
                $home = $controlForm->activeHome;
            }

            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addControl($controlForm);
        $this->addContent(new Dashboard\Settings($this->dashboard));
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        if ($this->getParam('home')) {
            $home = $this->dashboard->getHome($this->getParam('home'));
        } else {
            $home = $this->dashboard->rewindHomes();
        }

        $urlParam = [];

        if (! empty($home) && ! $home->getAttribute('disabled')) {
            $urlParam = ['home' => $home->getName()];
        }

        return $this->dashboard->getTabs()->extend(new DashboardSettings($urlParam));
    }

    /**
     * Check for required params
     *
     * @return Dashboard\Pane
     *
     * @throws HttpNotFoundException
     * @throws \Icinga\Exception\MissingParameterException
     */
    private function validateParams()
    {
        $home = $this->params->getRequired('home');
        $pane = $this->params->getRequired('pane');
        $dashlet = $this->params->getRequired('dashlet');

        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException($this->translate('Home not found'));
        }

        if (! $this->dashboard->getActiveHome()->hasPane($pane)) {
            throw new HttpNotFoundException($this->translate('Pane not found'));
        }

        $pane = $this->dashboard->getActiveHome()->getPane($pane);

        if (! $pane->hasDashlet($dashlet)) {
            throw new HttpNotFoundException($this->translate('Dashlet not found'));
        }

        return $pane;
    }
}
