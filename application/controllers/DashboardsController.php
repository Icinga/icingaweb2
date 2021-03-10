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
        $this->dashboard->load();
    }

    public function indexAction()
    {
        $this->createTabs();

        if ($this->dashboard->hasHome(Dashboard::DEFAULT_HOME)) {
            $defaultHome = $this->dashboard->getHome(Dashboard::DEFAULT_HOME);

            if (! $defaultHome->getAttribute('disabled')) {
                $this->dashboard->loadUserDashboards($defaultHome->getAttribute('homeId'));
            }
        }

        if (! $this->dashboard->hasPanes()) {
            $this->setTitle('Dashboard');
        } else {
            $panes = array_filter($this->dashboard->getPanes(), function ($pane) {
                return ! $pane->getDisabled();
            });

            if (empty($panes)) {
                $this->setTitle('Dashboard');
                $this->dashboard->getTabs()->add('dashboard', [
                    'active'    => true,
                    'title'     => $this->translate('Dashboard'),
                    'url'       => Url::fromRequest()
                ]);

                $this->content = $this->dashboard;
            } else {
                if ($this->getParam('pane')) {
                    $pane = $this->getParam('pane');
                    $this->dashboard->activate($pane);
                }

                if ($this->dashboard === null) {
                    $this->setTitle('Dashboard');
                } else {
                    $this->setTitle($this->dashboard->getActivePane()->getTitle());

                    $this->content = $this->dashboard;
                }
            }
        }
    }

    public function homeAction()
    {
        $home = $this->params->getRequired('home');

        if ($home === Dashboard::AVAILABLE_DASHLETS || $home === Dashboard::SHARED_DASHBOARDS) {
            $this->dashboard->getTabs()->add($home, [
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
            $this->createTabs(true);

            $panes = array_filter($this->dashboard->getPanes(), function ($pane) {
                return ! $pane->getDisabled();
            });

            if (empty($panes)) {
                $this->setTitle($this->getParam('home'));
                $this->dashboard->getTabs()->add('home', [
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
        $this->getTabs()->add('rename-home', [
            'active'    => true,
            'title'     => $this->translate('Update Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        if (! $this->getParam('home')) {
            throw new \Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }

        $homeForm = new HomeAndPaneForm($this->dashboard);
        $homeForm->on(HomeAndPaneForm::ON_SUCCESS, function () use ($homeForm) {
            $home = $homeForm->getValue('name');
            if (! $this->dashboard->hasHome($home)) {
                $home = $homeForm->getValue('org_name');
            }

            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home' => $home]));
        })->handleRequest(ServerRequest::fromGlobals());

        $homes = $this->dashboard->getHomes();
        $homeForm->load($homes[$this->params->getRequired('home')]);

        $this->addContent($homeForm);
    }

    public function removeHomeAction()
    {
        $this->getTabs()->add('remove-home', [
            'active'    => true,
            'label'     => $this->translate('Remove Home'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        if (! $this->getParam('home')) {
            throw new \Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }

        $homeForm = (new HomeAndPaneForm($this->dashboard))
            ->setAction((string)Url::fromRequest())
            ->on(HomeAndPaneForm::ON_SUCCESS, function () {
                // Since the navigation menu is not loaded that fast, we need to unset
                // the just deleted home from this array as well.
                $this->dashboard->unsetHome($this->getParam('home'));

                $firstHome = $this->dashboard->rewindHomes();
                if (empty($firstHome)) {
                    $this->redirectNow('dashboard/settings');
                } else {
                    $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                        'home'  => $firstHome->getName()
                    ]));
                }
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getHomes()[$this->getParam('home')]);
        $this->addContent($homeForm);
    }

    public function renamePaneAction()
    {
        $this->getTabs()->add('update-pane', [
            'active'    => true,
            'title'     => $this->translate('Update Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $home = $this->getParam('home');
        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException('Home not found');
        }

        $pane = $this->getParam('pane');
        if (! $this->dashboard->hasPane($pane)) {
            throw new HttpNotFoundException('Pane not found');
        }

        $paneForm = (new HomeAndPaneForm($this->dashboard))
            ->on(HomeAndPaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                    'home'  => $this->getParam('home')
                ]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getPane($pane));
        $this->addContent($paneForm);
    }

    public function removePaneAction()
    {
        $this->getTabs()->add('remove-pane', [
            'active'    => true,
            'label'     => $this->translate('Remove Pane'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $home = $this->getParam('home');
        if (! $this->dashboard->hasHome($home)) {
            throw new HttpNotFoundException('Home not found');
        }

        $pane = $this->getParam('pane');
        if (! $this->dashboard->hasPane($pane)) {
            throw new HttpNotFoundException('Pane not found');
        }

        $paneForm = (new HomeAndPaneForm($this->dashboard))
            ->on(HomeAndPaneForm::ON_SUCCESS, function () use ($home) {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams(['home'  => $home]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getPane($pane));
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
        $this->getTabs()->add('update-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Update Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $this->isMissingSomething();

        $pane = $this->dashboard->getPane($this->getParam('pane'));
        $dashlet = $pane->getDashlet($this->getParam('dashlet'));

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                'home'  => $dashletForm->getValue('home')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->load($dashlet);
        $this->addContent($dashletForm);
    }

    public function removeDashletAction()
    {
        $this->getTabs()->add('remove-dashlet', [
            'active'    => true,
            'label'     => $this->translate('Remove Dashlet'),
            'url'       => Url::fromRequest()
        ])->disableLegacyExtensions();

        $this->isMissingSomething();
        $dashletForm = (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                    'home'  => $this->getParam('home')
                ]));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($dashletForm);
    }

    public function settingsAction()
    {
        $this->createTabs();
        $controlForm = new Dashboard\SettingSortBox($this->dashboard);
        $controlForm->on(Dashboard\SettingSortBox::ON_SUCCESS, function () use ($controlForm) {
            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                'home' => $controlForm->getPopulatedValue('sort_dashboard_home')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addControl($controlForm);
        $this->addContent(new Dashboard\Settings($this->dashboard));
    }

    private function createTabs($defaultPanes = false)
    {
        if ($this->getParam('home')) {
            $urlParam = ['home' => $this->getParam('home')];
        } else {
            $home = $this->dashboard->rewindHomes();
            $urlParam = ['home' => ! $home ?: $home->getName()];
        }

        $this->controls->setTabs($this->dashboard->getTabs($defaultPanes)->extend(new DashboardSettings($urlParam)));
    }

    /**
     * Check if any of the required parameters are missing
     *
     * @throws \Zend_Controller_Action_Exception
     */
    private function isMissingSomething()
    {
        if (! $this->getParam('home')) {
            throw new \Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }
        if (! $this->getParam('pane')) {
            throw new \Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->getParam('dashlet')) {
            throw new \Zend_Controller_Action_Exception(
                'Missing parameter "dashlet"',
                400
            );
        }
    }
}
