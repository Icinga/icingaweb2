<?php
use Icinga\Backend;
use Icinga\Form\SendCommand;
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

    private function selectCommandTargets($hostname, $servicename = null)
    {
        $target = "hostlist";
        $filter = array();
        if (!$hostname && !$servicename) {
            throw new MissingParameterException("Missing host and service definition");
        }
        if ($hostname) {
            $filter["host_name"] = $hostname;
        }
        if ($servicename) {
            $filter["service_description"] = $servicename;
            $target = "servicelist";
        }
        return Backend::getInstance()->select()->from($target)->applyFilters($filter)->fetchAll();
    }

    private function getParameter($name, $mandatory = true)
    {
        $value = $this->_request->getParam($name);
        if ($mandatory && !$value) {
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

    public function schedulecheckAction()
    {
        $form = new SendCommand("Schedule Host/Service check");
        $form->addDatePicker("checkDate", "Check date", date("m-d-Y"));
        $form->addTimePicker("checkTime", "Check time", date("h:i A"));
        $form->addCheckbox("forceCheck", "Force check", false);

        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $withChilds = false;
                $services = $form->getServices();
                $time = sprintf("%s %s", $form->getDate("checkDate"), $form->getTime("checkTime"));

                if (!$services || $services === "all") {
                    $withChilds = $services === "all";
                    $targets = $this->selectCommandTargets($form->getHosts());
                } else {
                    $targets = $this->selectCommandTargets($form->getHosts(), $services);
                }

                if ($form->isChecked("forceCheck")) {
                    $this->target->scheduleForcedCheck($targets, $time, $withChilds);
                } else {
                    $this->target->scheduleCheck($targets, $time, $withChilds);
                }
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
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

    public function enableactivechecksAction()
    {
        // @TODO: Elaborate how "withChilds" and "forHosts" can be utilised
        $form = new SendCommand("Enable active checks?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $withChilds = $forHosts = false;
                $services = $form->getServices();

                if ($services) {
                    $withChilds = $services === "all";
                    $form->addCheckbox("forHosts", "", false);
                    $forHosts = $form->isChecked("forHosts");
                    if ($withChilds) {
                        $targets = $this->selectCommandTargets($form->getHosts());
                    } else {
                        $targets = $this->selectCommandTargets($form->getHosts(), $services);
                    }
                } else {
                    $targets = $this->selectCommandTargets($form->getHosts());
                }

                $this->target->enableActiveChecks($targets);
            }
        } else {
            $services = $this->getParameter("services", false);
            if ($services) {
                $form->addCheckbox("forHosts", "Enable for hosts too?", false);
            }
            $form->setServices($services);
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function disableactivechecksAction()
    {
        // @TODO: Elaborate how "withChilds" and "forHosts" can be utilised
        $form = new SendCommand("Disable active checks?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $withChilds = $forHosts = false;
                $services = $form->getServices();

                if ($services) {
                    $withChilds = $services === "all";
                    $form->addCheckbox("forHosts", "", false);
                    $forHosts = $form->isChecked("forHosts");
                    if ($withChilds) {
                        $targets = $this->selectCommandTargets($form->getHosts());
                    } else {
                        $targets = $this->selectCommandTargets($form->getHosts(), $services);
                    }
                } else {
                    $targets = $this->selectCommandTargets($form->getHosts());
                }

                $this->target->disableActiveChecks($targets);
            }
        } else {
            $services = $this->getParameter("services", false);
            if ($services) {
                $form->addCheckbox("forHosts", "Disable for hosts too?", false);
            }
            $form->setServices($services);
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function enablenotificationsAction()
    {
        // @TODO: Elaborate how "withChilds", "childHosts" and "forHosts" can be utilised
        $form = new SendCommand("Enable notifications?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $services = $form->getServices();
                $withChilds = $forHosts = $childHosts = false;

                if ($services) {
                    $withChilds = $services === "all";
                    $form->addCheckbox("forHosts", "", false);
                    $forHosts = $form->isChecked("forHosts");
                    if ($withChilds) {
                        $targets = $this->selectCommandTargets($form->getHosts());
                    } else {
                        $targets = $this->selectCommandTargets($form->getHosts(), $services);
                    }
                } else {
                    $form->addCheckbox("childHosts", "", false);
                    $childHosts = $form->isChecked("childHosts");
                    $targets = $this->selectCommandTargets($form->getHosts());
                }

                $this->target->enableNotifications($targets);
            }
        } else {
            $services = $this->getParameter("services", false);
            if ($services) {
                $form->addCheckbox("forHosts", "Enable for hosts too?", false);
            } else {
                $form->addCheckbox("childHosts", "Enable notifications for ".
                                                 "child hosts too?", false);
            }
            $form->setServices($services);
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function disablenotificationsAction()
    {
        // @TODO: Elaborate how "withChilds", "childHosts" and "forHosts" can be utilised
        $form = new SendCommand("Disable notifications?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $services = $form->getServices();
                $withChilds = $forHosts = $childHosts = false;

                if ($services) {
                    $withChilds = $services === "all";
                    $form->addCheckbox("forHosts", "", false);
                    $forHosts = $form->isChecked("forHosts");
                    if ($withChilds) {
                        $targets = $this->selectCommandTargets($form->getHosts());
                    } else {
                        $targets = $this->selectCommandTargets($form->getHosts(), $services);
                    }
                } else {
                    $form->addCheckbox("childHosts", "", false);
                    $childHosts = $form->isChecked("childHosts");
                    $targets = $this->selectCommandTargets($form->getHosts());
                }

                $this->target->disableNotifications($targets);
            }
        } else {
            $services = $this->getParameter("services", false);
            if ($services) {
                $form->addCheckbox("forHosts", "Disable for hosts too?", false);
            } else {
                $form->addCheckbox("childHosts", "Disable notifications for ".
                                                 "child hosts too?", false);
            }
            $form->setServices($services);
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function enableeventhandlingAction()
    {
        $form = new SendCommand("Enable event handler?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->enableEventHandler($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function disableeventhandlingAction()
    {
        $form = new SendCommand("Disable event handler?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->disableEventHandler($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function enableflapdetectionAction()
    {
        $form = new SendCommand("Enable flap detection?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->enableFlappingDetection($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function disableflapdetectionAction()
    {
        $form = new SendCommand("Disable flap detection?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->disableFlappingDetection($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function enablepassivechecksAction()
    {
        $form = new SendCommand("Enable passive checks?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->enablePassiveChecks($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function disablepassivechecksAction()
    {
        $form = new SendCommand("Disable passive checks?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->disablePassiveChecks($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function startobsessingAction()
    {
        $form = new SendCommand("Start obsessing?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->startObsessing($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
        }
    }

    public function stopobsessingAction()
    {
        $form = new SendCommand("Stop obsessing?");
        if ($this->_request->isPost()) {
            if ($form->isValid()) {
                $targets = $this->selectCommandTargets($form->getHosts(), $form->getServices());
                $this->target->stopObsessing($targets);
            }
        } else {
            $form->setServices($this->getParameter("services", false));
            $form->setHosts($this->getParameter("hosts"));
            $form->setAction($this->view->url());
            $form->addSubmitButton("Commit");
            $this->view->form = $form;
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
