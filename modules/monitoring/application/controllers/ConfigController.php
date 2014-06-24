<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Exception;

use \Icinga\Config\PreservingIniWriter;
use \Icinga\Web\Controller\BaseConfigController;
use \Icinga\Web\Widget\Tab;
use \Icinga\Web\Widget\AlertMessageBox;
use \Icinga\Web\Url;

use Icinga\Module\Monitoring\Form\Config\ConfirmRemovalForm;
use Icinga\Module\Monitoring\Form\Config\Backend\EditBackendForm;
use Icinga\Module\Monitoring\Form\Config\Backend\CreateBackendForm;

use Icinga\Module\Monitoring\Form\Config\Instance\EditInstanceForm;
use Icinga\Module\Monitoring\Form\Config\Instance\CreateInstanceForm;

use Icinga\Exception\NotReadableError;

/**
 * Configuration controller for editing monitoring resources
 */
class Monitoring_ConfigController extends BaseConfigController {

    /**
     * Create the tabs for being available via the applications 'Config' view
     *
     * @return array
     */
    static public function createProvidedTabs()
    {
        return array(
            'backends' => new Tab(array(
                'name'  => 'backends',
                'title' => 'Monitoring Backends',
                'url'   => Url::fromPath('/monitoring/config')
            ))
        );
    }

    /**
     * Display a list of available backends and instances
     */
    public function indexAction()
    {
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backends');
        $this->view->messageBox = new AlertMessageBox(true);
        foreach (array('backends', 'instances') as $element) {
            try {
                $elementConfig = $this->Config($element);
                if ($elementConfig === null) {
                    $this->view->{$element} = array();
                } else {
                    $this->view->{$element} = $elementConfig->toArray();
                }
            } catch (NotReadableError $e) {
                $this->view->{$element} = $e;
            }
        }
        $this->render('index');
    }

    /**
     * Display a form to modify the backend identified by the 'backend' parameter of the request
     */
    public function editbackendAction()
    {
        $backend = $this->getParam('backend');
        if (!$this->isExistingBackend($backend)) {
            $this->view->error = 'Unknown backend ' . $backend;
            return;
        }
        $backendForm = new EditBackendForm();
        $backendForm->setRequest($this->getRequest());
        $backendForm->setBackendConfiguration($this->Config('backends')->get($backend));

        if ($backendForm->isSubmittedAndValid()) {
            $newConfig = $backendForm->getConfig();
            $config = $this->Config('backends');
            $config->$backend = $newConfig;
            if ($this->writeConfiguration($config, 'backends')) {
                $this->addSuccessMessage('Backend ' . $backend . ' Modified.');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
                return;
            }
        }
        $this->view->name = $backend;
        $this->view->form = $backendForm;
    }

    /**
     * Display a form to create a new backends
     */
    public function createbackendAction()
    {
        $form = new CreateBackendForm();
        $form->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            $configArray  =  $this->Config('backends')->toArray();
            $configArray[$form->getBackendName()] = $form->getConfig();

            if ($this->writeConfiguration(new Zend_Config($configArray), 'backends')) {
                $this->addSuccessMessage('Backend Creation Succeeded');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
            }
            return;
        }
        $this->view->form = $form;
        $this->render('editbackend');
    }

    /**
     * Display a confirmation form to remove the backend identified by the 'backend' parameter
     */
    public function removebackendAction()
    {
        $backend = $this->getParam('backend');
        if (!$this->isExistingBackend($backend)) {
            $this->view->error = 'Unknown backend ' . $backend;
            return;
        }
        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('backend', $backend);

        if ($form->isSubmittedAndValid()) {
            $configArray = $this->Config('backends')->toArray();
            unset($configArray[$backend]);

            if ($this->writeConfiguration(new Zend_Config($configArray), 'backends')) {
                $this->addSuccessMessage('Backend "' . $backend . '" Removed');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
            }
            return;
        }

        $this->view->form = $form;
        $this->view->name = $backend;
    }

    /**
     * Display a form to remove the instance identified by the 'instance' parameter
     */
    public function removeinstanceAction()
    {
        $instance = $this->getParam('instance');
        if (!$this->isExistingInstance($instance)) {
            $this->view->error = 'Unknown instance ' . $instance;
            return;
        }

        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('instance', $instance);

        if ($form->isSubmittedAndValid()) {
            $configArray = $this->Config('instances')->toArray();
            unset($configArray[$instance]);

            if ($this->writeConfiguration(new Zend_Config($configArray), 'instances')) {
                $this->addSuccessMessage('Instance "' . $instance . '" Removed');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
            }
            return;
        }

        $this->view->form = $form;
        $this->view->name = $instance;
    }

    /**
     * Display a form to edit the instance identified by the 'instance' parameter of the request
     */
    public function editinstanceAction()
    {
        $instance = $this->getParam('instance');
        if (!$this->isExistingInstance($instance)) {
            $this->view->error = 'Unknown instance ' . htmlentities($instance);
            return;
        }
        $form = new EditInstanceForm();
        $form->setInstanceConfiguration($this->Config('instances')->get($instance));
        $form->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            $instanceConfig = $this->Config('instances')->toArray();
            $instanceConfig[$instance] = $form->getConfig();
            if ($this->writeConfiguration(new Zend_Config($instanceConfig), 'instances')) {
                $this->addSuccessMessage('Instance Modified');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
                return;
            }
        }
        $this->view->form = $form;
    }

    /**
     * Display a form to create a new instance
     */
    public function createinstanceAction()
    {
        $form = new CreateInstanceForm();
        $form->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            $instanceConfig = $this->Config('instances');
            if ($instanceConfig === null) {
                $instanceConfig = array();
            } else {
                $instanceConfig = $instanceConfig->toArray();
            }
            $instanceConfig[$form->getInstanceName()] = $form->getConfig()->toArray();
            if ($this->writeConfiguration(new Zend_Config($instanceConfig), 'instances')) {
                $this->addSuccessMessage('Instance Creation Succeeded');
                $this->redirectNow('monitoring/config');
            } else {
                $this->render('show-configuration');
            }
            return;
        }
        $this->view->form = $form;
        $this->render('editinstance');
    }

    /**
     * Display a form to remove the instance identified by the 'instance' parameter
     */
    private function writeConfiguration($config, $file)
    {
        $target = $this->Config($file)->getConfigFile();
        $writer = new PreservingIniWriter(array('filename' => $target, 'config' => $config));

        try {
            $writer->write();
        } catch (Exception $exc) {
            $this->view->exceptionMessage = $exc->getMessage();
            $this->view->iniConfigurationString = $writer->render();
            $this->view->file = $target;
            return false;
        }

        return true;
    }

    /**
     * Return true if the backend exists in the current configuration
     *
     * @param   string $backend The name of the backend to check for existence
     *
     * @return  bool True if the backend name exists, otherwise false
     */
    private function isExistingBackend($backend)
    {
        $backendCfg = $this->Config('backends');
        return $backend && $backendCfg->get($backend);
    }

    /**
     * Return true if the instance exists in the current configuration
     *
     * @param   string $instance The name of the instance to check for existence
     *
     * @return  bool True if the instance name exists, otherwise false
     */
    private function isExistingInstance($instance)
    {
        $instanceCfg = $this->Config('instances');
        return $instanceCfg && $instanceCfg->get($instance);
    }
}
// @codingStandardsIgnoreEnd
