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

use Icinga\Application\Benchmark;
use Icinga\Application\Icinga;
use Icinga\Backend;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Authentication\Manager;
use Icinga\Web\Form;
use Icinga\Web\ModuleActionController;
use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Protocol\Commandpipe\CommandPipe;
use Icinga\Protocol\Commandpipe\Acknowledgement;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\MissingParameterException;
use Monitoring\Form\Command\AcknowledgeForm;
use Monitoring\Form\Command\CommentForm;
use Monitoring\Form\Command\ConfirmationForm;
use Monitoring\Form\Command\ConfirmationWithIdentifierForm;
use Monitoring\Form\Command\CustomNotificationForm;
use Monitoring\Form\Command\DelayNotificationForm;
use Monitoring\Form\Command\RescheduleNextCheckForm;
use Monitoring\Form\Command\ScheduleDowntimeForm;
use Monitoring\Form\Command\SubmitPassiveCheckResultForm;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */
class Monitoring_CommandController extends ModuleActionController
{
    const DEFAULT_VIEW_SCRIPT = 'renderform';

    /**
     * Command target
     * @var CommandPipe
     */
    private $target;

    /**
     * Current form working on
     * @var Form
     */
    private $form;

    /**
     * Setter for form
     * @param Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Test if we have a valid form object
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
            if ($this->form->isPostAndValid()) {
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout()->disableLayout();
            }
            $this->view->form = $this->form;
        }

        parent::postDispatch();
    }


    /**
     * Controller configuration
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
                    throw new ConfigurationError("Not any instances are configured yet");
                } else {
                    $this->target = new CommandPipe($targetInfo);
                }
            }
        }

        if ($this->getRequest()->getActionName() !== 'list') {



            // Reduce template writing mess
            $this->_helper->viewRenderer->setRender(self::DEFAULT_VIEW_SCRIPT);
        }
    }

    private function isGlobalCommand()
    {
        return false;
    }

    /**
     * Retrieve all existing targets for host- and service combination
     * @param string $hostname
     * @param string $servicename
     * @return array
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
            ;
            $query = Backend::getInstance()->select()->from("status", $fields);
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

    private function supportedParameter(array $supported)
    {
        $given = array_intersect_key(array_flip($supported), $this->getRequest()->getParams());
        if (empty($given)) {
            throw new \Exception('Missing parameter, supported: '.implode(', ', $supported));
        }
        if (isset($given["host"])) {
            $this->objects = $this->selectCommandTargets(!in_array("service", $supported));
            if (empty($this->objects)) {
                throw new \Exception("No objects found for your command");
            }

        } else if (in_array("downtimeid", $supported)) {
            $this->objects = array();
            $downtimes = $this->getParam("downtimeid");
            if (!is_array($downtimes)) {
                $downtimes = array($downtimes);
            }
            foreach ($downtimes as $downtimeId) {
                $this->objects[] = (object) array("downtime_id" => $downtimeId);
            }
        }
    }

    // ------------------------------------------------------------------------
    // Commands for hosts / services
    // ------------------------------------------------------------------------

    /**
     * Handle command disableactivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableactivechecksAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableActiveChecks($this->objects);
        }
    }

    /**
     * Handle command  enableactivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableactivechecksAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableActiveChecks($this->objects);
        }
    }

    /**
     * Handle command  reschedulenextcheck
     * @throws Icinga\Exception\ProgrammingError
     */
    public function reschedulenextcheckAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->scheduleCheck($this->objects);
        }
    }

    /**
     * Handle command  submitpassivecheckresult
     * @throws Icinga\Exception\ProgrammingError
     */
    public function submitpassivecheckresultAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $type = SubmitPassiveCheckResultForm::TYPE_SERVICE;

        $form = new SubmitPassiveCheckResultForm();
        $form->setRequest($this->getRequest());
        $form->setType($type);

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->submitCheckResult($this->objects, $form->getState(), $form->getOutput(), $form->getPerformancedata());
        }
    }

    /**
     * Handle command stopobsessing
     * @throws Icinga\Exception\ProgrammingError
     */
    public function stopobsessingAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop obsessing'));
        $form->addNote(t('Stop obsessing over this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->stopObsessing($this->objects);
        }
    }

    /**
     * Handle command startobsessing
     * @throws Icinga\Exception\ProgrammingError
     */
    public function startobsessingAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start obsessing'));
        $form->addNote(t('Start obsessing over this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->startObsessing($this->objects);
        }
    }

    /**
     * Handle command stopacceptingpassivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function stopacceptingpassivechecksAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be omitted.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disablePassiveChecks($this->objects);
        }
    }

    /**
     * Handle command startacceptingpassivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function startacceptingpassivechecksAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be accepted.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableActiveChecks($this->objects);
        }
    }

    /**
     * Handle command disablenotifications
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disablenotificationsAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this object will be disabled.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableNotifications($this->objects);
        }
    }

    /**
     * Handle command enablenotifications
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enablenotificationsAction()
    {
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this object will be enabled.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableNotifications($this->objects);
        }
    }

    /**
     * Handle command sendcustomnotification
     * @throws Icinga\Exception\ProgrammingError
     */
    public function sendcustomnotificationAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new CustomNotificationForm();
        $form->setRequest($this->getRequest());
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $author = $this->getRequest()->getUser()->getUsername();
            $this->target->sendCustomNotification(
                $this->objects,
                new Comment($author, $form->getComment()),
                $form->getOptions()
            );
        }
    }

    /**
     * Handle command scheduledowntime
     * @throws Icinga\Exception\ProgrammingError
     */
    public function scheduledowntimeAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ScheduleDowntimeForm();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(false);
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->scheduleDowntime($this->objects, $form->getDowntime());
        }
    }

    /**
     * Handle command scheduledowntimeswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function scheduledowntimeswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ScheduleDowntimeForm();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(true);
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->scheduleDowntime($this->objects, $form->getDowntime());
        }
    }

    /**
     * Handle command removedowntimeswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removedowntimeswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove downtime(s)'));
        $form->addNote(t('Remove downtime(s) from this host and its services.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->removeDowntime($this->objects);
        }
    }

    /**
     * Handle command disablenotificationswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disablenotificationswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this host and its services will be disabled.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableNotifications($this->objects);
            $this->target->disableNotificationsForServices($this->objects);
        }
    }

    /**
     * Handle command enablenotificationswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enablenotificationswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this host and its services will be enabled.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableNotifications($this->objects);
            $this->target->enableNotificationsForServices($this->objects);
        }
    }

    /**
     * Handle command reschedulenextcheckwithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function reschedulenextcheckwithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());

        $form->setWithChildren(true);

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            if ($form->isForced()) {
                $this->target->scheduleForcedCheck($this->objects, time());
                $this->target->scheduleForcedCheck($this->objects, time(), true);
            } else {
                $this->target->scheduleCheck($this->objects, time());
                $this->target->scheduleCheck($this->objects, time(), true);
            }
        }
    }

    /**
     * Handle command disableactivecheckswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableactivecheckswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this host and its services.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableActiveChecks($this->objects);
            $this->target->disableActiveChecksWithChildren($this->objects);
        }
    }

    /**
     * Handle command enableactivecheckswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableactivecheckswithchildrenAction()
    {
        $this->supportedParameter(array('host'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this host and its services.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableActiveChecks($this->objects);
            $this->target->enableActiveChecksWithChildren($this->objects);
        }
    }

    /**
     * Handle command disableeventhandler
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableeventhandlerAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable event handler'));
        $form->addNote(t('Disable event handler for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableEventHandler($this->objects);
        }
    }

    /**
     * Handle command enableeventhandler
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableeventhandlerAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable event handler'));
        $form->addNote(t('Enable event handler for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableEventHandler($this->objects);
        }
    }

    /**
     * Handle command disableflapdetection
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableflapdetectionAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable flapping detection'));
        $form->addNote(t('Disable flapping detection for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->disableFlappingDetection($this->objects);
        }
    }

    /**
     * Handle command enableflapdetection
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableflapdetectionAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable flapping detection'));
        $form->addNote(t('Enable flapping detection for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->enableFlappingDetection($this->objects);
        }
    }

    /**
     * Handle command addcomment
     * @throws Icinga\Exception\ProgrammingError
     */
    public function addcommentAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new CommentForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->addComment($this->objects, $form->getComment());
        }
    }

    /**
     * Handle command resetattributes
     * @throws Icinga\Exception\ProgrammingError
     */
    public function resetattributesAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Reset attributes'));
        $form->addNote(t('Reset modified attributes to its default.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->resetAttributes($this->objects);
        }
    }

    /**
     * Handle command acknowledgeproblem
     * @throws Icinga\Exception\ProgrammingError
     */
    public function acknowledgeproblemAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new AcknowledgeForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->acknowledge($this->objects, $form->getAcknowledgement());
        }
    }

    /**
     * Handle command removeacknowledgement
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removeacknowledgementAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new ConfirmationForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove problem acknowledgement'));
        $form->addNote(t('Remove problem acknowledgement for this object.'));
        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->removeAcknowledge($this->objects);
        }
    }

    /**
     * Handle command delaynotification
     * @throws Icinga\Exception\ProgrammingError
     */
    public function delaynotificationAction()
    {
        $this->supportedParameter(array('host', 'service'));
        $form = new DelayNotificationForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->delayNotification($this->objects, $form->getDelayTime());
        }
    }

    /**
     * Handle command removedowntime
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removedowntimeAction()
    {
        $this->supportedParameter(array('downtimeid'));
        $form = new ConfirmationWithIdentifierForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Delete downtime'));
        $form->setFieldName('downtimeid');
        $form->setFieldLabel(t('Downtime id'));
        $form->addNote(t('Delete a single downtime with the id shown above'));

        $this->setForm($form);

        if ($form->isPostAndValid() === true) {
            $this->target->removeDowntime($this->objects);
        }
    }
}

// @codingStandardsIgnoreStop