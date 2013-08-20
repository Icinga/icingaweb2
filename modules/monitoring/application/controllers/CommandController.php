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

use \Icinga\Application\Icinga;
use \Icinga\Application\Config;
use \Icinga\Application\Logger;
use \Icinga\Web\Form;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Protocol\Commandpipe\CommandPipe;
use \Icinga\Exception\ConfigurationError;
use \Icinga\Exception\MissingParameterException;
use \Icinga\Module\Monitoring\Backend;
use \Icinga\Module\Monitoring\Form\Command\AcknowledgeForm;
use \Icinga\Module\Monitoring\Form\Command\CommentForm;
use \Icinga\Module\Monitoring\Form\Command\CommandForm;
use \Icinga\Module\Monitoring\Form\Command\CommandWithIdentifierForm;
use \Icinga\Module\Monitoring\Form\Command\CustomNotificationForm;
use \Icinga\Module\Monitoring\Form\Command\DelayNotificationForm;
use \Icinga\Module\Monitoring\Form\Command\RescheduleNextCheckForm;
use \Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm;
use \Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */
class Monitoring_CommandController extends ActionController
{
    const DEFAULT_VIEW_SCRIPT = 'renderform';

    /**
     * Command target
     *
     * @var CommandPipe
     */
    private $target;

    /**
     * Current form working on
     *
     * @var Form
     */
    private $form;

    /**
     * Setter for form
     *
     * @param Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Test if we have a valid form object
     *
     * @return bool
     */
    public function issetForm()
    {
        return $this->form !== null && ($this->form instanceof Form);
    }

    /**
     * Post dispatch method
     *
     * When we have a form put it into the view
     */
    public function postDispatch()
    {
        if ($this->issetForm()) {
            if ($this->form->isSubmittedAndValid()) {
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout()->disableLayout();
            }
            $this->view->form = $this->form;
        }
        parent::postDispatch();
    }


    /**
     * Controller configuration
     *
     * @throws Icinga\Exception\ConfigurationError
     */
    public function init()
    {
        if ($this->_request->isPost()) {

            $instance = $this->_request->getPost("instance");
            $targetConfig = Config::module('monitoring', 'instances');
            if ($instance) {
                if ($targetConfig->get($instance)) {
                    $this->target = new CommandPipe($targetConfig->get($instance));
                } else {
                    throw new ConfigurationError('Instance is not configured: '. $instance);
                }
            } else {
                $targetInfo = $targetConfig->current(); // Take the very first section

                if ($targetInfo === false) {
                    throw new ConfigurationError("No instances are configured yet");
                } else {
                    $this->target = new CommandPipe($targetInfo);
                }
            }
        }

        if ($this->getRequest()->getActionName() !== 'list') {
            $this->_helper->viewRenderer->setRender(self::DEFAULT_VIEW_SCRIPT);
        }
    }

    /**
     * Retrieve all existing targets for host- and service combination
     *
     * @param $hostOnly         Ignore the service parameters
     *                          (for example when using commands that only make sense for hosts)
     * @return array            Array of monitoring objects
     * @throws Icinga\Exception\MissingParameterException
     */
    private function selectCommandTargets($hostOnly = false)
    {
        $query = null;
        $fields = array(
            'host_name',
            'host_state'
        );
        try {
            $hostname    =  $this->getParam('host', null);
            $servicename =  $this->getParam('service', null);
            $filter = array();
            if (!$hostname && !$servicename) {
                throw new MissingParameterException("No target given for this command");
            }
            if ($hostname) {
                $filter["host_name"] = $hostname;
            }
            if ($servicename && !$hostOnly) {
                $filter["service_description"] = $servicename;
                $fields[] = "service_description";
                $fields[] = "service_state";
            }
            $query = Backend::getInstance($this->_getParam('backend'))->select()->from("status", $fields);
            return $query->applyFilters($filter)->fetchAll();
        } catch (\Exception $e) {
            Logger::error(
                "CommandController: SQL Query '%s' failed (message %s) ",
                $query ? (string) $query->getQuery()->dump() : '--', $e->getMessage()
            );
            return array();
        }
    }

    /**
     * Displays a list of all commands
     *
     * This method uses reflection on the sourcecode to determine all *Action classes and return
     * a list of them (ignoring the listAction)
     */
    public function listAction()
    {
        $reflection = new ReflectionObject($this);
        $commands = array();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $name = $method->getName();
            if ($name !== 'listAction' && preg_match('/Action$/', $name)) {
                $commands[] = preg_replace('/Action$/', '', $name);
            }
        }
        $this->view->commands = $commands;
    }

    /**
     * Tell the controller that at least one of the parameters in $supported is required to be availabe
     *
     * @param  array    $supported      An array of properties to check for existence in the POST or GET parameter list
     * @throws Exception                When non of the supported parameters is given
     */
    private function setSupportedParameters(array $supported)
    {
        $given = array_intersect_key(array_flip($supported), $this->getRequest()->getParams());
        if (empty($given)) {
            throw new \Exception('Missing parameter, supported: '.implode(', ', $supported));
        }
        if (isset($given["host"])) {
            $this->view->objects = $this->selectCommandTargets(!in_array("service", $supported));
            if (empty($this->view->objects)) {
                throw new \Exception("No objects found for your command");
            }
        } elseif (in_array("downtimeid", $supported)) {
            $this->view->objects = array();
            $downtimes = $this->getParam("downtimeid");
            if (!is_array($downtimes)) {
                $downtimes = array($downtimes);
            }
            foreach ($downtimes as $downtimeId) {
                $this->view->objects[] = (object) array("downtime_id" => $downtimeId);
            }
        }
    }

    // ------------------------------------------------------------------------
    // Commands for hosts / services
    // ------------------------------------------------------------------------

    /**
     * Handle command disableactivechecks
     */
    public function disableactivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Active Checks'));
        $form->addNote(t('Disable active checks for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableActiveChecks($this->view->objects);
        }
    }

    /**
     * Handle command  enableactivechecks
     */
    public function enableactivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableActiveChecks($this->view->objects);
        }
    }

    /**
     * Handle command  reschedulenextcheck
     */
    public function reschedulenextcheckAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->scheduleCheck($this->view->objects);
        }
    }

    /**
     * Handle command  submitpassivecheckresult
     */
    public function submitpassivecheckresultAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $type = SubmitPassiveCheckResultForm::TYPE_SERVICE;
        if ($this->getParam('service', null) === null) {
            $type = SubmitPassiveCheckResultForm::TYPE_HOST;
        }

        $form = new SubmitPassiveCheckResultForm();
        $form->setRequest($this->getRequest());
        $form->setType($type);

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->submitCheckResult($this->view->objects, $form->getState(), $form->getOutput(), $form->getPerformancedata());
        }
    }

    /**
     * Handle command stopobsessing
     */
    public function stopobsessingAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop obsessing'));
        $form->addNote(t('Stop obsessing over this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->stopObsessing($this->view->objects);
        }
    }

    /**
     * Handle command startobsessing
     */
    public function startobsessingAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start obsessing'));
        $form->addNote(t('Start obsessing over this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->startObsessing($this->view->objects);
        }
    }

    /**
     * Handle command stopacceptingpassivechecks
     */
    public function stopacceptingpassivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop Accepting Passive Checks'));
        $form->addNote(t('Passive checks for this object will be omitted.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disablePassiveChecks($this->view->objects);
        }
    }

    /**
     * Handle command startacceptingpassivechecks
     */
    public function startacceptingpassivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start Accepting Passive Checks'));
        $form->addNote(t('Passive checks for this object will be accepted.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableActiveChecks($this->view->objects);
        }
    }

    /**
     * Handle command disablenotifications
     */
    public function disablenotificationsAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Notifications'));
        $form->addNote(t('Notifications for this object will be disabled.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableNotifications($this->view->objects);
        }
    }

    /**
     * Handle command enablenotifications
     */
    public function enablenotificationsAction()
    {
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Notifications'));
        $form->addNote(t('Notifications for this object will be enabled.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableNotifications($this->view->objects);
        }
    }

    /**
     * Handle command sendcustomnotification
     */
    public function sendcustomnotificationAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CustomNotificationForm();
        $form->setRequest($this->getRequest());
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCustomNotification($this->view->objects, $form->getCustomNotification());
        }
    }

    /**
     * Handle command scheduledowntime
     */
    public function scheduledowntimeAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new ScheduleDowntimeForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());
        $form->setWithChildren(false);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->scheduleDowntime($this->view->objects, $form->getDowntime());
        }
    }

    /**
     * Handle command scheduledowntimeswithchildren
     */
    public function scheduledowntimeswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new ScheduleDowntimeForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());
        $form->setWithChildren(true);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->scheduleDowntime($this->view->objects, $form->getDowntime());
        }
    }

    /**
     * Handle command removedowntimeswithchildren
     */
    public function removedowntimeswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove Downtime(s)'));
        $form->addNote(t('Remove downtime(s) from this host and its services.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->removeDowntime($this->view->objects);
        }
    }

    /**
     * Handle command disablenotificationswithchildren
     */
    public function disablenotificationswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Notifications'));
        $form->addNote(t('Notifications for this host and its services will be disabled.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableNotifications($this->view->objects);
            $this->target->disableNotificationsForServices($this->view->objects);
        }
    }

    /**
     * Handle command enablenotificationswithchildren
     */
    public function enablenotificationswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Notifications'));
        $form->addNote(t('Notifications for this host and its services will be enabled.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableNotifications($this->view->objects);
            $this->target->enableNotificationsForServices($this->view->objects);
        }
    }

    /**
     * Handle command reschedulenextcheckwithchildren
     */
    public function reschedulenextcheckwithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());

        $form->setWithChildren(true);

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            if ($form->isForced()) {
                $this->target->scheduleForcedCheck($this->view->objects, time());
                $this->target->scheduleForcedCheck($this->view->objects, time(), true);
            } else {
                $this->target->scheduleCheck($this->view->objects, time());
                $this->target->scheduleCheck($this->view->objects, time(), true);
            }
        }
    }

    /**
     * Handle command disableactivecheckswithchildren
     */
    public function disableactivecheckswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Active Checks'));
        $form->addNote(t('Disable active checks for this host and its services.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableActiveChecks($this->view->objects);
            $this->target->disableActiveChecksWithChildren($this->view->objects);
        }
    }

    /**
     * Handle command enableactivecheckswithchildren
     */
    public function enableactivecheckswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Active Checks'));
        $form->addNote(t('Enable active checks for this host and its services.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableActiveChecks($this->view->objects);
            $this->target->enableActiveChecksWithChildren($this->view->objects);
        }
    }

    /**
     * Handle command disableeventhandler
     */
    public function disableeventhandlerAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Event Handler'));
        $form->addNote(t('Disable event handler for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableEventHandler($this->view->objects);
        }
    }

    /**
     * Handle command enableeventhandler
     */
    public function enableeventhandlerAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Event Handler'));
        $form->addNote(t('Enable event handler for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableEventHandler($this->view->objects);
        }
    }

    /**
     * Handle command disableflapdetection
     */
    public function disableflapdetectionAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Flapping Detection'));
        $form->addNote(t('Disable flapping detection for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->disableFlappingDetection($this->view->objects);
        }
    }

    /**
     * Handle command enableflapdetection
     */
    public function enableflapdetectionAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Flapping Detection'));
        $form->addNote(t('Enable flapping detection for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->enableFlappingDetection($this->view->objects);
        }
    }

    /**
     * Handle command addcomment
     */
    public function addcommentAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommentForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->addComment($this->view->objects, $form->getComment());
        }
    }

    /**
     * Handle command resetattributes
     */
    public function resetattributesAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Reset Attributes'));
        $form->addNote(t('Reset modified attributes to its default.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->resetAttributes($this->view->objects);
        }
    }

    /**
     * Handle command acknowledgeproblem
     */
    public function acknowledgeproblemAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new AcknowledgeForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->acknowledge($this->view->objects, $form->getAcknowledgement());
        }
    }

    /**
     * Handle command removeacknowledgement
     */
    public function removeacknowledgementAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove Problem Acknowledgement'));
        $form->addNote(t('Remove problem acknowledgement for this object.'));
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->removeAcknowledge($this->view->objects);
        }
    }

    /**
     * Handle command delaynotification
     */
    public function delaynotificationAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new DelayNotificationForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->delayNotification($this->view->objects, $form->getDelayTime());
        }
    }

    /**
     * Handle command removedowntime
     */
    public function removedowntimeAction()
    {
        $this->setSupportedParameters(array('downtimeid'));
        $form = new CommandWithIdentifierForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Delete Downtime'));
        $form->setFieldName('downtimeid');
        $form->setFieldLabel(t('Downtime Id'));
        $form->addNote(t('Delete a single downtime with the id shown above'));

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->removeDowntime($this->view->objects);
        }
    }
}

// @codingStandardsIgnoreStop
