<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Logger;
use Icinga\Form\Dashboard\ComponentForm;
use Icinga\Web\Notification;
use Icinga\Web\Controller\ActionController;
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
        $dashboard = new Dashboard();
        $dashboard->setUser($this->getRequest()->getUser());
        $dashboard->load();
        $form->setDashboard($dashboard);
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
        $form->setOnSuccess(function (\Icinga\Web\Request $request, \Icinga\Web\Form $form) use ($dashboard) {
            $pane = $dashboard->getPane($form->getValue('pane'));
            try {
                $component = $pane->getComponent($form->getValue('component'));
                $component->setUrl($form->getValue('url'));
            } catch (\Icinga\Exception\ProgrammingError $e) {
                $component = new Dashboard\Component($form->getValue('component'), $form->getValue('url'), $pane);
                $pane->addComponent($component);
            }
            $component->setUserWidget();
            // Rename component
            if ($form->getValue('org_component') && $form->getValue('org_component') !== $component->getTitle()) {
                $pane->removeComponent($form->getValue('org_component'));
            }
            $dashboard->write();
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

    public function deleteComponentAction()
    {
        $form = new ComponentForm();

        $this->createTabs();
        $dashboard = new Dashboard();
        $dashboard->setUser($this->getRequest()->getUser());
        $dashboard->load();
        $form->setDashboard($dashboard);
        $form->handleRequest();
        $this->view->form = $form;
    }

    /**
     * Display the form for adding new components or add the new component if submitted
     */
    public function addurlAction()
    {
        $form = new AddUrlForm();

        $dashboard = new Dashboard();
        $dashboard->setUser($this->getRequest()->getUser());
        $dashboard->load();
        $form->setDashboard($dashboard);
        $form->handleRequest();
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
                
                /* $this->view->tabs->add(
                    'Add',
                    array(
                        'title' => '+',
                        'url' => Url::fromPath('dashboard/addurl')
                    )
                ); */
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
