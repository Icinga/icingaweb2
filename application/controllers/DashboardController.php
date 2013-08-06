<?php

use Icinga\Web\ActionController;
use Icinga\Application\Config;
use Icinga\Web\Url;
use Icinga\Application\Icinga;
use Icinga\Web\Widget\Dashboard;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Exception\ConfigurationError;
use Icinga\Form\Dashboard\AddUrlForm;

class DashboardController extends ActionController
{
    private function getDashboard($config = 'dashboard/dashboard')
    {
        $dashboard = new Dashboard();
        $dashboard->readConfig(IcingaConfig::app($config));
        return $dashboard;
    }

    public function removecomponentAction()
    {
        $pane =  $this->_getParam('pane');
        $dashboard = $this->getDashboard();
        $dashboard->removeComponent(
            $pane,
            $this->_getParam('component')
        )->store();

        // When the pane doesn't exist anymore, display the default pane
        if ($dashboard->isEmptyPane($pane)) {
            $this->redirectNow(Url::fromPath('dashboard'));
            return;
        }
        $this->redirectNow(Url::fromPath('dashboard', array('pane' => $pane)));
    }
    
    public function addurlAction()
    {
        $form = new AddUrlForm();
        $form->setRequest($this->_request);
        $this->view->form = $form;


        if ($form->isSubmittedAndValid()) {
            $dashboard = $this->getDashboard();
            $dashboard->setComponentUrl(
                $form->getValue('pane'),
                $form->getValue('component'),
                ltrim($form->getValue('url'), '/')
            );
            $this->persistDashboard($dashboard);
            $this->redirectNow(
                Url::fromPath(
                    'dashboard',
                    array(
                        'pane' => $form->getValue('pane')
                    )
                )
            );
        }
    }

    private function persistDashboard(Dashboard $dashboard)
    {
        $dashboard->store();
    }
    
    public function indexAction()
    {
        $dashboard = $this->getDashboard();

        if ($this->_getParam('dashboard')) {
            $dashboardName = $this->_getParam('dashboard');
            $dashboard->activate($dashboardName);
        }
        $this->view->tabs = $dashboard->getTabs();
        $this->view->tabs->add("Add", array(
            "title" => "Add Url",
            "iconCls" => "plus",
            "url" => Url::fromPath("dashboard/addurl")
        ));
        $this->view->dashboard = $dashboard;  
    }
}

