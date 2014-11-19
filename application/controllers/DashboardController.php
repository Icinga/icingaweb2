<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Dashboard\ComponentForm;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Tabextension\DashboardSettings;

/**
 * Handle creation, removal and displaying of dashboards, panes and components
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
        $this->dashboard->setUser($this->getRequest()->getUser());
        $this->dashboard->load();
    }

    public function newComponentAction()
    {
        $form = new ComponentForm();
        $this->createTabs();
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
            $component = new Dashboard\Component($form->getValue('component'), $form->getValue('url'), $pane);
            $component->setUserWidget();
            $pane->addComponent($component);
            try {
                $dashboard->write();
            } catch (\Zend_Config_Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboard->createWriter();
                $action->render('error');
                return false;
            }
            Notification::success(t('Component created'));
            return true;
        });
        $form->setRedirectUrl('dashboard');
        $form->handleRequest();
        $this->view->form = $form;
    }

    public function updateComponentAction()
    {
        $this->createTabs();
        $dashboard = $this->dashboard;
        $form = new ComponentForm();
        $form->setDashboard($dashboard);
        $form->setSubmitLabel(t('Update Component'));
        if (! $this->_request->getParam('pane')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "pane"',
                400
            );
        }
        if (! $this->_request->getParam('component')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "component"',
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
                $component = $pane->getComponent($form->getValue('component'));
                $component->setUrl($form->getValue('url'));
            } catch (ProgrammingError $e) {
                $component = new Dashboard\Component($form->getValue('component'), $form->getValue('url'), $pane);
                $pane->addComponent($component);
            }
            $component->setUserWidget();
            // Rename component
            if ($form->getValue('org_component') && $form->getValue('org_component') !== $component->getTitle()) {
                $pane->removeComponent($form->getValue('org_component'));
            }
            // Move
            if ($form->getValue('org_pane') && $form->getValue('org_pane') !== $pane->getTitle()) {
                $oldPane = $dashboard->getPane($form->getValue('org_pane'));
                $oldPane->removeComponent($component->getTitle());
            }
            try {
                $dashboard->write();
            } catch (\Zend_Config_Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboard->createWriter();
                $action->render('error');
                return false;
            }
            Notification::success(t('Component updated'));
            return true;
        });
        $form->setRedirectUrl('dashboard/settings');
        $form->handleRequest();
        $pane = $dashboard->getPane($this->getParam('pane'));
        $component = $pane->getComponent($this->getParam('component'));
        $form->load($component);

        $this->view->form = $form;
    }

    public function removeComponentAction()
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
        if (! $this->_request->getParam('component')) {
            throw new Zend_Controller_Action_Exception(
                'Missing parameter "component"',
                400
            );
        }
        $pane = $this->_request->getParam('pane');
        $component = $this->_request->getParam('component');
        $action = $this;
        $form->setOnSuccess(function (Form $form) use ($dashboard, $component, $pane, $action) {
            try {
                $pane = $dashboard->getPane($pane);
                $pane->removeComponent($component);
                $dashboard->write();
                Notification::success(t('Component has been removed from') . ' ' . $pane->getTitle());
                return true;
            }  catch (\Zend_Config_Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboard->createWriter();
                $action->render('error');
                return false;
            } catch (ProgrammingError $e) {
                Notification::error($e->getMessage());
                return false;
            }
            return false;
        });
        $form->setRedirectUrl('dashboard/settings');
        $form->handleRequest();
        $this->view->pane = $pane;
        $this->view->component = $component;
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
            try {
                $pane = $dashboard->getPane($pane);
                $dashboard->removePane($pane->getTitle());
                $dashboard->write();
                Notification::success(t('Pane has been removed') . ': ' . $pane->getTitle());
                return true;
            }  catch (\Zend_Config_Exception $e) {
                $action->view->error = $e;
                $action->view->config = $dashboard->createWriter();
                $action->render('error');
                return false;
            } catch (ProgrammingError $e) {
                Notification::error($e->getMessage());
                return false;
            }
            return false;
        });
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
            if ($this->_getParam('pane')) {
                $pane = $this->_getParam('pane');
                $this->dashboard->activate($pane);
            }
            if ($this->dashboard === null) {
                $this->view->title = 'Dashboard';
            } else {
                $this->view->title = $this->dashboard->getActivePane()->getTitle() . ' :: Dashboard';
                if ($this->hasParam('remove')) {
                    $this->dashboard->getActivePane()->removeComponent($this->getParam('remove'));
                    $this->dashboard->write();
                    $this->redirectNow(URL::fromRequest()->remove('remove'));
                }
                $this->view->tabs->add(
                    'Add',
                    array(
                        'title' => '+',
                        'url' => Url::fromPath('dashboard/new-component')
                    )
                );
                $this->view->dashboard = $this->dashboard;
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
