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

namespace Icinga\Module\Monitoring\Command;

use Icinga\User;
use Icinga\Exception\ProgrammingError;

/**
 * Class Meta
 *
 * Deals with objects and available commands which can be used on the object
 */
class Meta
{
    /**
     * Category name which is useless
     */
    const DROP_CATEGORY = 'none';

    /**
     * Interface type for small interfaces
     *
     * Only important commands
     */
    const TYPE_SMALL = 'small';

    /**
     * Interface type for full featured interface
     *
     * All commands are shown
     */
    const TYPE_FULL = 'full';

    const CMD_DISABLE_ACTIVE_CHECKS = 1;
    const CMD_ENABLE_ACTIVE_CHECKS = 2;
    const CMD_RESCHEDULE_NEXT_CHECK = 3;
    const CMD_SUBMIT_PASSIVE_CHECK_RESULT = 4;
    const CMD_STOP_OBSESSING = 5;
    const CMD_START_OBSESSING = 6;
    const CMD_STOP_ACCEPTING_PASSIVE_CHECKS = 7;
    const CMD_START_ACCEPTING_PASSIVE_CHECKS = 8;
    const CMD_DISABLE_NOTIFICATIONS = 9;
    const CMD_ENABLE_NOTIFICATIONS = 10;
    const CMD_SEND_CUSTOM_NOTIFICATION = 11;
    const CMD_SCHEDULE_DOWNTIME = 12;
    const CMD_SCHEDULE_DOWNTIMES_TO_ALL = 13;
    const CMD_REMOVE_DOWNTIMES_FROM_ALL = 14;
    const CMD_DISABLE_NOTIFICATIONS_FOR_ALL = 15;
    const CMD_ENABLE_NOTIFICATIONS_FOR_ALL = 16;
    const CMD_RESCHEDULE_NEXT_CHECK_TO_ALL = 17;
    const CMD_DISABLE_ACTIVE_CHECKS_FOR_ALL = 18;
    const CMD_ENABLE_ACTIVE_CHECKS_FOR_ALL = 19;
    const CMD_DISABLE_EVENT_HANDLER = 20;
    const CMD_ENABLE_EVENT_HANDLER = 21;
    const CMD_DISABLE_FLAP_DETECTION = 22;
    const CMD_ENABLE_FLAP_DETECTION = 23;
    const CMD_ADD_COMMENT = 24;
    const CMD_RESET_ATTRIBUTES = 25;
    const CMD_ACKNOWLEDGE_PROBLEM = 26;
    const CMD_REMOVE_ACKNOWLEDGEMENT = 27;
    const CMD_DELAY_NOTIFICATION = 28;
    const CMD_REMOVE_DOWNTIME = 29;

    /**
     * Filter array for array displayed in small interfaces
     * @var int[]
     */
    private static $commandSmallFilter = array(
        self::CMD_RESCHEDULE_NEXT_CHECK,
        self::CMD_ACKNOWLEDGE_PROBLEM,
        self::CMD_REMOVE_ACKNOWLEDGEMENT
    );

    /**
     * Information about interface commands
     *
     * With following structure
     * <pre>
     * array(
     *  self::CMD_CONSTANT_* => array(
     *   '<LONG DESCRIPTION WHERE %s is the type, e.g. host or service>',
     *   '<SHORT DESCRIPTION WHERE %s is the type, e.g. host or service>',
     *   '[ICON CSS CLASS]',
     *   '[BUTTON CSS CLASS]',
     *
     *    // Maybe any other options later on
     *  )
     * )
     * </pre>
     *
     * @var array
     */
    private static $commandInformation = array(
        self::CMD_DISABLE_ACTIVE_CHECKS => array(
            'Disable Active Checks For This %s', // Long description (mandatory)
            'Disable Active Checks', // Short description (mandatory)
            '', // Icon anything (optional)
            '' // Button css cls (optional)
        ),
        self::CMD_ENABLE_ACTIVE_CHECKS => array(
            'Enable Active Checks For This %s',
            'Enable Active Checks',
            ''
        ),
        self::CMD_RESCHEDULE_NEXT_CHECK => array(
            'Reschedule Next Service Check',
            'Recheck',
            '',
            'btn-success'
        ),
        self::CMD_SUBMIT_PASSIVE_CHECK_RESULT => array(
            'Submit Passive Check Result',
            'Submit Check Result',
            ''
        ),
        self::CMD_STOP_OBSESSING => array(
            'Stop Obsessing Over This %s',
            'Stop Obsessing',
            ''
        ),
        self::CMD_START_OBSESSING => array(
            'Start Obsessing Over This %s',
            'Start Obsessing',
            ''
        ),
        self::CMD_STOP_ACCEPTING_PASSIVE_CHECKS => array(
            'Stop Accepting Passive Checks For This %s',
            'Stop Passive Checks',
            ''
        ),
        self::CMD_START_ACCEPTING_PASSIVE_CHECKS => array(
            'Start Accepting Passive Checks For This %s',
            'Start Passive Checks',
            ''
        ),
        self::CMD_DISABLE_NOTIFICATIONS => array(
            'Disable Notifications For This %s',
            'Disable Notifications',
            ''
        ),
        self::CMD_ENABLE_NOTIFICATIONS => array(
            'Enable Notifications For This %s',
            'Enable Notifications',
            ''
        ),
        self::CMD_SEND_CUSTOM_NOTIFICATION => array(
            'Send Custom %s Notification',
            'Send Notification',
            ''
        ),
        self::CMD_SCHEDULE_DOWNTIME => array(
            'Schedule Downtime For This %s',
            'Schedule Downtime',
            ''
        ),
        self::CMD_SCHEDULE_DOWNTIMES_TO_ALL => array(
            'Schedule Downtime For This %s And All Services',
            'Schedule Services Downtime',
            ''
        ),
        self::CMD_REMOVE_DOWNTIMES_FROM_ALL => array(
            'Remove Downtime(s) For This %s And All Services',
            'Remove Downtime(s)',
            ''
        ),
        self::CMD_DISABLE_NOTIFICATIONS_FOR_ALL => array(
            'Disable Notification For All Service On This %s',
            'Disable Service Notifications',
            ''
        ),
        self::CMD_ENABLE_NOTIFICATIONS_FOR_ALL => array(
            'Enable Notification For All Service On This %s',
            'Enable Service Notifications',
            ''
        ),
        self::CMD_RESCHEDULE_NEXT_CHECK_TO_ALL => array(
            'Schedule a Check Of All Service On This %s',
            'Recheck All Services',
            '',
            'btn-success'
        ),
        self::CMD_DISABLE_ACTIVE_CHECKS_FOR_ALL => array(
            'Disable Checks For All Services On This %s',
            'Disable Service Checks',
            ''
        ),
        self::CMD_ENABLE_ACTIVE_CHECKS_FOR_ALL => array(
            'Enable Checks For All Services On This %s',
            'Enable Service Checks',
            ''
        ),
        self::CMD_DISABLE_EVENT_HANDLER => array(
            'Disable Event Handler For This %s',
            'Disable Event Handler',
            ''
        ),
        self::CMD_ENABLE_EVENT_HANDLER => array(
            'Enable Event Handler For This %s',
            'Enable Event Handler',
            ''
        ),
        self::CMD_DISABLE_FLAP_DETECTION => array(
            'Disable Flap Detection For This %s',
            'Disable Flap Detection',
            ''
        ),
        self::CMD_ENABLE_FLAP_DETECTION => array(
            'Enable Flap Detection For This %s',
            'Enable Flap Detection',
            ''
        ),
        self::CMD_ADD_COMMENT => array(
            'Add New %s Comment',
            'Add Comment',
            ''
        ),
        self::CMD_RESET_ATTRIBUTES => array(
            'Reset Modified Attributes',
            'Reset Attributes',
            '',
            'btn-danger'
        ),
        self::CMD_ACKNOWLEDGE_PROBLEM => array(
            'Acknowledge %s Problem',
            'Acknowledge',
            '',
            'btn-warning'
        ),
        self::CMD_REMOVE_ACKNOWLEDGEMENT => array(
            'Remove %s Acknowledgement',
            'Remove Acknowledgement',
            '',
            'btn-warning'
        ),
        self::CMD_DELAY_NOTIFICATION => array(
            'Delay Next %s Notification',
            'Delay Notification',
            ''
        ),
    );

    /**
     * An mapping array which is valid for hosts and services
     * @var array
     */
    private static $defaultObjectCommands = array(
        self::CMD_DISABLE_ACTIVE_CHECKS,
        self::CMD_ENABLE_ACTIVE_CHECKS,
        self::CMD_RESCHEDULE_NEXT_CHECK,
        self::CMD_SUBMIT_PASSIVE_CHECK_RESULT,
        self::CMD_STOP_OBSESSING,
        self::CMD_START_OBSESSING,
        self::CMD_ACKNOWLEDGE_PROBLEM,
        self::CMD_REMOVE_ACKNOWLEDGEMENT,
        self::CMD_STOP_ACCEPTING_PASSIVE_CHECKS,
        self::CMD_START_ACCEPTING_PASSIVE_CHECKS,
        self::CMD_DISABLE_NOTIFICATIONS,
        self::CMD_ENABLE_NOTIFICATIONS,
        self::CMD_SEND_CUSTOM_NOTIFICATION,
        self::CMD_SCHEDULE_DOWNTIME,
        self::CMD_SCHEDULE_DOWNTIMES_TO_ALL,
        self::CMD_REMOVE_DOWNTIMES_FROM_ALL,
        self::CMD_DISABLE_NOTIFICATIONS_FOR_ALL,
        self::CMD_ENABLE_NOTIFICATIONS_FOR_ALL,
        self::CMD_RESCHEDULE_NEXT_CHECK_TO_ALL,
        self::CMD_DISABLE_ACTIVE_CHECKS_FOR_ALL,
        self::CMD_ENABLE_ACTIVE_CHECKS_FOR_ALL,
        self::CMD_DISABLE_EVENT_HANDLER,
        self::CMD_ENABLE_EVENT_HANDLER,
        self::CMD_DISABLE_FLAP_DETECTION,
        self::CMD_ENABLE_FLAP_DETECTION,
        self::CMD_ADD_COMMENT,
        self::CMD_RESET_ATTRIBUTES,
        self::CMD_DELAY_NOTIFICATION
    );

    /**
     * Command mapper
     *
     * Holds information about commands for object types
     *
     * Please note that host and service are implicit initialized!
     * see $defaultObjectCommands for definition
     *
     * @var array
     */
    private static $objectCommands = array(
        // 'host' => self::$defaultObjectCommands,
        // 'service' => self::$defaultObjectCommands,
        // -> this is done in self::initCommandStructure()
        //
        // Real other commands structures:
        // NONE
    );

    /**
     * Array of modifier methods in this class
     *
     * Maps object type to modifier method which rewrites
     * visible commands depending on object states
     *
     * @var array
     */
    private static $commandProcessorMethods = array(
        'host' => 'defaultCommandProcessor',
        'service' => 'defaultCommandProcessor'
    );

    /**
     * Init flag for lazy, static data preparation
     * @var bool
     */
    private static $initialized = false;

    /**
     * Array of available categories
     * @var array
     */
    private static $rawCategories = array();

    /**
     * Categories to command names
     * @var array
     */
    private static $rawCommandCategories = array();

    /**
     * Command reference list
     * @var array
     */
    private static $rawCommands = array(
        'NONE' => 'none',
        'ADD_HOST_COMMENT' => 'host',
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
        'SCHEDULE_HOST_SVC_CHECKS' => 'host',
        'DELAY_HOST_SVC_NOTIFICATIONS' => 'host',
        'DEL_ALL_HOST_COMMENTS' => 'host',
        'DEL_ALL_SVC_COMMENTS' => 'service',
        'ENABLE_SVC_NOTIFICATIONS' => 'service',
        'DISABLE_SVC_NOTIFICATIONS' => 'service',
        'ENABLE_HOST_NOTIFICATIONS' => 'host',
        'DISABLE_HOST_NOTIFICATIONS' => 'host',
        'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => 'host',
        'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => 'host',
        'ENABLE_HOST_SVC_NOTIFICATIONS' => 'host',
        'DISABLE_HOST_SVC_NOTIFICATIONS' => 'host',
        'PROCESS_SERVICE_CHECK_RESULT' => 'service',
        'SAVE_STATE_INFORMATION' => 'global',
        'READ_STATE_INFORMATION' => 'global',
        'ACKNOWLEDGE_HOST_PROBLEM' => 'host',
        'ACKNOWLEDGE_SVC_PROBLEM' => 'service',
        'START_EXECUTING_SVC_CHECKS' => 'service',
        'STOP_EXECUTING_SVC_CHECKS' => 'service',
        'START_ACCEPTING_PASSIVE_SVC_CHECKS' => 'service',
        'STOP_ACCEPTING_PASSIVE_SVC_CHECKS' => 'service',
        'ENABLE_PASSIVE_SVC_CHECKS' => 'service',
        'DISABLE_PASSIVE_SVC_CHECKS' => 'service',
        'ENABLE_EVENT_HANDLERS' => 'global',
        'DISABLE_EVENT_HANDLERS' => 'global',
        'ENABLE_HOST_EVENT_HANDLER' => 'host',
        'DISABLE_HOST_EVENT_HANDLER' => 'host',
        'ENABLE_SVC_EVENT_HANDLER' => 'service',
        'DISABLE_SVC_EVENT_HANDLER' => 'service',
        'ENABLE_HOST_CHECK' => 'host',
        'DISABLE_HOST_CHECK' => 'host',
        'START_OBSESSING_OVER_SVC_CHECKS' => 'service',
        'STOP_OBSESSING_OVER_SVC_CHECKS' => 'service',
        'REMOVE_HOST_ACKNOWLEDGEMENT' => 'host',
        'REMOVE_SVC_ACKNOWLEDGEMENT' => 'service',
        'SCHEDULE_FORCED_HOST_SVC_CHECKS' => 'host',
        'SCHEDULE_FORCED_SVC_CHECK' => 'service',
        'SCHEDULE_HOST_DOWNTIME' => 'host',
        'SCHEDULE_SVC_DOWNTIME' => 'service',
        'ENABLE_HOST_FLAP_DETECTION' => 'host',
        'DISABLE_HOST_FLAP_DETECTION' => 'host',
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
        'PROCESS_HOST_CHECK_RESULT' => 'host',
        'START_EXECUTING_HOST_CHECKS' => 'host',
        'STOP_EXECUTING_HOST_CHECKS' => 'host',
        'START_ACCEPTING_PASSIVE_HOST_CHECKS' => 'host',
        'STOP_ACCEPTING_PASSIVE_HOST_CHECKS' => 'host',
        'ENABLE_PASSIVE_HOST_CHECKS' => 'host',
        'DISABLE_PASSIVE_HOST_CHECKS' => 'host',
        'START_OBSESSING_OVER_HOST_CHECKS' => 'host',
        'STOP_OBSESSING_OVER_HOST_CHECKS' => 'host',
        'SCHEDULE_HOST_CHECK' => 'host',
        'SCHEDULE_FORCED_HOST_CHECK' => 'host',
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
     * Initialize command structures only once
     */
    private static function initCommandStructure()
    {
        if (self::$initialized === true) {
            return;
        }

        /*
         * Build everything for raw commands
         */
        $categories = array();
        foreach (self::$rawCommands as $commandName => $categoryName) {
            // We do not want to see useless commands
            if ($categoryName === self::DROP_CATEGORY) {
                unset(self::$rawCommands[$commandName]);
                continue;
            }

            $categories[$categoryName] = null;

            if (array_key_exists($categoryName, self::$rawCommandCategories) === false) {
                self::$rawCommandCategories[$categoryName] = array();
            }

            self::$rawCommandCategories[$categoryName][] = $commandName;
        }

        self::$rawCategories = array_keys($categories);
        sort(self::$rawCategories);

        /*
         * Build everything for object commands
         */
        self::$objectCommands['host'] = self::$defaultObjectCommands;
        self::$objectCommands['service'] = self::$defaultObjectCommands;

        self::$initialized = true;
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
    public function getRawCommands()
    {
        static $commands = null;

        if ($commands === null) {
            $commands = array_keys(self::$rawCommands);
        }

        return $commands;
    }

    /**
     * Return all commands for a category
     * @param string $categoryName
     * @return string[]
     */
    public function getRawCommandsForCategory($categoryName)
    {
        $this->assertRawCategoryExistence($categoryName);
        return array_values(self::$rawCommandCategories[$categoryName]);
    }

    /**
     * Test for category existence
     * @param string $categoryName
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function assertRawCategoryExistence($categoryName)
    {
        if (array_key_exists($categoryName, self::$rawCommandCategories) === false) {
            throw new ProgrammingError('Category does not exists: ' . $categoryName);
        }
    }

    /**
     * Return a list of all categories
     * @return string[]
     */
    public function getRawCategories()
    {
        return self::$rawCategories;
    }

    /**
     * Returns the type of object
     *
     * This is made by the first key of property
     * e.g.
     *      $object->host_state
     *      Type is 'host'
     *
     * @param \stdClass $object
     * @return mixed
     */
    private function getObjectType(\stdClass $object)
    {
        $objectKeys = array_keys(get_object_vars($object));
        $firstKeys = explode('_', array_shift($objectKeys), 2);
        return array_shift($firstKeys);
    }

    /**
     * Returns method name based on object type
     * @param string $type
     * @return string
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function getCommandProcessorMethod($type)
    {
        if (array_key_exists($type, self::$commandProcessorMethods)) {
            $method = self::$commandProcessorMethods[$type];
            if (is_callable(array(&$this, $method))) {
                return $method;
            }
        }

        throw new ProgrammingError('Type has no command processor: '. $type);
    }

    /**
     * Return interface commands by object type
     * @param string $type
     * @return array
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function getCommandsByType($type)
    {
        if (array_key_exists($type, self::$objectCommands)) {
            return self::$objectCommands[$type];
        }

        throw new ProgrammingError('Type has no commands defined: '. $type);
    }

    /**
     * Modifies data objects to drop their object type
     *
     * - host_state will be state
     * - service_state will be also state
     * - And so on
     *
     * @param \stdClass $object
     * @param $type
     * @return object
     */
    private function dropTypeAttributes(\stdClass $object, $type)
    {
        $objectData = get_object_vars($object);
        foreach ($objectData as $propertyName => $propertyValue) {
            $newProperty = str_replace($type. '_', '', $propertyName);
            $objectData[$newProperty] = $propertyValue;
            unset($objectData[$propertyName]);
        }
        return (object)$objectData;
    }

    /**
     * Default processor for host and service objects
     *
     * Drop commands from list based on states and object properties
     *
     * @param \stdClass $object
     * @param array $commands
     * @param string $type
     * @return array
     */
    private function defaultCommandProcessor(\stdClass $object, array $commands, $type)
    {
        $object = $this->dropTypeAttributes($object, $type);

        $commands = array_flip($commands);

        if ($object->active_checks_enabled === '1') {
            unset($commands[self::CMD_ENABLE_ACTIVE_CHECKS]);
        } else {
            unset($commands[self::CMD_DISABLE_ACTIVE_CHECKS]);
        }

        if ($object->passive_checks_enabled !== '1') {
            unset($commands[self::CMD_SUBMIT_PASSIVE_CHECK_RESULT]);
        }

        if ($object->passive_checks_enabled === '1') {
            unset($commands[self::CMD_STOP_ACCEPTING_PASSIVE_CHECKS]);
        } else {
            unset($commands[self::CMD_START_ACCEPTING_PASSIVE_CHECKS]);
        }

        if ($object->obsessing === '1') {
            unset($commands[self::CMD_START_OBSESSING]);
        } else {
            unset($commands[self::CMD_STOP_OBSESSING]);
        }

        if ($object->state !== '0') {
            if ($object->acknowledged === '1') {
                unset($commands[self::CMD_ACKNOWLEDGE_PROBLEM]);
            } else {
                unset($commands[self::CMD_REMOVE_ACKNOWLEDGEMENT]);
            }
        } else {
            unset($commands[self::CMD_ACKNOWLEDGE_PROBLEM]);
            unset($commands[self::CMD_REMOVE_ACKNOWLEDGEMENT]);
        }

        if ($object->notifications_enabled === '1') {
            unset($commands[self::CMD_ENABLE_NOTIFICATIONS]);
        } else {
            unset($commands[self::CMD_DISABLE_NOTIFICATIONS]);
        }

        if ($object->event_handler_enabled === '1') {
            unset($commands[self::CMD_ENABLE_EVENT_HANDLER]);
        } else {
            unset($commands[self::CMD_DISABLE_EVENT_HANDLER]);
        }

        if ($object->flap_detection_enabled === '1') {
            unset($commands[self::CMD_ENABLE_FLAP_DETECTION]);
        } else {
            unset($commands[self::CMD_DISABLE_FLAP_DETECTION]);
        }

        return array_flip($commands);
    }

    /**
     * Creates structure to work with in interfaces
     *
     * @param array $commands
     * @param string $objectType
     * @return array
     */
    private function buildInterfaceConfiguration(array $commands, $objectType)
    {
        $out = array();
        $objectType = ucfirst($objectType);
        foreach ($commands as $index => $commandId) {

            $command = new \stdClass();
            $command->id = $commandId;
            if (array_key_exists($commandId, self::$commandInformation)) {
                $command->shortDescription = sprintf(self::$commandInformation[$commandId][1], $objectType);
                $command->longDescription = sprintf(self::$commandInformation[$commandId][0], $objectType);
                $command->iconCls =
                    (isset(self::$commandInformation[$commandId][2]))
                    ? self::$commandInformation[$commandId][2]
                    : '';
                $command->btnCls =
                    (isset(self::$commandInformation[$commandId][3]))
                    ? self::$commandInformation[$commandId][3]
                    : '';
            }

            $out[] = $command;
        }

        return $out;
    }

    /**
     * Drop commands
     * For small interfaces or bypass when full interfaces are needed
     * @param array $commands
     * @param string $type
     * @return array
     */
    private function filterInterfaceType(array $commands, $type)
    {
        if ($type === self::TYPE_FULL) {
            return $commands;
        }

        foreach ($commands as $arrayId => $commandId) {
            if (in_array($commandId, self::$commandSmallFilter) === false) {
                unset($commands[$arrayId]);
            }
        }

        return $commands;
    }

    /**
     * Get commands for an object
     *
     * Based on objects and interface type
     *
     * @param \stdClass $object
     * @param $interfaceType
     * @param User $user
     * @return array
     */
    public function getCommandForObject(\stdClass $object, $interfaceType, User $user = null)
    {
        $objectType = $this->getObjectType($object);
        $commands = $this->getCommandsByType($objectType);
        $method = $this->getCommandProcessorMethod($objectType);
        $commands = $this->$method($object, $commands, $objectType);
        $commands = $this->filterInterfaceType($commands, $interfaceType);
        $commands = $this->buildInterfaceConfiguration($commands, $objectType);
        return $commands;
    }
}
