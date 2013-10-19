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

use Icinga\Application\Icinga;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Form\Command\DisableNotificationWithExpireForm;
use Icinga\Module\Monitoring\Form\Command\SingleArgumentCommandForm;
use Icinga\Web\Form;
use Icinga\Web\Controller\ActionController;
use Icinga\Protocol\Commandpipe\CommandPipe;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\MissingParameterException;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Form\Command\AcknowledgeForm;
use Icinga\Module\Monitoring\Form\Command\CommentForm;
use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Module\Monitoring\Form\Command\CommandWithIdentifierForm;
use Icinga\Module\Monitoring\Form\Command\CustomNotificationForm;
use Icinga\Module\Monitoring\Form\Command\DelayNotificationForm;
use Icinga\Module\Monitoring\Form\Command\RescheduleNextCheckForm;
use Icinga\Module\Monitoring\Form\Command\ScheduleDowntimeForm;
use Icinga\Module\Monitoring\Form\Command\SubmitPassiveCheckResultForm;

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
     * @param CommandForm $form
     */
    public function setForm(CommandForm $form)
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

                $requested = strtolower($this->_request->getHeader('x-requested-with'));
                $ajaxRequest = $requested === 'xmlhttprequest' ? true : false;

                if ($this->_request->getHeader('referer') && $ajaxRequest === false) {
                    $this->redirect($this->_request->getHeader('referer'));
                }

                return;
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

        $this->view->objects = array();
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

            // Implemented manuall search because api is not ready.
            // @TODO Implement this using the database api #4663 (mh)

            $query = Backend::createBackend($this->_getParam('backend'))->select()->from("status", $fields);
            $data = $query->fetchAll();
            $out = array();

            foreach ($data as $o) {
                $test = (array)$o;
                if ($test['host_name'] === $hostname) {
                    if (!$servicename) {
                        $out[] = (object) $o;
                    } elseif ($servicename && strtolower($test['service_description']) === strtolower($servicename)) {
                        $out[] = (object) $o;
                    }
                }
            }

            return $out;
        } catch (\Exception $e) {
            Logger::error(
                "CommandController: SQL Query '%s' failed (message %s) ",
                $query ? (string) $query->getQuery()->dump() : '--', $e->getMessage()
            );
            return array();
        }
    }

    /**
     * Convert other params into valid command structure
     *
     * @param   array   $supported  Array of supported parameter names
     * @param   array   $params     Parameters from request
     *
     * @return  array               Return
     */
    private function selectOtherTargets(array $supported, array $params)
    {
        $others = array_diff_key($supported, array('host' => true, 'service' => true));
        $otherParams = array_intersect_key($params, $others);
        $out = array();

        foreach ($otherParams as $name => $value) {
            $data = new stdClass();
            $data->{$name} = $value;
            $out[] = $data;
        }

        return $out;
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
        $objects = array();

        $supported = array_flip($supported);

        $given = array_intersect_key($supported, $this->getRequest()->getParams());

        if (empty($given)) {
            throw new \Exception('Missing parameter, supported: '.implode(', ', array_flip($supported)));
        }

        if (isset($given['host'])) {
            $objects = $this->selectCommandTargets(!in_array("service", $supported));
            if (empty($objects)) {
                throw new \Exception("No objects found for your command");
            }
        }

        $this->view->objects = $objects;
    }

    // ------------------------------------------------------------------------
    // Commands for hosts / services
    // ------------------------------------------------------------------------

    /**
     * Handle command disableactivechecks
     */
    public function disableactivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));

        $form = new SingleArgumentCommandForm();

        $form->setCommand(
            'DISABLE_HOST_CHECK',
            'DISABLE_SVC_CHECK'
        );

        $form->setGlobalCommands(
            'STOP_EXECUTING_HOST_CHECKS',
            'STOP_EXECUTING_SVC_CHECKS'
        );

        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Active Checks'));

        if ($form->provideGlobalCommand()) {
            $form->addNote(t('Disable active checks on a program-wide basis.'));
        } else {
            $form->addNote(t('Disable active checks for this object.'));
        }

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command  enableactivechecks
     */
    public function enableactivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setCommand('ENABLE_HOST_CHECK', 'ENABLE_SVC_CHECK');
        $form->setGlobalCommands('START_EXECUTING_HOST_CHECKS', 'START_EXECUTING_SVC_CHECKS');

        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Active Checks'));
        if ($form->provideGlobalCommand()) {
            $form->addNote(t('Enable active checks on a program-wide basis.'));
        } else {
            $form->addNote(t('Enable active checks for this object.'));
        }
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command stopobsessing
     */
    public function stopobsessingAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop obsessing'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Disable obsessing on a program-wide basis.'));
        } else {
            $form->addNote(t('Stop obsessing over this object.'));
        }

        $form->setCommand(
            'STOP_OBSESSING_OVER_HOST',
            'STOP_OBSESSING_OVER_SVC'
        );

        $form->setGlobalCommands(
            'STOP_OBSESSING_OVER_HOST_CHECKS',
            'STOP_OBSESSING_OVER_SVC_CHECKS'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command startobsessing
     */
    public function startobsessingAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start obsessing'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Enable obsessing on a program-wide basis.'));
        } else {
            $form->addNote(t('Start obsessing over this object.'));
        }

        $form->setCommand(
            'START_OBSESSING_OVER_HOST',
            'START_OBSESSING_OVER_SVC'
        );

        $form->setGlobalCommands(
            'START_OBSESSING_OVER_HOST_CHECKS',
            'START_OBSESSING_OVER_SVC_CHECKS'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command stopacceptingpassivechecks
     */
    public function stopacceptingpassivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop Accepting Passive Checks'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Disable passive checks on a program-wide basis.'));
        } else {
            $form->addNote(t('Passive checks for this object will be omitted.'));
        }

        $form->setCommand(
            'STOP_ACCEPTING_PASSIVE_HOST_CHECKS',
            'STOP_ACCEPTING_PASSIVE_SVC_CHECKS'
        );

        $form->setGlobalCommands(
            'STOP_ACCEPTING_PASSIVE_HOST_CHECKS',
            'STOP_ACCEPTING_PASSIVE_SVC_CHECKS'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command startacceptingpassivechecks
     */
    public function startacceptingpassivechecksAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start Accepting Passive Checks'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Enable passive checks on a program-wide basis.'));
        } else {
            $form->addNote(t('Passive checks for this object will be accepted.'));
        }

        $form->setCommand(
            'START_ACCEPTING_PASSIVE_HOST_CHECKS',
            'START_ACCEPTING_PASSIVE_SVC_CHECKS'
        );

        $form->setGlobalCommands(
            'START_ACCEPTING_PASSIVE_HOST_CHECKS',
            'START_ACCEPTING_PASSIVE_SVC_CHECKS'
        );
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Disable notifications with expiration
     *
     * This is a global command only
     */
    public function disablenotificationswithexpireAction()
    {
        $this->setParam('global', 1);
        $form = new DisableNotificationWithExpireForm();
        $form->setRequest($this->_request);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command disablenotifications
     */
    public function disablenotificationsAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Disable Notifications'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Disable notifications on a program-wide basis.'));
        } else {
            $form->addNote(t('Notifications for this object will be disabled.'));
        }

        $form->setCommand('DISABLE_HOST_NOTIFICATIONS', 'DISABLE_SVC_NOTIFICATIONS');
        $form->setGlobalCommands('DISABLE_NOTIFICATIONS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }

    }

    /**
     * Handle command enablenotifications
     */
    public function enablenotificationsAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Enable Notifications'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Enable notifications on a program-wide basis.'));
        } else {
            $form->addNote(t('Notifications for this object will be enabled.'));
        }

        $form->setCommand('ENABLE_HOST_NOTIFICATIONS', 'ENABLE_SVC_NOTIFICATIONS');
        $form->setGlobalCommands('ENABLE_NOTIFICATIONS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command removedowntimeswithchildren
     */
    public function removedowntimeswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove Downtime(s)'));
        $form->addNote(t('Remove downtime(s) from this host and its services.'));
        $form->setCommand('DEL_DOWNTIME_BY_HOST_NAME', 'DEL_DOWNTIME_BY_HOST_NAME');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command disablenotificationswithchildren
     */
    public function disablenotificationswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Notifications'));
        $form->addNote(t('Notifications for this host and its services will be disabled.'));
        $form->setCommand('DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            $form->setCommand('DISABLE_HOST_NOTIFICATIONS', 'DISABLE_SVC_NOTIFICATIONS');
            $this->target->sendCommand($form->createCommand(), $this->view->objects);

        }
    }

    /**
     * Handle command enablenotificationswithchildren
     */
    public function enablenotificationswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Notifications'));
        $form->addNote(t('Notifications for this host and its services will be enabled.'));
        $form->setCommand('ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            $form->setCommand('ENABLE_HOST_NOTIFICATIONS', 'ENABLE_SVC_NOTIFICATIONS');
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command disableactivecheckswithchildren
     */
    public function disableactivecheckswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Active Checks'));
        $form->addNote(t('Disable active checks for this host and its services.'));
        $form->setCommand('DISABLE_HOST_CHECK');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            // @TODO(mh): Missing child command
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command enableactivecheckswithchildren
     */
    public function enableactivecheckswithchildrenAction()
    {
        $this->setSupportedParameters(array('host'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Active Checks'));
        $form->addNote(t('Enable active checks for this host and its services.'));
        $form->setCommand('ENABLE_HOST_CHECK');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            // @TODO(mh): Missing child command
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command disableeventhandler
     */
    public function disableeventhandlerAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Event Handler'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Disable event handler for the whole system.'));
        } else {
            $form->addNote(t('Disable event handler for this object.'));
        }

        $form->setCommand(
            'DISABLE_HOST_EVENT_HANDLER',
            'DISABLE_SVC_EVENT_HANDLER'
        );

        $form->setGlobalCommands('DISABLE_EVENT_HANDLERS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command enableeventhandler
     */
    public function enableeventhandlerAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));

        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Event Handler'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Enable event handlers on the whole system.'));
        } else {
            $form->addNote(t('Enable event handler for this object.'));
        }

        $form->setCommand(
            'ENABLE_HOST_EVENT_HANDLER',
            'ENABLE_SVC_EVENT_HANDLER'
        );

        $form->setGlobalCommands('ENABLE_EVENT_HANDLERS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command disableflapdetection
     */
    public function disableflapdetectionAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable Flapping Detection'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Disable flapping detection on a program-wide basis.'));
        } else {
            $form->addNote(t('Disable flapping detection for this object.'));
        }

        $form->setCommand(
            'DISABLE_HOST_FLAP_DETECTION',
            'DISABLE_SVC_FLAP_DETECTION'
        );

        $form->setGlobalCommands(
            'DISABLE_FLAP_DETECTION'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command enableflapdetection
     */
    public function enableflapdetectionAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable Flapping Detection'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote(t('Enable flapping detection on a program-wide basis.'));
        } else {
            $form->addNote(t('Enable flapping detection for this object.'));
        }

        $form->setCommand(
            'ENABLE_HOST_FLAP_DETECTION',
            'ENABLE_SVC_FLAP_DETECTION'
        );

        $form->setGlobalCommands(
            'ENABLE_FLAP_DETECTION'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command addcomment
     */
    public function addcommentAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommentForm();
        $form->setRequest($this->_request);

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Remove a single comment
     */
    public function removecommentAction()
    {
        $this->setSupportedParameters(array('commentid', 'host', 'service'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);
        $form->setCommand('DEL_HOST_COMMENT', 'DEL_SVC_COMMENT');
        $form->setParameterName('commentid');
        $form->setSubmitLabel(t('Remove comment'));
        $form->setObjectIgnoreFlag(true);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command resetattributes
     */
    public function resetattributesAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Reset Attributes'));
        $form->addNote(t('Reset modified attributes to its default.'));
        $form->setCommand('CHANGE_HOST_MODATTR', 'CHANGE_SVC_MODATTR');
        $form->setParameterValue(0);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }

        $this->setForm($form);
    }

    /**
     * Handle command removeacknowledgement
     */
    public function removeacknowledgementAction()
    {
        $this->setSupportedParameters(array('host', 'service'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove Problem Acknowledgement'));
        $form->addNote(t('Remove problem acknowledgement for this object.'));
        $form->setCommand('REMOVE_HOST_ACKNOWLEDGEMENT', 'REMOVE_SVC_ACKNOWLEDGEMENT');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
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
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Handle command removedowntime
     */
    public function removedowntimeAction()
    {
        $this->setSupportedParameters(array('host', 'service', 'downtimeid'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Delete Downtime'));
        $form->setParameterName('downtimeid');
        $form->addNote(t('Delete a single downtime with the id shown above'));
        $form->setCommand('DEL_HOST_DOWNTIME', 'DEL_SVC_DOWNTIME');
        $form->setObjectIgnoreFlag(true);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Shutdown the icinga process
     */
    public function shutdownprocessAction()
    {
        $this->setParam('global', '1');
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);

        $form->setSubmitLabel(t('Shutdown monitoring process'));
        $form->addNote(t('Stop monitoring instance. You have to start it again from command line.'));
        $form->setGlobalCommands('SHUTDOWN_PROCESS');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Restart the icinga process
     */
    public function restartprocessAction()
    {
        $this->setParam('global', '1');
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);

        $form->setSubmitLabel(t('Restart monitoring process'));
        $form->addNote(t('Restart the monitoring process.'));
        $form->setGlobalCommands('RESTART_PROCESS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Disable processing of performance data
     */
    public function disableperformancedataAction()
    {
        $this->setParam('global', 1);
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);

        $form->setSubmitLabel(t('Disable Performance Data'));
        $form->addNote(t('Disable processing of performance data on a program-wide basis.'));

        $form->setGlobalCommands('DISABLE_PERFORMANCE_DATA');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }

    /**
     * Enable processing of performance data
     */
    public function enableperformancedataAction()
    {
        $this->setParam('global', 1);
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);

        $form->setSubmitLabel(t('Enable Performance Data'));
        $form->addNote(t('Enable processing of performance data on a program-wide basis.'));

        $form->setGlobalCommands('ENABLE_PERFORMANCE_DATA');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
        }
    }
}
// @codingStandardsIgnoreStop
