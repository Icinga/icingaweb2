<?php
namespace Icinga\Protocol\Commandpipe;
use Icinga\Application\Logger as IcingaLogger;
class CommandPipe
{
    private $path;
    private $name;
    private $user = falsE;
    private $host = false;
    private $port = 22;
    public $fopen_mode = "w";

    const TYPE_HOST = "HOST";
    const TYPE_SERVICE = "SVC";
    const TYPE_HOSTGROUP = "HOSTGROUP";
    const TYPE_SERVICEGROUP = "SERVICEGROUP";


    public function __construct(\Zend_Config $config)
    {
        $this->path = $config->path;
        $this->name = $config->name;
        if(isset($config->host)) {
            $this->host = $config->host;
        }
        if(isset($config->port)) {
            $this->port = $config->port;
        }
        if(isset($config->user)) {
            $this->user = $config->user;
        }
    }

    public function send($command)
    {
        if(!$this->host) {
            IcingaLogger::debug("Attempting to send external icinga command $command to local command file {$this->path}");
            $file = @fopen($this->path, $this->fopen_mode);
            if (!$file)
                throw new \RuntimeException("Could not open icinga pipe at $file : ".print_r(error_get_last(), true));
            fwrite($file,"[".time()."] ".$command.PHP_EOL);
            IcingaLogger::debug('Writing ['.time().'] '.$command.PHP_EOL);
            fclose($file);
        } else {
            // send over ssh
            $retCode = 0;
            $output = array();
            IcingaLogger::debug('Icinga instance is on different host, attempting to send command %s via ssh to %s:%s/%s', $command, $this->host, $this->port, $this->path);
            $hostConnector = $this->user ? $this->user."@".$this->host : $this->host;
            exec("ssh $hostConnector -p{$this->port} \"echo '[".time()."] ".escapeshellcmd($command)."' > {$this->path}\"", $output, $retCode);
            IcingaLogger::debug("$:ssh $hostConnector -p{$this->port} \"echo '[".time()."] ".escapeshellcmd($command)."' > {$this->path}\"");
            IcingaLogger::debug("Code code %s: %s ", $retCode, $output);

            if($retCode != 0) {
                throw new \RuntimeException('Could not send command to remote icinga host: '.implode("\n", $output)." (returncode $retCode)");
            }
        }
    }

    public function acknowledge($objects,IComment $acknowledgementOrComment) {
        if (is_a($acknowledgementOrComment,'Icinga\Protocol\Commandpipe\Comment'))
            $acknowledgementOrComment = new Acknowledgement($acknowledgementOrComment);

        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $format = $acknowledgementOrComment->getFormatString(self::TYPE_SERVICE);
                $this->send(sprintf($format,$object->host_name,$object->service_description));
            } else {
                $format = $acknowledgementOrComment->getFormatString(self::TYPE_HOST);
                $this->send(sprintf($format,$object->host_name));
            }
        }
    }

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

    public function submitCheckResult($objects, $state, $output)
    {
        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $this->send("PROCESS_SVC_CHECK_RESULT;$object->host_name;$object->service_description;$state;$output");
            } else {
                $this->send("PROCESS_HOST_CHECK_RESULT;$object->host_name;$state;$output");
            }
        }
    }

    public function scheduleForcedCheck($objects,$time=false,$withChilds=false) {
        if (!$time)
            $time = time();
        $base = "SCHEDULE_FORCED_";
        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $this->send($base."SVC_CHECK;$object->host_name;$object->service_description;$time");
            } else {
                $this->send($base.'HOST_'.($withChilds ? 'SVC_CHECKS' : 'CHECK' ).";$object->host_name;$time");
            }
        }
    }

    public function scheduleCheck($objects,$time=false,$withChilds=false) {
        if (!$time)
            $time = time();
        $base = "SCHEDULE_";
        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $this->send($base."SVC_CHECK;$object->host_name;$object->service_description;$time");
            } else {
                $this->send($base.'HOST_'.($withChilds ? 'SVC_CHECKS' : 'CHECK' ).";$object->host_name;$time");
            }
        }
    }

    public function addComment(array $objects, Comment $comment)
    {
        foreach ($objects as $object) {
            if (isset($object->service_description)) {
                $format = $comment->getFormatString(self::TYPE_SERVICE);
                $this->send(sprintf($format,$object->host_name,$object->service_description));
            } else {
                $format = $comment->getFormatString(self::TYPE_HOST);
                $this->send(sprintf($format,$object->host_name));
            }
        }

    }

    public function removeComment($objectsOrComments)
    {
        foreach ($objectsOrComments as $object) {
            if (isset($object->comment_id)) {
                if (isset($object->service_description)) {
                    $type = "SERVICE_COMMENT";
                } else {
                    $type = "HOST_COMMENT";
                }
                $this->send("DEL_{$type};".intval($object->comment_id));
            } else {
                if (isset($object->service_description)) {
                    $type = "SERVICE_COMMENT";
                } else {
                    $type = "HOST_COMMENT";
                }
                $cmd = "DEL_ALL_{$type}S;".$object->host_name;
                if ($type == "SERVICE_COMMENT")
                    $cmd .= ";".$object->service_description;
                $this->send($cmd);
            }
        }
    }

    public function enableGlobalNotifications()
    {
        $this->send("ENABLE_NOTIFICATIONS");
    }

    public function disableGlobalNotifications()
    {
        $this->send("DISABLE_NOTIFICATIONS");
    }

    private function getObjectType($object)
    {
        //@TODO: This must be refactored once more commands are supported
        if (isset($object->service_description))
            return self::TYPE_SERVICE;
        return self::TYPE_HOST;
    }

    public function scheduleDowntime($objects, Downtime $downtime)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            if($type == self::TYPE_SERVICE)
                $this->send(sprintf($downtime->getFormatString($type),$object->host_name,$object->service_description));
            else
                $this->send(sprintf($downtime->getFormatString($type),$object->host_name));
        }
    }

    public function removeDowntime($objects,$starttime = 0)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            if (isset($object->downtime_id)) {
                $this->send("DEL_".$type."_DOWNTIME;".$object->downtime_id);
                continue;
            }
            $cmd = "DEL_DOWNTIME_BY_HOST_NAME;".$object->host_name;
            if($type == self::TYPE_SERVICE)
                $cmd .= ";".$object->service_description;
            if($starttime != 0)
                $cmd .= ";".$starttime;
            $this->send($cmd);
        }
    }

    public function restartIcinga()
    {
        $this->send("RESTART_PROCESS");
    }

    public function setMonitoringProperties($objects, PropertyModifier $flags)
    {
        foreach ($objects as $object) {
            $type = $this->getObjectType($object);
            $formatArray = $flags->getFormatString($type);
            foreach ($formatArray as $format) {
                $format .= ";".$object->host_name.($type == self::TYPE_SERVICE ? ";".$object->service_description : "");
                $this->send($format);
            }
        }
    }

    public function enableActiveChecks($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::ACTIVE => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disableActiveChecks($objects)
    {
        $this->modifyMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::ACTIVE => PropertyModifier::STATE_DISABLE
        )));
    }

    public function enablePassiveChecks($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::PASSIVE => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disablePassiveChecks($objects)
    {
        $this->modifyMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::PASSIVE => PropertyModifier::STATE_DISABLE
        )));
    }

    public function enableFlappingDetection($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::FLAPPING => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disableFlappingDetection($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::FLAPPING => PropertyModifier::STATE_DISABLE
        )));
    }

    public function enableNotifications($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::NOTIFICATIONS => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disableNotifications($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::NOTIFICATIONS => PropertyModifier::STATE_DISABLE
        )));
    }

    public function enableFreshnessChecks($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::FRESHNESS => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disableFreshnessChecks($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::FRESHNESS => PropertyModifier::STATE_DISABLE
        )));
    }
    public function enableEventHandler($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::EVENTHANDLER => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disableEventHandler($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::EVENTHANDLER => PropertyModifier::STATE_DISABLE
        )));
    }

    public function enablePerfdata($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::PERFDATA => PropertyModifier::STATE_ENABLE
        )));
    }

    public function disablePerfdata($objects)
    {
        $this->setMonitoringProperties($objects,new PropertyModifier(array(
            PropertyModifier::PERFDATA => PropertyModifier::STATE_DISABLE
        )));
    }
}