<?php
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

/**
 * Class Zend_View_Helper_CommandButton
 *
 * TODO: Check if it should eventually be implement as a widget
 */
class Zend_View_Helper_CommandButton extends Zend_View_Helper_Abstracts {

    /**
     * Available command buttons.
     */
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
     * Render the given command-button
     *
     * @param $command  The command constant, for example CommandButton::CMD_DISABLE_ACTIVE_CHECKS
     * @param $href     The href that should be executed when clicking this button.
     */
    private function render($command, $href) {
        $cmd = $this->commandInformation[$command];

    }

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
            'Disable Active Checks For This %s',
            'Disable Active Checks',
            '',
            ''
        ),
        self::CMD_ENABLE_ACTIVE_CHECKS => array(
            'Enable Active Checks For This %s',
            'Enable Active Checks',
            '',
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
            '',
            ''
        ),
        self::CMD_STOP_OBSESSING => array(
            'Stop Obsessing Over This %s',
            'Stop Obsessing',
            '',
            ''
        ),
        self::CMD_START_OBSESSING => array(
            'Start Obsessing Over This %s',
            'Start Obsessing',
            '',
            ''
        ),
        self::CMD_STOP_ACCEPTING_PASSIVE_CHECKS => array(
            'Stop Accepting Passive Checks For This %s',
            'Stop Passive Checks',
            '',
            ''
        ),
        self::CMD_START_ACCEPTING_PASSIVE_CHECKS => array(
            'Start Accepting Passive Checks For This %s',
            'Start Passive Checks',
            '',
            ''
        ),
        self::CMD_DISABLE_NOTIFICATIONS => array(
            'Disable Notifications For This %s',
            'Disable Notifications',
            '',
            ''
        ),
        self::CMD_ENABLE_NOTIFICATIONS => array(
            'Enable Notifications For This %s',
            'Enable Notifications',
            '',
            ''
        ),
        self::CMD_SEND_CUSTOM_NOTIFICATION => array(
            'Send Custom %s Notification',
            'Send Notification',
            '',
            ''
        ),
        self::CMD_SCHEDULE_DOWNTIME => array(
            'Schedule Downtime For This %s',
            'Schedule Downtime',
            '',
            ''
        ),
        self::CMD_SCHEDULE_DOWNTIMES_TO_ALL => array(
            'Schedule Downtime For This %s And All Services',
            'Schedule Services Downtime',
            '',
            ''
        ),
        self::CMD_REMOVE_DOWNTIMES_FROM_ALL => array(
            'Remove Downtime(s) For This %s And All Services',
            'Remove Downtime(s)',
            '',
            ''
        ),
        self::CMD_DISABLE_NOTIFICATIONS_FOR_ALL => array(
            'Disable Notification For All Service On This %s',
            'Disable Service Notifications',
            '',
            ''
        ),
        self::CMD_ENABLE_NOTIFICATIONS_FOR_ALL => array(
            'Enable Notification For All Service On This %s',
            'Enable Service Notifications',
            '',
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
            '',
            ''
        ),
        self::CMD_ENABLE_ACTIVE_CHECKS_FOR_ALL => array(
            'Enable Checks For All Services On This %s',
            'Enable Service Checks',
            '',
            ''
        ),
        self::CMD_DISABLE_EVENT_HANDLER => array(
            'Disable Event Handler For This %s',
            'Disable Event Handler',
            '',
            ''
        ),
        self::CMD_ENABLE_EVENT_HANDLER => array(
            'Enable Event Handler For This %s',
            'Enable Event Handler',
            '',
            ''
        ),
        self::CMD_DISABLE_FLAP_DETECTION => array(
            'Disable Flap Detection For This %s',
            'Disable Flap Detection',
            '',
            ''
        ),
        self::CMD_ENABLE_FLAP_DETECTION => array(
            'Enable Flap Detection For This %s',
            'Enable Flap Detection',
            '',
            ''
        ),
        self::CMD_ADD_COMMENT => array(
            'Add New %s Comment',
            'Add Comment',
            '',
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
            '',
            ''
        )
    );
}