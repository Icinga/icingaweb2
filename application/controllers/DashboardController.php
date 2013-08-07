<?php

use Icinga\Web\ActionController;
use Icinga\Web\Url;
use Icinga\Application\Icinga;
use Icinga\Web\Widget\Dashboard;
use Icinga\Config\Config as IcingaConfig;
use Icinga\Form\Dashboard\AddUrlForm;
use Icinga\Exception\ConfigurationError;


/**
 * Handle creation, removal and displaying of dashboards, panes and components
 *
 * @see Icinga\Web\Widget\Dashboard for more information about dashboards
 */
class DashboardController extends ActionController
{

    /**
     * Retrieve a dashboard from the provided config
     *
     * @param string $config    The config to read the dashboard from, or 'dashboard/dashboard' if none is given
     *
     * @return Dashboard
     */
    private function getDashboard($config = 'dashboard/dashboard')
    {
        $dashboard = new Dashboard();
        $dashboard->readConfig(IcingaConfig::app($config));
        return $dashboard;
    }

    /**
     * Remove a component from the pane identified by the 'pane' parameter
     *
     */
    public function removecomponentAction()
    {
        $pane =  $this->_getParam('pane');
        $dashboard = $this->getDashboard();
        try {
            $dashboard->removeComponent(
                $pane,
                $this->_getParam('component')
            )->store();
            $this->redirectNow(Url::fromPath('dashboard', array('pane' => $pane)));
        } catch(ConfigurationError $exc ) {

            $this->_helper->viewRenderer('show_configuration');
            $this->view->exceptionMessage = $exc->getMessage();
            $this->view->iniConfigurationString = $dashboard->toIni();
        }
    }


    /**
     * Display the form for adding new components or add the new component if submitted
     *
     */
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
            try {
                $dashboard->store();
                $this->redirectNow(
                    Url::fromPath('dashboard',array('pane' => $form->getValue('pane')))
                );
            } catch (ConfigurationError $exc) {
                $this->_helper->viewRenderer('show_configuration');
                $this->view->exceptionMessage = $exc->getMessage();
                $this->view->iniConfigurationString = $dashboard->toIni();
            }
        }
    }

    /**
     * Display the dashboard with the pane set in the 'pane' request parameter
     *
     * If no pane is submitted or the submitted one doesn't exist, the default pane is
     * displayed (normally the first one)
     *
     */
    public function indexAction()
    {
        $dashboard = $this->getDashboard();

        if ($this->_getParam('dashboard')) {
            $dashboardName = $this->_getParam('dashboard');
            $dashboard->activate($dashboardName);
        }
        $this->view->tabs = $dashboard->getTabs();
        $this->view->tabs->add('Add', array(
            'title' => 'Add Url',
            'iconCls' => 'plus',
            'url' => Url::fromPath('dashboard/addurl')
        ));
        $this->view->dashboard = $dashboard;  
    }
}

