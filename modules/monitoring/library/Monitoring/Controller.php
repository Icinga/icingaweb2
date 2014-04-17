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

namespace Icinga\Module\Monitoring;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\File\Csv;
use Icinga\Web\Controller\ActionController;

/**
 * Base class for all monitoring action controller
 */
class Controller extends ActionController
{
    /**
     * Compact layout name
     *
     * Set to a string containing the compact layout name to use when
     * 'compact' is set as the layout parameter, otherwise null
     *
     * @var string
     */
    protected $compactView;

    protected function handleFormatRequest($query)
    {
        if ($this->compactView !== null && ($this->_getParam('view', false) === 'compact')) {
            $this->_helper->viewRenderer($this->compactView);
        }

        if ($this->_getParam('format') === 'sql') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json') {
            header('Content-type: application/json');
            echo json_encode($query->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }

    /**
     * Create a backend
     *
     * @param   string $backendName Name of the backend or null for creating the default backend which is the first INI
     *                              configuration entry not being disabled
     *
     * @return  Backend
     * @throws  ConfigurationError  When no backend has been configured or all backends are disabled or the
     *                              configuration for the requested backend does either not exist or it's disabled
     */
    protected function createBackend($backendName = null)
    {
        $allBackends = array();
        $defaultBackend = null;
        foreach (IcingaConfig::module('monitoring', 'backends') as $name => $config) {
            if (!(bool) $config->get('disabled', false) && $defaultBackend === null) {
                $defaultBackend = $config;
            }
            $allBackends[$name] = $config;
        }
        if (empty($allBackends)) {
            throw new ConfigurationError('No backend has been configured');
        }
        if ($defaultBackend === null) {
            throw new ConfigurationError('All backends are disabled');
        }
        if ($backendName === null) {
            $backendConfig = $defaultBackend;
        } else {
            if (!array_key_exists($backendName, $allBackends)) {
                throw new ConfigurationError('No configuration for backend ' . $backendName);
            }
            $backendConfig = $allBackends[$backendName];
            if ((bool) $backendConfig->get('disabled', false)) {
                throw new ConfigurationError(
                    'Configuration for backend ' . $backendName . ' available but backend is disabled'
                );
            }
        }
        $resource = ResourceFactory::createResource(ResourceFactory::getResourceConfig($backendConfig->resource));
        if ($backendConfig->type === 'ido' && $resource->getDbType() !== 'oracle') {
            // TODO(el): The resource should set the table prefix
            $resource->setTablePrefix('icinga_');
        }
        return new Backend($resource, $backendConfig->type);
    }
}
// @codingStandardsIgnoreEnd
