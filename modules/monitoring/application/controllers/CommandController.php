<?php
use Icinga\Backend;
use Icinga\Form\Confirmation;
use Icinga\Application\Config;
use Icinga\Web\ModuleActionController;
use Icinga\Protocol\Commandpipe\CommandPipe;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\MissingParameterException;

class Monitoring_CommandController extends ModuleActionController
{
    /**
     * @var \Icinga\Protocol\Commandpipe\CommandPipe
     */
    public $target;

    public function init()
    {
        if ($this->_request->isPost()) {
            // We do not need to display a view..
            $this->_helper->viewRenderer->setNoRender(true);
            // ..nor the overall site layout in case its a POST request.
            $this->_helper->layout()->disableLayout();

            $instance = $this->_request->getPost("instance");
            $target_config = Config::getInstance()->getModuleConfig("instances", "monitoring");
            if ($instance) {
                if (isset($target_config[$instance])) {
                    $this->target = new CommandPipe($target_config[$instance]);
                } else {
                    throw new ConfigurationError("Instance $instance is not configured");
                }
            } else {
                $target_info = $target_config->current(); // Take the very first section
                if ($target_info === false) {
                    throw new ConfigurationError("Not any instances are configured yet");
                } else {
                    $this->target = new CommandPipe($target_info);
                }
            }
        }
    }

    private function selectCommandTargets()
    {
        $hostname = $this->_request->getPost("hosts");
        $servicename = $this->_request->getPost("services");
        $target = "hostlist";
        $filter = array();
        if (!$hostname && !$servicename) {
            throw new MissingParameterException("Missing host and service definition");
        }
        if ($hostname) {
            $filter["hostname"] = explode(";", $hostname);
        }
        if ($servicename) {
            $filter["servicedescription"] = explode(";", $servicename);
            $target = "servicelist";
        }
        return Backend::getInstance()->select()->from($target)->applyFilters($filter)->fetchAll();
    }

    private function getMandatoryParameter($name)
    {
        $value = $this->_request->getParam($name);
        if (!$value) {
            throw new MissingParameterException("Missing parameter $name");
        }
        return $value;
    }

    public function restartAction()
    {
        $form = new Confirmation("Restart Icinga?", Confirmation::YES_NO);
        if ($this->_request->isPost()) {
            if ($form->isValid() && $form->isConfirmed()) {
                $this->target->restartIcinga();
            }
        } else {
            $form->setAction($this->view->url());
            $this->view->form = $form;
        }
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
}
