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

namespace Icinga\Protocol\Commandpipe;

use Icinga\Protocol\Commandpipe\Transport\Transport;
use Icinga\Protocol\Commandpipe\Transport\LocalPipe;
use Icinga\Protocol\Commandpipe\Transport\SecureShell;

/**
 * Class to the access icinga CommandPipe via a @see Icinga\Protocol\Commandpipe\Transport.php
 *
 * Will be configured using the instances.ini
 */
class CommandPipe
{
    /**
     * The name of this class as defined in the instances.ini
     *
     * @var string
     */
    private $name = "";

    /**
     * The underlying @see Icinga\Protocol\Commandpipe\Transport.php class handling communication with icinga
     *
     * @var Icinga\Protocol\Commandpipe\Transport
     */
    private $transport = null;

    /**
     *  Constant identifying a monitoring object as host
     */
    const TYPE_HOST = "HOST";

    /**
     *  Constant identifying a monitoring object as service
     */
    const TYPE_SERVICE = "SVC";

    /**
     *  Constant identifying a monitoring object as hostgroup
     */
    const TYPE_HOSTGROUP = "HOSTGROUP";

    /**
     *  Constant identifying a monitoring object as servicegroups
     */
    const TYPE_SERVICEGROUP = "SERVICEGROUP";

    /**
     *  Notification option (use logical OR for combination)
     *
     *  Broadcast (send notification to all normal and all escalated contacts for the service)
     */
    const NOTIFY_BROADCAST  = 1;

    /**
     *  Notification option (use logical OR for combination)
     *
     *  notification is sent out regardless of current time, whether or not notifications are enabled, etc.
     */
    const NOTIFY_FORCED     = 2;

    /**
     *  Notification option (use logical OR for combination)
     *
     *  Increment current notification # for the service(this is not done by default for custom notifications)
     */
    const NOTIFY_INCREMENT  = 4;

    /**
     * Create a new CommandPipe class which accesses the icinga.cmd pipe as defined in $config
     *
     * @param \Zend_Config $config
     */
    public function __construct(\Zend_Config $config)
    {
        $this->getTransportForConfiguration($config);
        $this->name = $config->name;
    }

    /**
     * Setup the @see Icinga\Protocol\Commandpipe\Transport.php class that will be used for accessing the command pipe
     *
     * Currently this method uses SecureShell when a host is given, otherwise it assumes the pipe is accessible
     * via the machines filesystem
     *
     * @param \Zend_Config $config          The configuration as defined in the instances.ini
     */
    private function getTransportForConfiguration(\Zend_Config $config)
    {
        if (isset($config->host)) {
            $this->transport = new SecureShell();
            $this->transport->setEndpoint($config);
        } else {
            $this->transport = new LocalPipe();
            $this->transport->setEndpoint($config);
        }
    }

    /**
     * Send the command string $command to the icinga pipe
     *
     * This method just delegates the send command to the underlying transport
     *
     * @param String $command       The command string to send, without the timestamp
     */
    public function send($command)
    {
        $this->transport->send($command);
    }

    /**
     * Send a command to the icinga pipe
     *
     * @param Command   $command
     * @param array     $objects
     */
    public function sendCommand(Command $command, array $objects)
    {
        if ($command->provideGlobalCommand() === true) {
            $this->transport->send($command->getGlobalCommand());
        } else {
            foreach ($objects as $object) {
                $objectType = $this->getObjectType($object);
                if ($objectType === self::TYPE_SERVICE) {
                    $this->transport->send($command->getServiceCommand($object->host_name, $object->service_description));
                } else {
                    $this->transport->send($command->getHostCommand($object->host_name));
                }
            }
        }
    }

    /**
     * Remove the acknowledgements of the provided objects
     *
     * @param array $objects        An array of mixed service and host objects whose acknowledgments will be removed
     */
    public function removeAcknowledge($objects)
    {
        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $this->send("REMOVE_SVC_ACKNOWLEDGEMENT;$object->host_name;$object->service_description");
            } else {
                $this->send("REMOVE_HOST_ACKNOWLEDGEMENT;$object->host_name");
            }
        }
    }

    /**
     * Removes the submitted comments
     *
     * @param array $objectsOrComments      An array of hosts and services (to remove all their comments)
     *                                      or single comment objects to remove
     */
    public function removeComment($objectsOrComments)
    {
        foreach ($objectsOrComments as $object) {
            if (isset($object->comment_id)) {
                if (isset($object->service_description)) {
                    $type = "SERVICE_COMMENT";
                } else {
                    $type = "HOST_COMMENT";
                }
                $this->send("DEL_{$type};" . intval($object->comment_id));
            } else {
                if (isset($object->service_description)) {
                    $type = "SERVICE_COMMENT";
                } else {
                    $type = "HOST_COMMENT";
                }
                $cmd = "DEL_ALL_{$type}S;" . $object->host_name;
                if ($type == "SERVICE_COMMENT") {
                    $cmd .= ";" . $object->service_description;
                }
                $this->send($cmd);
            }
        }
    }

    /**
     *  Globally enable notifications for this instance
     *
     */
    public function enableGlobalNotifications()
    {
        $this->send("ENABLE_NOTIFICATIONS");
    }

    /**
     *  Globally disable notifications for this instance
     *
     */
    public function disableGlobalNotifications()
    {
        $this->send("DISABLE_NOTIFICATIONS");
    }

    /**
     * Return the object type of the provided object (TYPE_SERVICE or TYPE_HOST)
     *
     * @param $object           The object to identify
     * @return string           TYPE_SERVICE or TYPE_HOST
     */
    private function getObjectType($object)
    {
        //@TODO: This must be refactored once more commands are supported
        if (isset($object->service_description)) {
            return self::TYPE_SERVICE;
        }
        return self::TYPE_HOST;
    }

    /**
     * Remove downtimes for objects
     *
     * @param array $objects        An array containing hosts, service or downtime objects
     * @param int $starttime        An optional starttime to use for the DEL_DOWNTIME_BY_HOST_NAME command
     */
    public function removeDowntime($objects, $starttime = 0)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            if (isset($object->downtime_id)) {
                $this->send("DEL_" . $type . "_DOWNTIME;" . $object->downtime_id);
                continue;
            }
            $cmd = "DEL_DOWNTIME_BY_HOST_NAME;" . $object->host_name;
            if ($type == self::TYPE_SERVICE) {
                $cmd .= ";" . $object->service_description;
            }
            if ($starttime != 0) {
                $cmd .= ";" . $starttime;
            }
            $this->send($cmd);
        }
    }

    /**
     *  Restart the icinga instance
     *
     */
    public function restartIcinga()
    {
        $this->send("RESTART_PROCESS");
    }

    /**
     * Modify monitoring flags for the provided objects
     *
     * @param array $objects            An arry of service and/or host objects to modify
     * @param PropertyModifier $flags   The Monitoring attributes to modify
     */
    public function setMonitoringProperties($objects, PropertyModifier $flags)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            $formatArray = $flags->getFormatString($type);
            foreach ($formatArray as $format) {
                $format .= ";"
                    . $object->host_name
                    . ($type == self::TYPE_SERVICE ? ";" . $object->service_description : "");
                $this->send($format);
            }
        }
    }

    /**
     * Enable active checks for all provided objects
     *
     * @param array $objects        An array containing services and hosts to enable active checks for
     */
    public function enableActiveChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::ACTIVE => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable active checks for all provided objects
     *
     * @param array $objects        An array containing services and hosts to disable active checks
     */
    public function disableActiveChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::ACTIVE => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable passive checks for all provided objects
     *
     * @param array $objects        An array containing services and hosts to enable passive checks for
     */
    public function enablePassiveChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::PASSIVE => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Enable passive checks for all provided objects
     *
     * @param array $objects        An array containing services and hosts to enable passive checks for
     */
    public function disablePassiveChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::PASSIVE => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable flap detection for all provided objects
     *
     * @param array $objects        An array containing services and hosts to enable flap detection
     *
     */
    public function enableFlappingDetection($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::FLAPPING => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable flap detection for all provided objects
     *
     * @param array $objects        An array containing services and hosts to disable flap detection
     *
     */
    public function disableFlappingDetection($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::FLAPPING => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable notifications for all provided objects
     *
     * @param array $objects        An array containing services and hosts to enable notification
     *
     */
    public function enableNotifications($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::NOTIFICATIONS => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable flap detection for all provided objects
     *
     * @param array $objects        An array containing services and hosts to disable notifications
     *
     */
    public function disableNotifications($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::NOTIFICATIONS => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable freshness checks for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function enableFreshnessChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::FRESHNESS => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable freshness checks for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function disableFreshnessChecks($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::FRESHNESS => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable event handler for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function enableEventHandler($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::EVENTHANDLER => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable event handler for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function disableEventHandler($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::EVENTHANDLER => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Enable performance data parsing for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function enablePerfdata($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::PERFDATA => PropertyModifier::STATE_ENABLE
                )
            )
        );
    }

    /**
     * Disable performance data parsing for all provided objects
     *
     * @param array $objects    An array of hosts and/or services
     */
    public function disablePerfdata($objects)
    {
        $this->setMonitoringProperties(
            $objects,
            new PropertyModifier(
                array(
                    PropertyModifier::PERFDATA => PropertyModifier::STATE_DISABLE
                )
            )
        );
    }

    /**
     * Disable notifications for all services of the provided hosts
     *
     * @param array $objects    An array of hosts
     */
    public function disableNotificationsForServices($objects)
    {
        foreach ($objects as $host) {
            $msg = 'DISABLE_HOST_SVC_NOTIFICATIONS;'.$host->host_name;
            $this->send($msg);
        }
    }

    /**
     * Enable notifications for all services of the provided hosts
     *
     * @param array $objects    An array of hosts
     */
    public function enableNotificationsForServices($objects)
    {
        foreach ($objects as $host) {
            $msg = 'ENABLE_HOST_SVC_NOTIFICATIONS;'.$host->host_name;
            $this->send($msg);
        }
    }

    /**
     * Disable active checks for all services of the provided hosts
     *
     * @param array $objects    An array of hosts
     */
    public function disableActiveChecksWithChildren($objects)
    {
        foreach ($objects as $host) {
            $msg = 'DISABLE_HOST_SVC_CHECKS;'.$host->host_name;
            $this->send($msg);
        }
    }

    /**
     * Enable active checks for all services of the provided hosts
     *
     * @param array $objects    An array of hosts
     */
    public function enableActiveChecksWithChildren($objects)
    {
        foreach ($objects as $host) {
            $msg = 'ENABLE_HOST_SVC_CHECKS;'.$host->host_name;
            $this->send($msg);
        }
    }

    /**
     * Reset modified attributes for all provided objects
     *
     * @param array $objects    An array of hosts and services
     */
    public function resetAttributes($objects)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            if ($type === self::TYPE_SERVICE) {
                $this->send('CHANGE_SVC_MODATTR;'.$object->host_name.';'.$object->service_description.';0');
            } else {
                $this->send('CHANGE_HOST_MODATTR;'.$object->host_name.';0');
            }
        }
    }

    /**
     * Return the transport handler that handles actual sending of commands
     *
     * @return Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
