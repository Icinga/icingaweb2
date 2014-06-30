<?php
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
use Icinga\Application\Config as IcingaConfig;
use Icinga\Web\Url;
use Icinga\Data\ResourceFactory;

/**
 * Class ListController
 *
 * Application wide controller for various listing actions
 */
class ListController extends Controller
{
    /**
     * Add title tab
     *
     * @param string $action
     */
    protected function addTitleTab($action)
    {
        $this->getTabs()->add($action, array(
            'title' => ucfirst($action),
            'url' => Url::fromPath(
                    'list/'
                    . str_replace(' ', '', $action)
                )
        ))->activate($action);
    }

    /**
     * Display the application log
     */
    public function applicationlogAction()
    {
        $this->addTitleTab('application log');
        $config_ini = IcingaConfig::app()->toArray();
        if (!in_array('logging', $config_ini) || (
                in_array('type', $config_ini['logging']) &&
                    $config_ini['logging']['type'] === 'file' &&
                in_array('target', $config_ini['logging']) &&
                    file_exists($config_ini['logging']['target'])
            )
        ) {
            $config = ResourceFactory::getResourceConfig('logfile');
            $resource = ResourceFactory::createResource($config);
            $this->view->logData = $resource->select()->order('DESC')->paginate();
        } else {
            $this->view->logData = null;
        }
    }
}
