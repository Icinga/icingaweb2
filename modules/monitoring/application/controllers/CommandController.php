<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Form\Command\DisableNotificationWithExpireForm;
use Icinga\Module\Monitoring\Form\Command\SingleArgumentCommandForm;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Notification;
use Icinga\Module\Monitoring\Controller;
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
use Icinga\Exception\IcingaException;

/**
 * Class Monitoring_CommandController
 *
 * Interface to send commands and display forms
 */
class Monitoring_CommandController extends Controller
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

    protected function addTitleTab($action)
    {
        $this->getTabs()->add($action, array(
            'title' => ucfirst($action),
            'url' => Url::fromRequest()
        ))->activate($action);
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
                $this->ignoreXhrBody();
                if ($this->_request->getHeader('referer') && ! $this->getRequest()->isXmlHttpRequest()) {
                    $this->redirect($this->_request->getHeader('referer'));
                }
            } else {
                $this->view->form = $this->form;
            }
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
            $instance = $this->_request->getPost('instance');
            $targetConfig = Config::module('monitoring', 'instances');
            if ($instance) {
                if ($targetConfig->get($instance)) {
                    $this->target = new CommandPipe($targetConfig->get($instance));
                } else {
                    throw new ConfigurationError(
                        $this->translate('Instance is not configured: %s'),
                        $instance
                    );
                }
            } else {
                if ($targetConfig && $targetInfo = $targetConfig->current()) {
                    // Take the very first section
                    $this->target = new CommandPipe($targetInfo);
                } else {
                    throw new ConfigurationError($this->translate('No instances are configured yet'));
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

            if (!$hostname && !$servicename) {
                throw new MissingParameterException('No target given for this command');
            }

            if ($servicename && !$hostOnly) {
                $fields[] = 'service_description';
                $query = $this->backend->select()
                    ->from('serviceStatus', $fields)
                    ->where('host', $hostname)
                    ->where('service', $servicename);
            } elseif ($hostname) {
                $query = $this->backend->select()->from('hostStatus', $fields)->where('host', $hostname);
            } else {
                throw new MissingParameterException('hostOnly command got no hostname');
            }
            return $query->getQuery()->fetchAll();

        } catch (\Exception $e) {
            Logger::error(
                "CommandController: SQL Query '%s' failed (message %s) ",
                $query ? (string) $query->dump() : '--', $e->getMessage()
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
            throw new IcingaException(
                'Missing parameter, supported: %s',
                implode(', ', array_flip($supported))
            );
        }

        if (isset($given['host'])) {
            $objects = $this->selectCommandTargets(!in_array("service", $supported));
            if (empty($objects)) {
                throw new IcingaException('No objects found for your command');
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
        $form->setSubmitLabel($this->translate('Disable Active Checks'));

        if ($form->provideGlobalCommand()) {
            $form->addNote($this->translate('Disable active checks on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Disable active checks for this object.'));
        }

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, active checks will be disabled'));
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
        $form->setSubmitLabel($this->translate('Enable Active Checks'));
        if ($form->provideGlobalCommand()) {
            $form->addNote($this->translate('Enable active checks on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Enable active checks for this object.'));
        }
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, active checks will be enabled'));
        }
    }

    /**
     * Handle command  reschedulenextcheck
     */
    public function reschedulenextcheckAction()
    {
        $this->addTitleTab('Reschedule Next Check');
        $this->setSupportedParameters(array('host', 'service'));
        $form = new RescheduleNextCheckForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, check will be rescheduled'));
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
            Notification::success($this->translate('Passive check result has been submitted'));
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
        $form->setSubmitLabel($this->translate('Stop obsessing'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Disable obsessing on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Stop obsessing over this object.'));
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
            Notification::success($this->translate('Command has been sent, obsessing will be disabled'));
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
        $form->setSubmitLabel($this->translate('Start obsessing'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Enable obsessing on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Start obsessing over this object.'));
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
            Notification::success($this->translate('Command has been sent, obsessing will be enabled'));
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
        $form->setSubmitLabel($this->translate('Stop Accepting Passive Checks'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Disable passive checks on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Passive checks for this object will be omitted.'));
        }

        $form->setCommand(
            'DISABLE_PASSIVE_HOST_CHECKS',
            'DISABLE_PASSIVE_SVC_CHECKS'
        );

        $form->setGlobalCommands(
            'STOP_ACCEPTING_PASSIVE_HOST_CHECKS',
            'STOP_ACCEPTING_PASSIVE_SVC_CHECKS'
        );

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, passive check results will be refused'));
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
        $form->setSubmitLabel($this->translate('Start Accepting Passive Checks'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Enable passive checks on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Passive checks for this object will be accepted.'));
        }

        $form->setCommand(
            'ENABLE_PASSIVE_HOST_CHECKS',
            'ENABLE_PASSIVE_SVC_CHECKS'
        );

        $form->setGlobalCommands(
            'START_ACCEPTING_PASSIVE_HOST_CHECKS',
            'START_ACCEPTING_PASSIVE_SVC_CHECKS'
        );
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, passive check results will be accepted'));
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
            Notification::success($this->translate('Command has been sent, notifications will be disabled'));
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

        $form->setSubmitLabel($this->translate('Disable Notifications'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Disable notifications on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Notifications for this object will be disabled.'));
        }

        $form->setCommand('DISABLE_HOST_NOTIFICATIONS', 'DISABLE_SVC_NOTIFICATIONS');
        $form->setGlobalCommands('DISABLE_NOTIFICATIONS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, notifications will be disabled'));
        }

    }

    /**
     * Handle command enablenotifications
     */
    public function enablenotificationsAction()
    {
        $this->addTitleTab('Enable Notifications');
        $this->setSupportedParameters(array('host', 'service', 'global'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel($this->translate('Enable Notifications'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Enable notifications on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Notifications for this object will be enabled.'));
        }

        $form->setCommand('ENABLE_HOST_NOTIFICATIONS', 'ENABLE_SVC_NOTIFICATIONS');
        $form->setGlobalCommands('ENABLE_NOTIFICATIONS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, notifications will be enabled'));
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
            Notification::success($this->translate('Custom notification has been sent'));
        }
    }

    /**
     * Handle command scheduledowntime
     */
    public function scheduledowntimeAction()
    {
        $this->addTitleTab('Schedule Downtime');
        $this->setSupportedParameters(array('host', 'service'));
        $form = new ScheduleDowntimeForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());
        $form->setWithChildren(false);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Downtime scheduling requested'));
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
            Notification::success($this->translate('Downtime scheduling requested'));
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
        $form->setSubmitLabel($this->translate('Remove Downtime(s)'));
        $form->addNote($this->translate('Remove downtime(s) from this host and its services.'));
        $form->setCommand('DEL_DOWNTIME_BY_HOST_NAME', 'DEL_DOWNTIME_BY_HOST_NAME');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Downtime removal requested'));
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
        $form->setSubmitLabel($this->translate('Disable Notifications'));
        $form->addNote($this->translate('Notifications for this host and its services will be disabled.'));
        $form->setCommand('DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            $form->setCommand('DISABLE_HOST_NOTIFICATIONS', 'DISABLE_SVC_NOTIFICATIONS');
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, notifications will be disabled'));
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
        $form->setSubmitLabel($this->translate('Enable Notifications'));
        $form->addNote($this->translate('Notifications for this host and its services will be enabled.'));
        $form->setCommand('ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            $form->setCommand('ENABLE_HOST_NOTIFICATIONS', 'ENABLE_SVC_NOTIFICATIONS');
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, notifications will be enabled'));
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
            Notification::success($this->translate('Command has been sent, checks will be rescheduled'));
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
        $form->setSubmitLabel($this->translate('Disable Active Checks'));
        $form->addNote($this->translate('Disable active checks for this host and its services.'));
        $form->setCommand('DISABLE_HOST_CHECK');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            // @TODO(mh): Missing child command
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, active checks will be disabled'));
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
        $form->setSubmitLabel($this->translate('Enable Active Checks'));
        $form->addNote($this->translate('Enable active checks for this host and its services.'));
        $form->setCommand('ENABLE_HOST_CHECK');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            // @TODO(mh): Missing child command
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, active checks will be enabled'));
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
        $form->setSubmitLabel($this->translate('Disable Event Handler'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Disable event handler for the whole system.'));
        } else {
            $form->addNote($this->translate('Disable event handler for this object.'));
        }

        $form->setCommand(
            'DISABLE_HOST_EVENT_HANDLER',
            'DISABLE_SVC_EVENT_HANDLER'
        );

        $form->setGlobalCommands('DISABLE_EVENT_HANDLERS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, event handlers will be disabled'));
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
        $form->setSubmitLabel($this->translate('Enable Event Handler'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Enable event handlers on the whole system.'));
        } else {
            $form->addNote($this->translate('Enable event handler for this object.'));
        }

        $form->setCommand(
            'ENABLE_HOST_EVENT_HANDLER',
            'ENABLE_SVC_EVENT_HANDLER'
        );

        $form->setGlobalCommands('ENABLE_EVENT_HANDLERS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, event handlers will be enabled'));
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
        $form->setSubmitLabel($this->translate('Disable Flapping Detection'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Disable flapping detection on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Disable flapping detection for this object.'));
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
            Notification::success($this->translate('Command has been sent, flap detection will be disabled'));
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
        $form->setSubmitLabel($this->translate('Enable Flapping Detection'));

        if ($form->provideGlobalCommand() === true) {
            $form->addNote($this->translate('Enable flapping detection on a program-wide basis.'));
        } else {
            $form->addNote($this->translate('Enable flapping detection for this object.'));
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
            Notification::success($this->translate('Command has been sent, flap detection will be enabled'));
        }
    }

    /**
     * Handle command addcomment
     */
    public function addcommentAction()
    {
        $this->addTitleTab('Add comment');
        $this->setSupportedParameters(array('host', 'service'));
        $form = new CommentForm();
        $form->setRequest($this->_request);

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Your new comment has been submitted'));
        }
    }

    /**
     * Remove a single comment
     */
    public function removecommentAction()
    {
        $this->addTitleTab('Remove Comment');
        $this->setSupportedParameters(array('commentid', 'host', 'service'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->_request);
        $form->setCommand('DEL_HOST_COMMENT', 'DEL_SVC_COMMENT');
        $form->setParameterName('commentid');
        $form->setSubmitLabel($this->translate('Remove comment'));
        $form->setObjectIgnoreFlag(true);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Comment removal has been requested'));
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
        $form->setSubmitLabel($this->translate('Reset Attributes'));
        $form->addNote($this->translate('Reset modified attributes to its default.'));
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
        $this->addTitleTab('Acknowledge Problem');
        $this->setSupportedParameters(array('host', 'service'));
        $form = new AcknowledgeForm();
        $form->setRequest($this->getRequest());
        $form->setConfiguration(Config::app());

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Acknowledgement has been sent'));
        }

        $this->setForm($form);
    }

    /**
     * Handle command removeacknowledgement
     */
    public function removeacknowledgementAction()
    {
        $this->addTitleTab('Remove Acknowledgement');
        $this->setSupportedParameters(array('host', 'service'));
        $form = new SingleArgumentCommandForm();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel($this->translate('Remove Problem Acknowledgement'));
        $form->addNote($this->translate('Remove problem acknowledgement for this object.'));
        $form->setCommand('REMOVE_HOST_ACKNOWLEDGEMENT', 'REMOVE_SVC_ACKNOWLEDGEMENT');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Acknowledgement removal has been requested'));
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
            Notification::success($this->translate('Notification delay has been requested'));
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

        $form->setSubmitLabel($this->translate('Delete Downtime'));
        $form->setParameterName('downtimeid');
        $form->addNote($this->translate('Delete a single downtime with the id shown above'));
        $form->setCommand('DEL_HOST_DOWNTIME', 'DEL_SVC_DOWNTIME');
        $form->setObjectIgnoreFlag(true);
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Downtime removal has been requested'));
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

        $form->setSubmitLabel($this->translate('Shutdown monitoring process'));
        $form->addNote($this->translate('Stop monitoring instance. You have to start it again from command line.'));
        $form->setGlobalCommands('SHUTDOWN_PROCESS');
        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, process will shut down'));
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

        $form->setSubmitLabel($this->translate('Restart monitoring process'));
        $form->addNote($this->translate('Restart the monitoring process.'));
        $form->setGlobalCommands('RESTART_PROCESS');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, monitoring process will restart now'));
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

        $form->setSubmitLabel($this->translate('Disable Performance Data'));
        $form->addNote($this->translate('Disable processing of performance data on a program-wide basis.'));

        $form->setGlobalCommands('DISABLE_PERFORMANCE_DATA');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, performance data processing will be disabled'));
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

        $form->setSubmitLabel($this->translate('Enable Performance Data'));
        $form->addNote($this->translate('Enable processing of performance data on a program-wide basis.'));

        $form->setGlobalCommands('ENABLE_PERFORMANCE_DATA');

        $this->setForm($form);

        if ($form->IsSubmittedAndValid() === true) {
            $this->target->sendCommand($form->createCommand(), $this->view->objects);
            Notification::success($this->translate('Command has been sent, performance data processing will be enabled'));
        }
    }
}
