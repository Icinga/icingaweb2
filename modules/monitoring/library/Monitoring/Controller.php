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
use Icinga\Module\Monitoring\DataView\ServiceStatus as ServiceStatusView;
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

    /**
     * Retrieve services from either given parameters or request
     *
     * @param   array $params
     *
     * @return  \Zend_Paginator
     */
    protected function fetchServices(array $params = null)
    {
        $columns = array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_perfdata',
            'service_attempt',
            'service_last_state_change',
            'service_icon_image',
            // 'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            // 'service_last_comment',
            'service_active_checks_enabled',
            'service_passive_checks_enabled',
            'current_check_attempt' => 'service_current_check_attempt',
            'max_check_attempts'    => 'service_max_check_attempts'
        );
        if ($params === null) {
            $query = ServiceStatusView::fromRequest(
                $this->_request,
                $columns
            )->getQuery();
        } else {
            $params['backend'] = $this->_request->getParam('backend');
            $query = ServiceStatusView::fromParams(
                $params,
                $columns
            )->getQuery();
        }
        $this->handleFormatRequest($query);
        return $query;
    }

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
}
// @codingStandardsIgnoreEnd
