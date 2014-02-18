<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Web\Controller\ActionController;
use \Icinga\Web\Url;
use \Icinga\Application\Icinga;
use \Icinga\Web\Widget\Dashboard;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Form\Dashboard\AddUrlForm;
use \Icinga\Exception\ConfigurationError;

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
     * @param   string $config The config to read the dashboard from, or 'dashboard/dashboard' if none is given
     *
     * @return  \Icinga\Web\Widget\Dashboard
     */
    private function getDashboard($config = 'dashboard/dashboard')
    {
        $dashboard = new Dashboard();
        $dashboard->readConfig(IcingaConfig::app($config));
        return $dashboard;
    }

    /**
     * Remove a component from the pane identified by the 'pane' parameter
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
        } catch (ConfigurationError $exc ) {
            $this->_helper->viewRenderer('show_configuration');
            $this->view->exceptionMessage = $exc->getMessage();
            $this->view->iniConfigurationString = $dashboard->toIni();
        }
    }

    /**
     * Display the form for adding new components or add the new component if submitted
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
                    Url::fromPath('dashboard', array('pane' => $form->getValue('pane')))
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
     */
    public function indexAction()
    {
        $dashboard = $this->getDashboard();
        if ($this->_getParam('pane')) {
            $pane = $this->_getParam('pane');
            $dashboard->activate($pane);
        } else {
            $dashboard->determineActivePane();
        }
        $this->view->tabs = $dashboard->getTabs();
        $this->view->tabs->add(
            'Add',
            array(
                'title' => '+',
                'url' => Url::fromPath('dashboard/addurl')
            )
        );
        $this->view->dashboard = $dashboard;
    }
}
// @codingStandardsIgnoreEnd
