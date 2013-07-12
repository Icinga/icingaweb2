<?php
use Icinga\Web\ModuleActionController;
use \Icinga\Exception as IcingaException;

class Monitoring_CommandController extends ModuleActionController
{
    /**
     * @var \Icinga\Protocol\Commandpipe\CommandPipe
     */
    public $target;

    private function getMandatoryParameter($name)
    {
        $value = $this->_request->getPost($name, false);
        if ($value === false)
            throw new IcingaException\MissingParameterException("Missing parameter $name");
        return $value;
    }

    public function init()
    {
        if (!$this->_request->isPost()) {
            $this->_response->clearBody();
            $this->_response->clearHeaders();
            $this->_response->setHttpResponseCode(405);
            $this->_redirect("/");
        }
        if (!$this->hasValidToken())
            throw new Exception("Invalid token given", 401);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $targets = $this->config->instances;
        $instance = $this->_getParam("instance");
        if ($instance && isset($targets[$instance])) {
            $this->target = new \Icinga\Protocol\Commandpipe\CommandPipe($targets[$instance]);
        } else {
            foreach ($targets as $target) {
                $this->target = new \Icinga\Protocol\Commandpipe\CommandPipe($target);
                break;
            }
        }
    }

    private function selectCommandTargets()
    {
        $hostname = $this->_getParam("hosts");
        $servicename = $this->_getParam("services");
        $target = "hostlist";
        $filter = array();
        if (!$hostname && !$servicename) {
            throw new IcingaException\MissingParameterException("Missing host and service definition");
        }
        if ($hostname) {
            $filter["hostname"] = explode(";", $hostname);
        }
        if ($servicename) {
            $filter["servicedescription"] = explode(";", $servicename);
            $target = "servicelist";
        }

        return $this->backend = Icinga\Backend::getInstance()
            ->select()
            ->from($target)
            ->applyFilters($filter)
            ->fetchAll();
    }

    public function sendReschedule()
    {
        $forced = (trim($this->_getParam("forced"), false) === "true");
        $time = $this->_request->getPost("time", false);
        $childs = $this->_request->getPost("withChilds", false);
        if ($forced) {
            $this->target->scheduleForcedCheck($this->selectCommandTargets(), $time, $childs);
        } else {
            $this->target->scheduleCheck($this->selectCommandTargets(), $time, $childs);
        }
    }

    public function sendAcknowledge()
    {
        $author = "AUTHOR"; //@TODO: get from auth backend
        $comment = $this->getMandatoryParameter("comment");
        $persistent = $this->_request->getPost("persistent", false) == "true";
        $commentObj = new \Icinga\Protocol\Commandpipe\Comment($author, $comment, $persistent);

        $notify = $this->_request->getPost("notify", false) == "true";
        $sticky = $this->_request->getPost("sticky", false) == "true";
        $expire = intval($this->_request->getPost("expire", false));
        if (!$expire) {
            $expire = -1;
        }
        $ack = new \Icinga\Protocol\Commandpipe\Acknowledgement($commentObj, $notify, $expire, $sticky);
        $this->target->acknowledge($this->selectCommandTargets(), $ack);
    }

    public function sendScheduledowntime()
    {
        $author = "AUTHOR"; //@TODO: get from auth backend
        $comment = $this->getMandatoryParameter("comment");
        $persistent = $this->_request->getPost("persistent", false) == "true";
        $commentObj = new \Icinga\Protocol\Commandpipe\Comment($author, $comment, $persistent);

        $start = intval($this->_request->getPost("start", time()));
        $end = intval($this->getMandatoryParameter("end"));
        $duration = $this->_request->getPost("duration", false);
        if ($duration !== false) {
            $duration = intval($duration);
        }
        $downtime = new \Icinga\Protocol\Commandpipe\Downtime($start, $end, $commentObj, $duration);

        $this->target->scheduleDowntime($this->selectCommandTargets(), $downtime);
    }

    public function sendActivechecks()
    {
        if ($this->getMandatoryParameter("enable")) {
            $this->target->enableActiveChecks($this->selectCommandTargets());
        } else {
            $this->target->disableActiveChecks($this->selectCommandTargets());
        }
    }

    public function sendPassivechecks()
    {
        if ($this->getMandatoryParameter("enable")) {
            $this->target->enablePassiveChecks($this->selectCommandTargets());
        } else {
            $this->target->disablePassiveChecks($this->selectCommandTargets());
        }
    }

    public function sendFlappingdetection()
    {
        if ($this->getMandatoryParameter("enable")) {
            $this->target->enableFlappingDetection($this->selectCommandTargets());
        } else {
            $this->target->disableFlappingDetection($this->selectCommandTargets());
        }
    }

    public function sendComment()
    {
        $author = "AUTHOR"; //@TODO: get from auth backend
        $comment = $this->getMandatoryParameter("comment");
        $persistent = $this->_request->getPost("persistent", false) == "true";
        $commentObj = new \Icinga\Protocol\Commandpipe\Comment($author, $comment, $persistent);
        $this->target->addComment($this->selectCommandTargets(), $commentObj);
    }

    public function sendDeletecomment()
    {
        if ($this->_request->getPost("comments")) {
            $comments = array();
            foreach ($this->_request->getPost("comments") as $id => $content) {
                $comment = new StdClass();
                $comment->comment_id = $id;
                $value = explode(";", $content, 2);
                $comment->host_name = $value[0];
                if (isset($value[1])) {
                    $comment->service_description = $value[1];
                }
                $comments[] = $comment;
            }
            $this->target->removeComment($comments);
        } else {
            $this->target->removeComment($this->selectCommandTargets());
        }
    }

    public function sendRestartIcinga()
    {
        $this->target->restartIcinga();
    }

    public function sendDeletedowntime()
    {
        if ($this->_request->getPost("downtimes")) {
            $downtimes = array();
            foreach ($this->_request->getPost("comments") as $id => $content) {
                $downtime = new StdClass();
                $downtime->downtime_id = $id;
                $value = explode(";", $content, 2);
                $downtime->host_name = $value[0];
                if (isset($value[1])) {
                    $downtime->service_description = $value[1];
                }
                $downtimes[] = $downtime;
            }
            $this->target->removeDowntime($downtimes);
        } else {
            $this->target->removeDowntime($this->selectCommandTargets());
        }
    }


    public function __call($method, $args)
    {
        $command = substr($method, 0, -6);
        if ('Action' == substr($method, -6)) {
            $command[0] = strtoupper($command[0]);
            if (method_exists($this, "send$command")) {
                return $this->{"send$command"}();
            }
            throw new Exception("Invalid command $command", 404);
        }

        throw new BadMethodCallException("Call to undefined method $method");

    }


}
