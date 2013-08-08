<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Backend;

class Monitoring_SummaryController extends ModuleActionController
{
    protected $backend;
    protected $host;
    protected $service;

    public function init()
    {
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $this->view->tabs = $this->getTabs();
    }

    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $tabs->add('hostgroup', array(
            'title'     => 'Hostgroups',
            'url'       => 'monitoring/summary/group',
            'urlParams' => array('by' => 'hostgroup'),
        ));
        $tabs->add('servicegroup', array(
            'title'     => 'Servicegroups',
            'url'       => 'monitoring/summary/group',
            'urlParams' => array('by' => 'servicegroup'),
        ));
        $tabs->activate($this->_getParam('by', 'hostgroup'));
        return $tabs;
    }

    public function historyAction()
    {
        $this->_helper->viewRenderer('history');
    }

    public function groupAction()
    {
        if ($this->_getParam('by') === 'servicegroup') {
            $view = 'servicegroupsummary';
        } else {
            $view = 'hostgroupsummary';
        }
        if (! $this->backend->hasView($view)) {
            $this->view->backend = $this->backend;
            $this->view->view_name = $view;
            $this->_helper->viewRenderer('backend-is-missing');
            return;
        }

        $this->view->preserve = array(
            'problems' => $this->_getParam('problems') ? 'true' : 'false',
            'search'   => $this->_getParam('search')
        );
        $query = $this->backend->select()->from($view);
        $query->where('problems', $this->_getParam('problems') ? 'true' : 'false');
        //$query->where('ss.current_state > 0');
        $query->where('search', $this->_getParam('search'));

        // echo '<pre>' . $query->dump() . '</pre>'; exit;
        $this->view->summary = $query->paginate();

    }

}
// @codingStandardsIgnoreEnd
