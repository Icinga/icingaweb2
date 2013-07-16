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

use Icinga\Backend;
use Icinga\Application\Config;
use Icinga\Authentication\Manager;
use Icinga\Web\ModuleActionController;
use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Protocol\Commandpipe\CommandPipe;
use Icinga\Protocol\Commandpipe\Acknowledgement;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\MissingParameterException;
use Monitoring\Form\Command\Acknowledge;
use Monitoring\Form\Command\Comment as CommentForm;
use Monitoring\Form\Command\Confirmation;
use Monitoring\Form\Command\CustomNotification;
use Monitoring\Form\Command\RescheduleNextCheck;
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
     * @var \Icinga\Protocol\Commandpipe\CommandPipe
     */
    public $target;

    /**
     * Controller configuration
     * @throws Icinga\Exception\ConfigurationError
     */
    public function init()
    {
//        if ($this->_request->isPost()) {
//            // We do not need to display a view..
//            $this->_helper->viewRenderer->setNoRender(true);
//            // ..nor the overall site layout in case its a POST request.
//            $this->_helper->layout()->disableLayout();
//
//            $instance = $this->_request->getPost("instance");
//            $target_config = Config::getInstance()->getModuleConfig("instances", "monitoring");
//            if ($instance) {
//                if (isset($target_config[$instance])) {
//                    $this->target = new CommandPipe($target_config[$instance]);
//                } else {
//                    throw new ConfigurationError("Instance $instance is not configured");
//                }
//            } else {
//                $target_info = $target_config->current(); // Take the very first section
//                if ($target_info === false) {
//                    throw new ConfigurationError("Not any instances are configured yet");
//                } else {
//                    $this->target = new CommandPipe($target_info);
//                }
//            }
//        }

        $this->_helper->viewRenderer->setRender(self::DEFAULT_VIEW_SCRIPT);
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
     * Getter for request parameters
     * @param string $name
     * @param bool $mandatory
     * @return mixed
     * @throws Icinga\Exception\MissingParameterException
     */
    private function getParameter($name, $mandatory = true)
    {
        $value = $this->_request->getParam($name);
        if ($mandatory && !$value) {
            throw new MissingParameterException("Missing parameter $name");
        }
        return $value;
    }

    // ------------------------------------------------------------------------
    // Commands for hosts / services
    // ------------------------------------------------------------------------

    public function disableactivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enableactivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function reschedulenextcheckAction()
    {
        $form = new RescheduleNextCheck();
        $form->setRequest($this->getRequest());

        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function submitpassivecheckresultAction()
    {
        $type = SubmitPassiveCheckResult::TYPE_SERVICE;

        $form = new SubmitPassiveCheckResult();
        $form->setRequest($this->getRequest());
        $form->setType($type);

        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function stopobsessingAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop obsessing'));
        $form->addNote(t('Stop obsessing over this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function startobsessingAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start obsessing'));
        $form->addNote(t('Start obsessing over this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function stopacceptingpassivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Stop accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be omitted.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function startacceptingpassivechecksAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Start accepting passive checks'));
        $form->addNote(t('Passive checks for this object will be accepted.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function disablenotificationsAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this object will be disabled.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enablenotificationsAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this object will be enabled.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function sendcustomnotificationAction()
    {
        $form = new CustomNotification();
        $form->setRequest($this->getRequest());
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function scheduledowntimeAction()
    {

    }

    public function scheduledowntimeswithchildrenAction()
    {

    }

    public function removedowntimeswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove downtime(s)'));
        $form->addNote(t('Remove downtime(s) from this host and its services.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function disablenotificationswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable notifications'));
        $form->addNote(t('Notifications for this host and its services will be disabled.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enablenotificationswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable notifications'));
        $form->addNote(t('Notifications for this host and its services will be enabled.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function reschedulenextcheckwithchildrenAction()
    {
        $form = new RescheduleNextCheck();
        $form->setRequest($this->getRequest());

        $form->setWithChildred(true);

        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function disableactivecheckswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable active checks'));
        $form->addNote(t('Disable active checks for this host and its services.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enableactivecheckswithchildrenAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable active checks'));
        $form->addNote(t('Enable active checks for this host and its services.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function disableeventhandlerAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable event handler'));
        $form->addNote(t('Disable event handler for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enableeventhandlerAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable event handler'));
        $form->addNote(t('Enable event handler for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function disableflapdetectionAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Disable flapping detection'));
        $form->addNote(t('Disable flapping detection for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function enableflapdetectionAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Enable flapping detection'));
        $form->addNote(t('Enable flapping detection for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function addcommentAction()
    {
        $form = new CommentForm();
        $form->setRequest($this->getRequest());

        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function resetattributesAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Reset attributes'));
        $form->addNote(t('Reset modified attributes to its default.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function acknowledgeproblemAction()
    {
        $form = new Acknowledge();
        $form->setRequest($this->getRequest());

        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function removeacknowledgementAction()
    {
        $form = new Confirmation();
        $form->setRequest($this->getRequest());
        $form->setSubmitLabel(t('Remove problem acknowledgement'));
        $form->addNote(t('Remove problem acknowledgement for this object.'));
        $this->view->form = $form;

        if ($form->isValid($this->getRequest()) && $this->getRequest()->isPost()) {
            throw new \Icinga\Exception\ProgrammingError('Command sender not implemented: '. __FUNCTION__);
        }
    }

    public function delaynotificationAction()
    {

    }

    public function removedowntimeAction()
    {
        // DOWNTIME ID
    }
}
