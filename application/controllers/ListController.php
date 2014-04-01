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

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabs;
use Icinga\Module\Monitoring\Backend;
use Icinga\Application\Config as IcingaConfig;

use Icinga\Filter\Filterable;
use Icinga\Web\Url;
use Icinga\Data\ResourceFactory;

class ListController extends Controller
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;
    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->backend = Backend::createBackend($this->_getParam('backend'));
        $this->view->grapher = Hook::get('grapher');
        $this->createTabs();
        $this->view->activeRowHref = $this->getParam('detail');
		$this->view->compact = ($this->_request->getParam('view') === 'compact');
    }

    /**
     * Overwrite the backend to use (used for testing)
     *
     * @param Backend $backend      The Backend that should be used for querying
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Apply current users monitoring/filter restrictions to the given query
     *
     * @param $query  Filterable  Query that should be filtered
     * @return Filterable
     */
    protected function applyRestrictions(Filterable $query)
    {
        foreach ($this->getRestrictions('monitoring/filter') as $restriction) {
            parse_str($restriction, $filter);
            foreach ($filter as $k => $v) {
                if ($query->isValidFilterTarget($k)) {
                    // TODO: This is NOT enough. We need to fix filters and get
                    // applyAuthFilters back.
                    $query->where($k, $v);
                }
            }
        }
        return $query;
    }

    protected function addTitleTab($action)
    {
        $this->getTabs()->add($action, array(
            'title' => ucfirst($action),
            'url' => Url::fromPath('monitoring/list/' . $action)
        ))->activate($action);
    }

    /**
     * Return all tabs for this controller
     *
     * @return Tabs
     */
    private function createTabs()
    {
        $tabs = $this->getTabs();
        if (in_array($this->_request->getActionName(), array(
            'hosts',
            'services',
            'eventhistory',
            'notifications'
        ))) {
            $tabs->extend(new OutputFormat())->extend(new DashboardAction());
        }
    }

    public function applicationlogAction()
    {
        $this->addTitleTab('application log');
        $config_ini = IcingaConfig::app()->toArray();
        if (!in_array('logging', $config_ini) || (
                in_array('type', $config_ini['logging']) &&
                    $config_ini['logging']['type'] === 'stream' &&
                in_array('target', $config_ini['logging']) &&
                    file_exists($config_ini['logging']['target'])
            )
        ) {
            $config = ResourceFactory::getResourceConfig('logfile');
            $resource = ResourceFactory::createResource($config);
            $this->view->logData = $resource->select()->paginate();
        } else {
            $this->view->logData = null;
        }
    }
}
// @codingStandardsIgnoreEnd
