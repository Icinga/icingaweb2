<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
* Icinga 2 Web - Head for multiple monitoring frontends
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
* @author Icinga Development Team <info@icinga.org>
*/
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Monitoring\Command;

use Icinga\Authentication\User;
use Icinga\Exception\ProgrammingError;

/**
 * Class Meta
 *
 * Deals with objects and available commands which can be used on the object
 */
class Meta
{
    const DROP_CATEGORY = 'none';

    /**
     * Array of available categories
     * @var array
     */
    private static $categories = array(

    );

    /**
     * Categories to command names
     * @var array
     */
    private static $categoriesToCommands = array();

    /**
     * Command reference list
     * @var array
     */
    private static $commands = array(
        'NONE' => 'none',
        'ADD_HOST_COMMENT' => 'host,interface',
        'DEL_HOST_COMMENT' => 'host',
        'ADD_SVC_COMMENT' => 'service',
        'DEL_SVC_COMMENT' => 'service',
        'ENABLE_SVC_CHECK' => 'service',
        'DISABLE_SVC_CHECK' => 'service',
        'SCHEDULE_SVC_CHECK' => 'service',
        'DELAY_SVC_NOTIFICATION' => 'service',
        'DELAY_HOST_NOTIFICATION' => 'host',
        'DISABLE_NOTIFICATIONS' => 'global',
        'ENABLE_NOTIFICATIONS' => 'global',
        'RESTART_PROCESS' => 'global',
        'SHUTDOWN_PROCESS' => 'global',
        'ENABLE_HOST_SVC_CHECKS' => 'host',
        'DISABLE_HOST_SVC_CHECKS' => 'host',
        'SCHEDULE_HOST_SVC_CHECKS' => 'host,interface',
        'DELAY_HOST_SVC_NOTIFICATIONS' => 'host',
        'DEL_ALL_HOST_COMMENTS' => 'host,',
        'DEL_ALL_SVC_COMMENTS' => 'service',
        'ENABLE_SVC_NOTIFICATIONS' => 'service',
        'DISABLE_SVC_NOTIFICATIONS' => 'service',
        'ENABLE_HOST_NOTIFICATIONS' => 'host,interface',
        'DISABLE_HOST_NOTIFICATIONS' => 'host,interface',
        'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => 'host',
        'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => 'host',
        'ENABLE_HOST_SVC_NOTIFICATIONS' => 'host',
        'DISABLE_HOST_SVC_NOTIFICATIONS' => 'host',
        'PROCESS_SERVICE_CHECK_RESULT' => 'service',
        'SAVE_STATE_INFORMATION' => 'global',
        'READ_STATE_INFORMATION' => 'global',
        'ACKNOWLEDGE_HOST_PROBLEM' => 'host,interface',
        'ACKNOWLEDGE_SVC_PROBLEM' => 'service',
        'START_EXECUTING_SVC_CHECKS' => 'service',
        'STOP_EXECUTING_SVC_CHECKS' => 'service',
        'START_ACCEPTING_PASSIVE_SVC_CHECKS' => 'service',
        'STOP_ACCEPTING_PASSIVE_SVC_CHECKS' => 'service',
        'ENABLE_PASSIVE_SVC_CHECKS' => 'service',
        'DISABLE_PASSIVE_SVC_CHECKS' => 'service',
        'ENABLE_EVENT_HANDLERS' => 'global',
        'DISABLE_EVENT_HANDLERS' => 'global',
        'ENABLE_HOST_EVENT_HANDLER' => 'host,interface',
        'DISABLE_HOST_EVENT_HANDLER' => 'host,interface',
        'ENABLE_SVC_EVENT_HANDLER' => 'service',
        'DISABLE_SVC_EVENT_HANDLER' => 'service',
        'ENABLE_HOST_CHECK' => 'host,interface',
        'DISABLE_HOST_CHECK' => 'host,interface',
        'START_OBSESSING_OVER_SVC_CHECKS' => 'service',
        'STOP_OBSESSING_OVER_SVC_CHECKS' => 'service',
        'REMOVE_HOST_ACKNOWLEDGEMENT' => 'host,interface',
        'REMOVE_SVC_ACKNOWLEDGEMENT' => 'service',
        'SCHEDULE_FORCED_HOST_SVC_CHECKS' => 'host',
        'SCHEDULE_FORCED_SVC_CHECK' => 'service',
        'SCHEDULE_HOST_DOWNTIME' => 'host,interface',
        'SCHEDULE_SVC_DOWNTIME' => 'service',
        'ENABLE_HOST_FLAP_DETECTION' => 'host,interface',
        'DISABLE_HOST_FLAP_DETECTION' => 'host,interface',
        'ENABLE_SVC_FLAP_DETECTION' => 'service',
        'DISABLE_SVC_FLAP_DETECTION' => 'service',
        'ENABLE_FLAP_DETECTION' => 'global',
        'DISABLE_FLAP_DETECTION' => 'global',
        'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS' => 'hostgroup',
        'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS' => 'hostgroup',
        'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS' => 'hostgroup',
        'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS' => 'hostgroup',
        'ENABLE_HOSTGROUP_SVC_CHECKS' => 'hostgroup',
        'DISABLE_HOSTGROUP_SVC_CHECKS' => 'hostgroup',
        'CANCEL_HOST_DOWNTIME' => 'host',
        'CANCEL_SVC_DOWNTIME' => 'service',
        'CANCEL_ACTIVE_HOST_DOWNTIME' => 'host',
        'CANCEL_PENDING_HOST_DOWNTIME' => 'host',
        'CANCEL_ACTIVE_SVC_DOWNTIME' => 'service',
        'CANCEL_PENDING_SVC_DOWNTIME' => 'service',
        'CANCEL_ACTIVE_HOST_SVC_DOWNTIME' => 'host',
        'CANCEL_PENDING_HOST_SVC_DOWNTIME' => 'host',
        'FLUSH_PENDING_COMMANDS' => 'global',
        'DEL_HOST_DOWNTIME' => 'host',
        'DEL_SVC_DOWNTIME' => 'service',
        'ENABLE_FAILURE_PREDICTION' => 'global',
        'DISABLE_FAILURE_PREDICTION' => 'global',
        'ENABLE_PERFORMANCE_DATA' => 'global',
        'DISABLE_PERFORMANCE_DATA' => 'global',
        'SCHEDULE_HOSTGROUP_HOST_DOWNTIME' => 'hostgroup',
        'SCHEDULE_HOSTGROUP_SVC_DOWNTIME' => 'hostgroup',
        'SCHEDULE_HOST_SVC_DOWNTIME' => 'host',
        'PROCESS_HOST_CHECK_RESULT' => 'host,interface',
        'START_EXECUTING_HOST_CHECKS' => 'host,interface',
        'STOP_EXECUTING_HOST_CHECKS' => 'host,interface',
        'START_ACCEPTING_PASSIVE_HOST_CHECKS' => 'host,interface',
        'STOP_ACCEPTING_PASSIVE_HOST_CHECKS' => 'host,interface',
        'ENABLE_PASSIVE_HOST_CHECKS' => 'host.interface',
        'DISABLE_PASSIVE_HOST_CHECKS' => 'host,interface',
        'START_OBSESSING_OVER_HOST_CHECKS' => 'host,interface',
        'STOP_OBSESSING_OVER_HOST_CHECKS' => 'host,interface',
        'SCHEDULE_HOST_CHECK' => 'host,interface',
        'SCHEDULE_FORCED_HOST_CHECK' => 'host,interface',
        'START_OBSESSING_OVER_SVC' => 'global',
        'STOP_OBSESSING_OVER_SVC' => 'global',
        'START_OBSESSING_OVER_HOST' => 'global',
        'STOP_OBSESSING_OVER_HOST' => 'global',
        'ENABLE_HOSTGROUP_HOST_CHECKS' => 'host',
        'DISABLE_HOSTGROUP_HOST_CHECKS' => 'host',
        'ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => 'host',
        'DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => 'host',
        'ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => 'host',
        'DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => 'host',
        'ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => 'servicegroup',
        'ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => 'servicegroup',
        'ENABLE_SERVICEGROUP_SVC_CHECKS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_SVC_CHECKS' => 'servicegroup',
        'ENABLE_SERVICEGROUP_HOST_CHECKS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_HOST_CHECKS' => 'servicegroup',
        'ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => 'servicegroup',
        'ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => 'servicegroup',
        'DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => 'servicegroup',
        'SCHEDULE_SERVICEGROUP_HOST_DOWNTIME' => 'servicegroup',
        'SCHEDULE_SERVICEGROUP_SVC_DOWNTIME' => 'servicegroup',
        'CHANGE_GLOBAL_HOST_EVENT_HANDLER' => 'global',
        'CHANGE_GLOBAL_SVC_EVENT_HANDLER' => 'global',
        'CHANGE_HOST_EVENT_HANDLER' => 'host',
        'CHANGE_SVC_EVENT_HANDLER' => 'service',
        'CHANGE_HOST_CHECK_COMMAND' => 'host',
        'CHANGE_SVC_CHECK_COMMAND' => 'service',
        'CHANGE_NORMAL_HOST_CHECK_INTERVAL' => 'host',
        'CHANGE_NORMAL_SVC_CHECK_INTERVAL' => 'service',
        'CHANGE_RETRY_SVC_CHECK_INTERVAL' => 'service',
        'CHANGE_MAX_HOST_CHECK_ATTEMPTS' => 'host',
        'CHANGE_MAX_SVC_CHECK_ATTEMPTS' => 'service',
        'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME' => 'host',
        'ENABLE_HOST_AND_CHILD_NOTIFICATIONS' => 'host',
        'DISABLE_HOST_AND_CHILD_NOTIFICATIONS' => 'host',
        'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME' => 'host',
        'ENABLE_SERVICE_FRESHNESS_CHECKS' => 'global',
        'DISABLE_SERVICE_FRESHNESS_CHECKS' => 'global',
        'ENABLE_HOST_FRESHNESS_CHECKS' => 'host',
        'DISABLE_HOST_FRESHNESS_CHECKS' => 'host',
        'SET_HOST_NOTIFICATION_NUMBER' => 'host',
        'SET_SVC_NOTIFICATION_NUMBER' => 'service',
        'CHANGE_HOST_CHECK_TIMEPERIOD' => 'host',
        'CHANGE_SVC_CHECK_TIMEPERIOD' => 'service',
        'PROCESS_FILE' => 'global',
        'CHANGE_CUSTOM_HOST_VAR' => 'host',
        'CHANGE_CUSTOM_SVC_VAR' => 'service',
        'CHANGE_CUSTOM_CONTACT_VAR' => 'global',
        'ENABLE_CONTACT_HOST_NOTIFICATIONS' => 'host',
        'DISABLE_CONTACT_HOST_NOTIFICATIONS' => 'host',
        'ENABLE_CONTACT_SVC_NOTIFICATIONS' => 'service',
        'DISABLE_CONTACT_SVC_NOTIFICATIONS' => 'service',
        'ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => 'host',
        'DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => 'host',
        'ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => 'service',
        'DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => 'service',
        'CHANGE_RETRY_HOST_CHECK_INTERVAL' => 'host',
        'SEND_CUSTOM_HOST_NOTIFICATION' => 'host',
        'SEND_CUSTOM_SVC_NOTIFICATION' => 'service',
        'CHANGE_HOST_NOTIFICATION_TIMEPERIOD' => 'host',
        'CHANGE_SVC_NOTIFICATION_TIMEPERIOD' => 'service',
        'CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD' => 'host',
        'CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD' => 'service',
        'CHANGE_HOST_MODATTR' => 'host',
        'CHANGE_SVC_MODATTR' => 'service',
        'CHANGE_CONTACT_MODATTR' => 'contact',
        'CHANGE_CONTACT_MODHATTR' => 'contact',
        'CHANGE_CONTACT_MODSATTR' => 'contact',
        'SYNC_STATE_INFORMATION' => 'contact',
        'DEL_DOWNTIME_BY_HOST_NAME' => 'host',
        'DEL_DOWNTIME_BY_HOSTGROUP_NAME' => 'hostgroup',
        'DEL_DOWNTIME_BY_START_TIME_COMMENT' => 'comment',
        'ACKNOWLEDGE_HOST_PROBLEM_EXPIRE' => 'host',
        'ACKNOWLEDGE_SVC_PROBLEM_EXPIRE' => 'service',
        'DISABLE_NOTIFICATIONS_EXPIRE_TIME' => 'global',
        'CUSTOM_COMMAND' => 'none',
    );

    /**
     * Labels of commands
     * @var array
     */
    private static $commandLabels = array();

    /**
     * Initialize command structures only once
     */
    private static function initCommandStructure()
    {
        static $initialized = false;

        if ($initialized === true) {
            return;
        }

        $categories = array();
        foreach (self::$commands as $commandName => $categoryName) {
            $flags = explode(',', $categoryName);
            $categoryName = array_shift($flags);
            // We do not want to see useless commands
            if ($categoryName === self::DROP_CATEGORY) {
                unset(self::$commands[$commandName]);
                continue;
            }

            $categories[$categoryName] = null;

            if (array_key_exists($categoryName, self::$categoriesToCommands) === false) {
                self::$categoriesToCommands[$categoryName] = array();
            }

            self::$categoriesToCommands[$categoryName][$commandName] = $flags;
        }

        self::$categories = $categories;

        $initialized = true;
    }

    /**
     * Creates a new object
     */
    public function __construct()
    {
        self::initCommandStructure();
    }

    /**
     * Return a full list of commands
     * @return string[]
     */
    public function getCommands()
    {
        static $commands = null;

        if ($commands === null) {
            $commands = array_keys(self::$commands);
        }

        return $commands;
    }

    /**
     * Return all commands for a category
     * @param string $categoryName
     * @return string[]
     */
    public function getCommandsForCategory($categoryName)
    {
        static $commands = null;

        $this->assertCategoryExistence($categoryName);

        if (!$commands === null) {
            $commands = array_keys(self::$categoriesToCommands[$category]);
        }

        return $commands;
    }

    /**
     * Test for category existence
     * @param string $categoryName
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function assertCategoryExistence($categoryName)
    {
        if (array_key_exists($categoryName, self::$categoriesToCommands) === false) {
            throw new ProgrammingError('Category does not exists: '. $categoryName);
        }
    }

    /**
     * Return a list of all categories
     * @return string[]
     */
    public function getCategories()
    {
        return self::$categories;
    }

    public function getCommandsForObject(\stdClass $object, User $user = null)
    {
        $items = self::$categoriesToCommands['host'];
        var_dump($items);
    }
}