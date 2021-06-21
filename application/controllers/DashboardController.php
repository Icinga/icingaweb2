<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Icinga;
use Icinga\Forms\Dashboard\AvailableDashlets;
use Icinga\Forms\Dashboard\HomeAndPaneForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;
use Zend_Controller_Action_Exception;
use ipl\Web\Url;

/**
 * Handle creation, removal and displaying of dashboards, panes and dashlets
 *
 * @see Icinga\Web\Widget\Dashboard for more information about dashboards
 */
class DashboardController extends ActionController
{
    /**
     * @var Dashboard;
     */
    private $dashboard;

    public function init()
    {
        $this->dashboard = new Dashboard();
        $this->dashboard->setUser($this->Auth()->getUser());
        $this->dashboard->load();
    }

    public function newDashletAction()
    {
        $this->getTabs()->add('new-dashlet', array(
            'active'    => true,
            'label'     => $this->translate('New Dashlet'),
            'url'       => Url::fromRequest()
        ));

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $this->redirectNow(Url::fromPath('dashboard/home')->addParams([
                'home'  => $dashletForm->getValue('home'),
                'pane'  => $dashletForm->getValue('pane'),
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        if ($this->getParam('url')) {
            $params = $this->getAllParams();
            $params['url'] = rawurldecode($this->getParam('url'));
            $dashletForm->populate($params);
        }

        $this->view->form = $dashletForm;
    }

    public function updateDashletAction()
    {
        $this->getTabs()->add('update-dashlet', array(
            'active'    => true,
            'label'     => $this->translate('Update Dashlet'),
            'url'       => Url::fromRequest()
        ));

        if (! $this->getParam('home')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }
        if (! $this->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->getParam('dashlet')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "dashlet"',
                400
            );
        }

        $pane = $this->dashboard->getPane($this->getParam('pane'));
        $dashlet = $pane->getDashlet($this->getParam('dashlet'));

        $dashletForm = new DashletForm($this->dashboard);
        $dashletForm->on(DashletForm::ON_SUCCESS, function () use ($dashletForm) {
            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                'home' => $dashletForm->getValue('home')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $dashletForm->load($dashlet);
        $this->view->form = $dashletForm;
    }

    public function removeDashletAction()
    {
        if (! $this->getParam('home')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }
        if (! $this->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->getParam('dashlet')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "dashlet"',
                400
            );
        }

        // No need to pass the form to the view renderer
        (new DashletForm($this->dashboard))
            ->on(DashletForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath('dashboard/settings')->setParams([
                    'home' => $this->getParam('home')
                ]));
            })->handleRequest(ServerRequest::fromGlobals());
    }

    public function renamePaneAction()
    {
        $this->getTabs()->add('update-pane', [
            'title' => $this->translate('Update Pane'),
            'url'   => Url::fromRequest()
        ])->activate('update-pane');

        $home = $this->getParam('home');
        if (! array_key_exists($home, $this->dashboard->getHomes())) {
            throw new HttpNotFoundException('Home not found');
        }

        $paneName = $this->getParam('pane');
        if (! $this->dashboard->hasPane($paneName)) {
            throw new HttpNotFoundException('Pane not found');
        }

        $paneForm = (new HomeAndPaneForm($this->dashboard))
            ->on(HomeAndPaneForm::ON_SUCCESS, function () {
                $this->redirectNow('__back__');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $paneForm->load($this->dashboard->getPane($paneName));
        $this->view->form = $paneForm;
    }

    public function removePaneAction()
    {
        $home = $this->getParam('home');
        if (! array_key_exists($home, $this->dashboard->getHomes())) {
            throw new HttpNotFoundException('Home not found');
        }

        $paneName = $this->getParam('pane');
        if (! $this->dashboard->hasPane($paneName)) {
            throw new HttpNotFoundException('Pane not found');
        }

        (new HomeAndPaneForm($this->dashboard))
            ->on(HomeAndPaneForm::ON_SUCCESS, function () {
                $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                    'home' => $this->getParam('home')
                ]));
            })
            ->handleRequest(ServerRequest::fromGlobals())
            ->load($this->dashboard->getPane($paneName));
    }

    public function renameHomeAction()
    {
        $this->getTabs()->add('rename-home', [
            'title' => $this->translate('Update Home'),
            'url'   => Url::fromRequest()
        ])->activate('rename-home');

        if (! $this->getParam('home')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }

        $homeForm = new HomeAndPaneForm($this->dashboard);
        $homeForm->on(HomeAndPaneForm::ON_SUCCESS, function () use ($homeForm) {
            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                'home'  => $homeForm->getValue('name')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $homeForm->load($this->dashboard->getHomes()[$this->getParam('home')]);
        $this->view->form = $homeForm;
    }

    public function removeHomeAction()
    {
        if (! $this->hasParam('home')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "home"',
                400
            );
        }

        (new HomeAndPaneForm($this->dashboard))
            ->setAction((string)Url::fromRequest())
            ->on(HomeAndPaneForm::ON_SUCCESS, function () {
                $homes = $this->dashboard->getHomes();
                // Since the navigation menu is not loaded that fast, we need to unset
                // the just deleted home from this array as well.
                unset($homes[$this->getParam('home')]);

                $firstHome = reset($homes);
                if (empty($firstHome)) {
                    $this->redirectNow('dashboard');
                } else {
                    $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                        'home'  => $firstHome->getName()
                    ]));
                }
            })->handleRequest(ServerRequest::fromGlobals())
            ->load($this->dashboard->getHomes()[$this->getParam('home')]);
    }

    public function homeAction()
    {
        $dashboardHome = $this->getParam('home');

        if ($dashboardHome === Dashboard::AVAILABLE_DASHLETS || $dashboardHome === Dashboard::SHARED_DASHBOARDS) {
            $this->view->tabeleView = true;

            $this->getTabs()->add($dashboardHome, [
                'label' => $dashboardHome,
                'url'   => Url::fromRequest()
            ])->activate($dashboardHome);

            if ($dashboardHome === Dashboard::AVAILABLE_DASHLETS) {
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

                $dashlet = new AvailableDashlets($dashlets);
                $this->view->dashlets = $dashlet;
            }
        } else {
            $this->createTabs(true);
            // Table view and dashboard/dashlets view have different div contents
            // so we need to set tableView to false
            $this->view->tabeleView = false;

            if ($this->getParam('pane')) {
                $pane = $this->getParam('pane');
                $this->dashboard->activate($pane);
            }

            $this->view->dashboard = $this->dashboard;
        }
    }

    public function homeDetailAction()
    {
        $dashlet = $this->getParam('dashlet');
        $this->getTabs()->add($dashlet, [
            'label' => $this->getParam('module') . ' Dashboard',
            'url'   => Url::fromRequest()
        ])->activate($dashlet);

        $dashletWidget = new Dashboard\Dashlet($dashlet, Url::fromRequest());
        $this->view->dashlets = $dashletWidget;
    }

    /**
     * Display the dashboard with the pane set in the 'pane' request parameter
     *
     * If no pane is submitted or the submitted one doesn't exist, the default pane is
     * displayed (normally the first one)
     */
    public function indexAction()
    {
        $homes = $this->dashboard->getHomes();
        if (array_key_exists(Dashboard::DEFAULT_HOME, $homes)) {
            $defaultHome = $homes[Dashboard::DEFAULT_HOME];
            $this->dashboard->loadUserDashboardsFromDatabase($defaultHome->getAttribute('homeId'));
        }

        $this->createTabs();
        if (! $this->dashboard->hasPanes()) {
            $this->view->title = 'Dashboard';
        } else {
            if (empty($this->dashboard->getPanes())) {
                $this->view->title = 'Dashboard';
                $this->getTabs()->add('dashboard', array(
                    'active'    => true,
                    'title'     => $this->translate('Dashboard'),
                    'url'       => Url::fromRequest()
                ));
            } else {
                if ($this->_getParam('pane')) {
                    $pane = $this->_getParam('pane');
                    $this->dashboard->activate($pane);
                }
                if ($this->dashboard === null) {
                    $this->view->title = 'Dashboard';
                } else {
                    $this->view->title = $this->dashboard->getActivePane()->getTitle() . ' :: Dashboard';
                    $this->view->dashboard = $this->dashboard;
                }
            }
        }
    }

    /**
     * Setting dialog
     */
    public function settingsAction()
    {
        $this->createTabs();
        $controlForm = new Dashboard\SettingSortBox($this->dashboard);
        $controlForm->on(Dashboard\SettingSortBox::ON_SUCCESS, function () use ($controlForm) {
            $this->redirectNow(Url::fromPath('dashboard/settings')->addParams([
                'home' => $controlForm->getPopulatedValue('sort_dashboard_home')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->view->control = $controlForm;
        $this->view->dashboard = $this->dashboard;
        $this->view->settings = new Dashboard\Settings($this->dashboard);
    }

    /**
     * Create tab aggregation
     *
     * @param  bool  $defaultPanes
     */
    private function createTabs($defaultPanes = false)
    {
        $urlParam = [];
        if ($this->hasParam('home')) {
            $urlParam = ['home' => $this->getParam('home')];
        }
        $this->view->tabs = $this->dashboard->getTabs($defaultPanes)->extend(new DashboardSettings($urlParam));
    }
}
