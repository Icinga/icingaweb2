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
use Icinga\Backend;
use Icinga\Application\Config;
use Icinga\Authentication\Manager;
use Icinga\Web\Form;
use Icinga\Web\ModuleActionController;
use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Protocol\Commandpipe\CommandPipe;
use Icinga\Protocol\Commandpipe\Acknowledgement;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\MissingParameterException;
use Monitoring\Form\Command\Acknowledge;
use Monitoring\Form\Command\Comment as CommentForm;
use Monitoring\Form\Command\Confirmation;
use Monitoring\Form\Command\ConfirmationWithIdentifier;
use Monitoring\Form\Command\CustomNotification;
use Monitoring\Form\Command\DelayNotification;
use Monitoring\Form\Command\RescheduleNextCheck;
use Monitoring\Form\Command\ScheduleDowntime;
use Monitoring\Form\Command\SubmitPassiveCheckResult;

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
     * Getter for form
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Test if we have a valid form object
     * @return bool
     */
    public function issetForm()
    {
        return $this->getForm() !== null && ($this->getForm() instanceof Form);
    }

    /**
     * Post dispatch method
     *
     * When we have a form put it into the view
     */
    public function postDispatch()
    {
        if ($this->issetForm()) {
            if ($this->getRequest()->isPost() && $this->getForm()->isValid(null) === true) {
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout()->disableLayout();
            }
            $this->view->form = $this->getForm();
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

    /**
     * Retrieve all existing targets for host- and service combination
     * @param string $hostname
     * @param string $servicename
     * @return array
     * @throws Icinga\Exception\MissingParameterException
     */
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

    // ------------------------------------------------------------------------
    // Commands for hosts / services
    // ------------------------------------------------------------------------

    /**
     * Handle command disableactivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableactivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command  enableactivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableactivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command  reschedulenextcheck
     * @throws Icinga\Exception\ProgrammingError
     */
    public function reschedulenextcheckAction()
    {
        $form = new RescheduleNextCheck();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command  submitpassivecheckresult
     * @throws Icinga\Exception\ProgrammingError
     */
    public function submitpassivecheckresultAction()
    {
        $type = SubmitPassiveCheckResult::TYPE_SERVICE;

        $form = new SubmitPassiveCheckResult();
        $form->setRequest($this->getRequest());
        $form->setType($type);

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command stopobsessing
     * @throws Icinga\Exception\ProgrammingError
     */
    public function stopobsessingAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop obsessing'));
        $form->addNote(t('Stop obsessing over this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command startobsessing
     * @throws Icinga\Exception\ProgrammingError
     */
    public function startobsessingAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start obsessing'));
        $form->addNote(t('Start obsessing over this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command stopacceptingpassivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function stopacceptingpassivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be omitted.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command startacceptingpassivechecks
     * @throws Icinga\Exception\ProgrammingError
     */
    public function startacceptingpassivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be accepted.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command disablenotifications
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disablenotificationsAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this object will be disabled.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command enablenotifications
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enablenotificationsAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this object will be enabled.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command sendcustomnotification
     * @throws Icinga\Exception\ProgrammingError
     */
    public function sendcustomnotificationAction()
    {
        $form = new CustomNotification();
        $form->setRequest($this->getRequest());
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command scheduledowntime
     * @throws Icinga\Exception\ProgrammingError
     */
    public function scheduledowntimeAction()
    {
        $form = new ScheduleDowntime();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(false);
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command scheduledowntimeswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function scheduledowntimeswithchildrenAction()
    {
        $form = new ScheduleDowntime();
        $form->setRequest($this->getRequest());
        $form->setWithChildren(true);
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command removedowntimeswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removedowntimeswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove downtime(s)'));
        $form->addNote(t('Remove downtime(s) from this host and its services.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command disablenotificationswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disablenotificationswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this host and its services will be disabled.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command enablenotificationswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enablenotificationswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this host and its services will be enabled.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command reschedulenextcheckwithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function reschedulenextcheckwithchildrenAction()
    {
        $form = new RescheduleNextCheck();
        $form->setRequest($this->getRequest());

        $form->setWithChildren(true);

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command disableactivecheckswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableactivecheckswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this host and its services.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command enableactivecheckswithchildren
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableactivecheckswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this host and its services.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command disableeventhandler
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableeventhandlerAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable event handler'));
        $form->addNote(t('Disable event handler for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command enableeventhandler
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableeventhandlerAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable event handler'));
        $form->addNote(t('Enable event handler for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command disableflapdetection
     * @throws Icinga\Exception\ProgrammingError
     */
    public function disableflapdetectionAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable flapping detection'));
        $form->addNote(t('Disable flapping detection for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command enableflapdetection
     * @throws Icinga\Exception\ProgrammingError
     */
    public function enableflapdetectionAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable flapping detection'));
        $form->addNote(t('Enable flapping detection for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command addcomment
     * @throws Icinga\Exception\ProgrammingError
     */
    public function addcommentAction()
    {
        $form = new CommentForm();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command resetattributes
     * @throws Icinga\Exception\ProgrammingError
     */
    public function resetattributesAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Reset attributes'));
        $form->addNote(t('Reset modified attributes to its default.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command acknowledgeproblem
     * @throws Icinga\Exception\ProgrammingError
     */
    public function acknowledgeproblemAction()
    {
        $form = new Acknowledge();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command removeacknowledgement
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removeacknowledgementAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove problem acknowledgement'));
        $form->addNote(t('Remove problem acknowledgement for this object.'));
        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command delaynotification
     * @throws Icinga\Exception\ProgrammingError
     */
    public function delaynotificationAction()
    {
        $form = new DelayNotification();
        $form->setRequest($this->getRequest());

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    /**
     * Handle command removedowntime
     * @throws Icinga\Exception\ProgrammingError
     */
    public function removedowntimeAction()
    {
        $form = new ConfirmationWithIdentifier();
        $form->setRequest($this->getRequest());

        $form->setSubmitLabel(t('Delete downtime'));
        $form->setFieldName('downtimeid');
        $form->setFieldLabel(t('Downtime id'));
        $form->addNote(t('Delete a single downtime with the id shown above'));

        $this->setForm($form);

        if ($this->getRequest()->isPost() && $form->isValid(null)) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }
}

// @codingStandardsIgnoreStop