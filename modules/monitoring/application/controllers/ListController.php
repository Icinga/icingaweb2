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


use \Icinga\Application\Benchmark;
use \Icinga\Data\Db\Query;
use \Icinga\File\Csv;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Web\Hook;
use \Icinga\Web\Widget\Tabextension\BasketAction;
use \Icinga\Web\Widget\Tabextension\DashboardAction;
use \Icinga\Web\Widget\Tabextension\OutputFormat;
use \Icinga\Web\Widget\Tabs;
use \Monitoring\Backend;

class Monitoring_ListController extends ActionController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Set to a string containing the compact layout name to use when
     * 'compact' is set as the layout parameter, otherwise null
     *
     * @var string
     */
    private $compactView;

    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $this->view->grapher = Hook::get('grapher');
        $this->createTabs();
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
     * Display host list
     */
    public function hostsAction()
    {
        Benchmark::measure("hostsAction::query()");
        $this->compactView = "hosts-compact";
        $this->view->hosts = $this->query(
            'status',
            array(
                'host_icon_image',
                'host_name',
                'host_state',
                'host_address',
                'host_acknowledged',
                'host_output',
                'host_long_output',
                'host_in_downtime',
                'host_is_flapping',
                'host_state_type',
                'host_handled',
                'host_last_check',
                'host_last_state_change',
                'host_notifications_enabled',
                'host_unhandled_service_count',
                'host_action_url',
                'host_notes_url',
                'host_last_comment'
            )
        );
    }

    /**
     * Display service list
     */
    public function servicesAction()
    {
        if ($this->_getParam('_statetype', 'soft') === 'soft') {
            $state_column = 'service_state';
            $state_change_column = 'service_last_state_change';
        } else {
            $state_column = 'service_hard_state';
            $state_change_column = 'service_last_hard_state_change';
        }
        $this->compactView = "services-compact";

        $this->view->services = $this->query('status', array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state' => $state_column,
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_last_state_change' => $state_change_column,
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_last_comment'
        ));
        $this->inheritCurrentSortColumn();
    }

    /**
     * Fetch the current downtimes and put them into the view property `downtimes`
     */
    public function downtimesAction()
    {
         $query = $this->backend->select()
            ->from('downtime',array(
                'host_name',
                'object_type',
                'service_description',
                'downtime_entry_time',
                'downtime_internal_downtime_id',
                'downtime_author_name',
                'downtime_comment_data',
                'downtime_duration',
                'downtime_scheduled_start_time',
                'downtime_scheduled_end_time',
                'downtime_is_fixed',
                'downtime_is_in_effect',
                'downtime_triggered_by_id',
                'downtime_trigger_time'
        ));
        if (!$this->_getParam('sort')) {
            $query->order('downtime_is_in_effect');
        }
        $this->view->downtimes = $query->applyRequest($this->_request);
        $this->inheritCurrentSortColumn();
    }

    /**
     * Display notification overview
     */
    public function notificationsAction()
    {
        $this->view->notifications = $this->query(
            'notification',
            array(
                'host_name',
                'service_description',
                'notification_type',
                'notification_reason',
                'notification_start_time',
                'notification_contact',
                'notification_information',
                'notification_command'
            )
        );
        if (!$this->_getParam('sort')) {
            $this->view->notifications->order('notification_start_time DESC');
        }

        $this->inheritCurrentSortColumn();
    }

    /**
     * Create query
     *
     * @param string $view
     * @param array  $columns
     *
     * @return Query
     */
    private function query($view, $columns)
    {
        $extra = preg_split(
            '~,~',
            $this->_getParam('extracolumns', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $this->view->extraColumns = $extra;
        $query = $this->backend->select()
            ->from($view, array_merge($columns, $extra))
            ->applyRequest($this->_request);
        $this->handleFormatRequest($query);
        return $query;
    }

    /**
     * Handle the 'format' and 'view' parameter
     *
     * @param Query $query The current query
     */
    private function handleFormatRequest($query)
    {
        if ($this->compactView !== null && ($this->_getParam('view', false) === 'compact')) {
            $this->_helper->viewRenderer($this->compactView);
        }

        if ($this->_getParam('format') === 'sql') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->getQuery()->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json')
        {
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
     * Return all tabs for this controller
     *
     * @return Tabs
     */
    private function createTabs()
    {

        $tabs = $this->getTabs();
        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction())
            ->extend(new BasketAction());
    }

    /**
     * Let the current response inherit the used sort column by applying it to the view property `sort`
     */
    private function inheritCurrentSortColumn()
    {
        if ($this->_getParam('sort')) {
            $this->view->sort = $this->_getParam('sort');
        }
    }
}
// @codingStandardsIgnoreEnd
