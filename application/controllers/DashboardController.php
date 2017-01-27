<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use Zend_Controller_Action_Exception;
use Icinga\Exception\ProgrammingError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Dashboard\DashletForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;

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
        $form = new DashletForm();
        $this->getTabs()->add('new-dashlet', array(
            'active'    => true,
            'label'     => $this->translate('New Dashlet'),
            'url'       => Url::fromRequest()
        ));
        $dashboard = $this->dashboard;
        $form->setDashboard($dashboard);
        if ($this->_request->getParam('url')) {
            $params = $this->_request->getParams();
            $params['url'] = rawurldecode($this->_request->getParam('url'));
            $form->populate($params);
        }
        $action = $this;
        $form->setOnSuccess(function (Form $form) use ($dashboard, $action) {
            try {
                $pane = $dashboard->getPane($form->getValue('pane'));
            } catch (ProgrammingError $e) {
                $pane = new Dashboard\Pane($form->getValue('pane'));
                $pane->setUserWidget();
                $dashboard->addPane($pane);
            }
            $dashlet = new Dashboard\Dashlet($form->getValue('dashlet'), $form->getValue('url'), $pane);
            $dashlet->setUserWidget();
            $pane->addDashlet($dashlet);
            $dashboardConfig = $dashboard->getConfig();
            try {
                $dashboardConfig->saveIni();
            } catch (Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboardConfig;
                $action->render('error');
                return false;
            }
            Notification::success(t('Dashlet created'));
            return true;
        });
        $form->setTitle($this->translate('Add Dashlet To Dashboard'));
        $form->setRedirectUrl('dashboard');
        $form->handleRequest();
        $this->view->form = $form;
    }

    public function updateDashletAction()
    {
        $this->getTabs()->add('update-dashlet', array(
            'active'    => true,
            'label'     => $this->translate('Update Dashlet'),
            'url'       => Url::fromRequest()
        ));
        $dashboard = $this->dashboard;
        $form = new DashletForm();
        $form->setDashboard($dashboard);
        $form->setSubmitLabel($this->translate('Update Dashlet'));
        if (! $this->_request->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->_request->getParam('dashlet')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "dashlet"',
                400
            );
        }
        $action = $this;
        $form->setOnSuccess(function (Form $form) use ($dashboard, $action) {
            try {
                $pane = $dashboard->getPane($form->getValue('pane'));
            } catch (ProgrammingError $e) {
                $pane = new Dashboard\Pane($form->getValue('pane'));
                $pane->setUserWidget();
                $dashboard->addPane($pane);
            }
            try {
                $dashlet = $pane->getDashlet($form->getValue('dashlet'));
                $dashlet->setUrl($form->getValue('url'));
            } catch (ProgrammingError $e) {
                $dashlet = new Dashboard\Dashlet($form->getValue('dashlet'), $form->getValue('url'), $pane);
                $pane->addDashlet($dashlet);
            }
            $dashlet->setUserWidget();
            // Rename dashlet
            if ($form->getValue('org_dashlet') && $form->getValue('org_dashlet') !== $dashlet->getTitle()) {
                $pane->removeDashlet($form->getValue('org_dashlet'));
            }
            // Move
            if ($form->getValue('org_pane') && $form->getValue('org_pane') !== $pane->getTitle()) {
                $oldPane = $dashboard->getPane($form->getValue('org_pane'));
                $oldPane->removeDashlet($dashlet->getTitle());
            }
            $dashboardConfig = $dashboard->getConfig();
            try {
                $dashboardConfig->saveIni();
            } catch (Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboardConfig;
                $action->render('error');
                return false;
            }
            Notification::success(t('Dashlet updated'));
            return true;
        });
        $form->setTitle($this->translate('Edit Dashlet'));
        $form->setRedirectUrl('dashboard/settings');
        $form->handleRequest();
        $pane = $dashboard->getPane($this->getParam('pane'));
        $dashlet = $pane->getDashlet($this->getParam('dashlet'));
        $form->load($dashlet);

        $this->view->form = $form;
    }

    public function removeDashletAction()
    {
        $form = new ConfirmRemovalForm();
        $this->getTabs()->add('remove-dashlet', array(
            'active'    => true,
            'label'     => $this->translate('Remove Dashlet'),
            'url'       => Url::fromRequest()
        ));
        $dashboard = $this->dashboard;
        if (! $this->_request->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->_request->getParam('dashlet')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "dashlet"',
                400
            );
        }
        $pane = $this->_request->getParam('pane');
        $dashlet = $this->_request->getParam('dashlet');
        $action = $this;
        $form->setOnSuccess(function (Form $form) use ($dashboard, $dashlet, $pane, $action) {
            $pane = $dashboard->getPane($pane);
            $pane->removeDashlet($dashlet);
            $dashboardConfig = $dashboard->getConfig();
            try {
                $dashboardConfig->saveIni();
                Notification::success(t('Dashlet has been removed from') . ' ' . $pane->getTitle());
            } catch (Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboardConfig;
                $action->render('error');
                return false;
            }
            return true;
        });
        $form->setTitle($this->translate('Remove Dashlet From Dashboard'));
        $form->setRedirectUrl('dashboard/settings');
        $form->handleRequest();
        $this->view->pane = $pane;
        $this->view->dashlet = $dashlet;
        $this->view->form = $form;
    }

    public function removePaneAction()
    {
        $form = new ConfirmRemovalForm();
        $this->createTabs();
        $dashboard = $this->dashboard;
        if (! $this->_request->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        $pane = $this->_request->getParam('pane');
        $action = $this;
        $form->setOnSuccess(function (Form $form) use ($dashboard, $pane, $action) {
            $pane = $dashboard->getPane($pane);
            $dashboard->removePane($pane->getTitle());
            $dashboardConfig = $dashboard->getConfig();
            try {
                $dashboardConfig->saveIni();
                Notification::success(t('Dashboard has been removed') . ': ' . $pane->getTitle());
            } catch (Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboardConfig;
                $action->render('error');
                return false;
            }
            return true;
        });
        $form->setTitle($this->translate('Remove Dashboard'));
        $form->setRedirectUrl('dashboard/settings');
        $form->handleRequest();
        $this->view->pane = $pane;
        $this->view->form = $form;
    }

    /**
     * Display the dashboard with the pane set in the 'pane' request parameter
     *
     * If no pane is submitted or the submitted one doesn't exist, the default pane is
     * displayed (normally the first one)
     */
    public function indexAction()
    {
        $this->createTabs();
        if (! $this->dashboard->hasPanes()) {
            $this->view->title = 'Dashboard';
        } else {
            $panes = array_filter(
                $this->dashboard->getPanes(),
                function ($pane) {
                    return ! $pane->getDisabled();
                }
            );
            if (empty($panes)) {
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
                    if ($this->hasParam('remove')) {
                        $this->dashboard->getActivePane()->removeDashlet($this->getParam('remove'));
                        $this->dashboard->getConfig()->saveIni();
                        $this->redirectNow(URL::fromRequest()->remove('remove'));
                    }
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
        $this->view->dashboard = $this->dashboard;
    }

    /**
     * Create tab aggregation
     */
    private function createTabs()
    {
        $this->view->tabs = $this->dashboard->getTabs()->extend(new DashboardSettings());
    }
}
