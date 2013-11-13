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

use \Icinga\Web\Controller\BaseConfigController;
use \Icinga\Web\Widget\Tab;
use \Icinga\Web\Url;
use \Icinga\Web\Widget\Tabs;
use \Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;
use \Icinga\Application\Icinga;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Data\ResourceFactory;
use \Icinga\Form\Config\GeneralForm;
use \Icinga\Form\Config\Authentication\ReorderForm;
use \Icinga\Form\Config\Authentication\LdapBackendForm;
use \Icinga\Form\Config\Authentication\DbBackendForm;
use \Icinga\Form\Config\Resource\EditResourceForm;
use \Icinga\Form\Config\LoggingForm;
use \Icinga\Form\Config\ConfirmRemovalForm;
use \Icinga\Config\PreservingIniWriter;

/**
 * Application wide controller for application preferences
 */
class ConfigController extends BaseConfigController
{
    /**
     * The resource types that are available.
     *
     * @var array
     */
    private $resourceTypes = array('livestatus', 'ido', 'statusdat', 'ldap');

    /**
     * Create tabs for this configuration controller
     *
     * @return  array
     *
     * @see     BaseConfigController::createProvidedTabs()
     */
    public static function createProvidedTabs()
    {
        return array(
            'index' => new Tab(
                array(
                    'name'      => 'index',
                    'title'     => 'Application',
                    'url'       => Url::fromPath('/config')
                )
            ),
            'authentication' => new Tab(
                array(
                    'name'      => 'auth',
                    'title'     => 'Authentication',
                    'url'       =>  Url::fromPath('/config/authentication')
                )
            ),
            'resources' => new Tab(
                array(
                    'name'      => 'resource',
                    'title'     => 'Resources',
                    'url'       => Url::fromPath('/config/resource')
                )
            ),
            'logging' => new Tab(
                array(
                    'name'      => 'logging',
                    'title'     => 'Logging',
                    'url'       => Url::fromPath('/config/logging')
                )
            ),
            'modules' => new Tab(
                array(
                    'name'      => 'modules',
                    'title'     => 'Modules',
                    'url'       => Url::fromPath('/config/moduleoverview')
                )
            )
        );
    }

    /**
     * Index action, entry point for configuration
     */
    public function indexAction()
    {
        $form = new GeneralForm();

        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            if (!$this->writeConfigFile($form->getConfig(), 'config'))  {
                return;
            }
            $this->view->successMessage = "Config Sucessfully Updated";
            $form->setConfiguration(IcingaConfig::app(), true);

        }
        $this->view->form = $form;
    }

    /**
     * Form for modifying the logging configuration
     */
    public function loggingAction()
    {
        $form = new LoggingForm();
        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            if (!$this->writeConfigFile($form->getConfig(), 'config')) {
                return;
            }
            $this->view->successMessage = "Config Sucessfully Updated";
            $form->setConfiguration(IcingaConfig::app(), true);
        }
        $this->view->form = $form;
    }

    /**
     * Display the list of all modules
     */
    public function moduleoverviewAction()
    {
        $this->view->modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('name');
        $this->render('module/overview');
    }

    /**
     * Enable a specific module provided by the 'name' param
     */
    public function moduleenableAction()
    {
        $module = $this->getParam('name');
        $manager = Icinga::app()->getModuleManager();
        try {
            $manager->enableModule($module);
            $manager->loadModule($module);
            $this->view->successMessage = 'Module "' . $module . '" enabled';
            $this->moduleoverviewAction();
        } catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();
            $this->view->moduleName = $module;
            $this->view->action = 'enable';
            $this->render('module-configuration-error');
        }
    }

    /**
     * Disable a module specific module provided by the 'name' param
     */
    public function moduledisableAction()
    {
        $module = $this->getParam('name');
        $manager = Icinga::app()->getModuleManager();
        try {
            $manager->disableModule($module);
            $this->view->successMessage = 'Module "' . $module . '" disabled';
            $this->moduleoverviewAction();
        } catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();
            $this->view->moduleName = $module;
            $this->view->action = 'disable';
            $this->render('module-configuration-error');
        }
    }

    /**
     * Action for creating a new authentication backend
     */
    public function authenticationAction($showOnly = false)
    {
        $config = IcingaConfig::app('authentication', true);
        $order = array_keys($config->toArray());
        $this->view->backends = array();

        foreach ($config as $backend=>$backendConfig) {
            $form = new ReorderForm();
            $form->setName('form_reorder_backend_' . $backend);
            $form->setAuthenticationBackend($backend);
            $form->setCurrentOrder($order);
            $form->setRequest($this->_request);

            if (!$showOnly && $form->isSubmittedAndValid()) {
                if ($this->writeAuthenticationFile($form->getReorderedConfig($config))) {
                    $this->view->successMessage = 'Authentication Order Updated';
                    $this->authenticationAction(true);
                }
                return;
            }

            $this->view->backends[] = (object) array(
                'name'          =>  $backend,
                'reorderForm'   =>  $form
            );
        }
        $this->render('authentication');
    }

    /**
     * Action for creating a new authentication backend
     */
    public function createauthenticationbackendAction()
    {
        if ($this->getRequest()->getParam('type') === 'ldap') {
            $form = new LdapBackendForm();
        } else {
            $form = new DbBackendForm();
        }
        if ($this->getParam('auth_backend')) {
            $form->setBackendName($this->getParam('auth_backend'));
        }
        $form->setRequest($this->getRequest());

        if ($form->isSubmittedAndValid()) {
            $backendCfg = IcingaConfig::app('authentication')->toArray();
            foreach ($backendCfg as $backendName => $settings) {
                unset($backendCfg[$backendName]['name']);
            }
            foreach ($form->getConfig() as $backendName => $settings) {
                unset($settings->{'name'});
                if (isset($backendCfg[$backendName])) {
                    $this->view->errorMessage = 'Backend name already exists';
                    $this->view->form = $form;
                    $this->render('authentication/create');
                    return;
                }
                $backendCfg[$backendName] = $settings;
            }
            if ($this->writeAuthenticationFile($backendCfg)) {
                // redirect to overview with success message
                $this->view->successMessage = 'Backend Modification Written';
                $this->authenticationAction(true);
            }
            return;
        }
        $this->view->form = $form;
        $this->render('authentication/create');
    }


    /**
     *  Form for editing backends
     *
     *  Mostly the same like the createAuthenticationBackendAction, but with additional checks for backend existence
     *  and form population
     */
    public function editauthenticationbackendAction()
    {
        $configArray = IcingaConfig::app('authentication', true)->toArray();
        $authBackend =  $this->getParam('auth_backend');
        if (!isset($configArray[$authBackend])) {
            $this->view->errorMessage = 'Can\'t edit: Unknown Authentication Backend Provided';
            $this->authenticationAction(true);
            return;
        }
        if (!array_key_exists('resource', $configArray[$authBackend])) {
            $this->view->errorMessage = 'Configuration error: Backend "' . $authBackend . '" has no Resource';
            $this->authenticationAction(true);
            return;
        }

        $type = ResourceFactory::getResourceConfig($configArray[$authBackend]['resource'])->type;
        switch ($type) {
            case 'ldap':
                $form = new LdapBackendForm();
                break;
            case 'db':
                $form = new DbBackendForm();
                break;
            default:
                $this->view->errorMessage = 'Can\'t edit: backend type "' . $type . '" of given resource not supported.';
                $this->authenticationAction(true);
                return;
        }

        $form->setBackendName($this->getParam('auth_backend'));
        $form->setBackend(IcingaConfig::app('authentication', true)->$authBackend);
        $form->setRequest($this->getRequest());

        if ($form->isSubmittedAndValid()) {
            $backendCfg = IcingaConfig::app('authentication')->toArray();
            foreach ($form->getConfig() as $backendName => $settings) {
                $backendCfg[$backendName] = $settings;
                // Remove the old section if the backend is renamed
                if ($backendName != $authBackend) {
                    unset($backendCfg[$authBackend]);
                }
                unset($settings['name']);
            }
            if ($this->writeAuthenticationFile($backendCfg)) {
                // redirect to overview with success message
                $this->view->successMessage = 'Backend "' . $authBackend . '" created';
                $this->authenticationAction(true);
            }
            return;
        }

        $this->view->name = $authBackend;
        $this->view->form = $form;
        $this->render('authentication/modify');
    }

    /**
     * Action for removing a backend from the authentication list.
     *
     * Redirects to the overview after removal is finished
     */
    public function removeauthenticationbackendAction()
    {
        $configArray = IcingaConfig::app('authentication', true)->toArray();
        $authBackend =  $this->getParam('auth_backend');
        if (!isset($configArray[$authBackend])) {
            $this->view->errorMessage = 'Can\'t perform removal: Unknown Authentication Backend Provided';
            $this->authenticationAction(true);
            return;
        }
        if (!array_key_exists('resource', $configArray[$authBackend])) {
            $this->view->errorMessage = 'Configuration error: Backend "' . $authBackend . '" has no Resource';
            $this->authenticationAction(true);
            return;
        }

        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('auth_backend', $authBackend);

        if ($form->isSubmittedAndValid()) {
            unset($configArray[$authBackend]);
            if ($this->writeAuthenticationFile($configArray)) {
                $this->view->successMessage = 'Authentication Backend "' . $authBackend . '" Removed';
                $this->authenticationAction(true);
            }
            return;
        }

        $this->view->form = $form;
        $this->view->name = $authBackend;
        $this->render('authentication/remove');
    }

    public function resourceAction($showOnly = false)
    {

        $this->view->resources = IcingaConfig::app('resources', true)->toArray();
        $this->render('resource');
    }

    public function createresourceAction()
    {
        $this->view->resourceTypes = $this->resourceTypes;
        $resources = IcingaConfig::app('resources', true);
        $form = new EditResourceForm();
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            $name = $form->getName();
            if (isset($resources->{$name})) {
                $this->view->errorMessage = 'Resource name "' . $name .'" already in use.';
                $this->view->form = $form;
                $this->render('resource/create');
                return;
            }
            $resources->{$name} = $form->getConfig();
            if ($this->writeConfigFile($resources, 'resources')) {
                $this->view->successMessage = 'Resource "' . $name . '" created.';
                $this->resourceAction(true);
            }
            return;
        }
        $this->view->form = $form;
        $this->render('resource/create');
    }

    public function editresourceAction()
    {
        $resources = ResourceFactory::getResourceConfigs();
        $name =  $this->getParam('resource');
        if ($resources->get($name) === null) {
            $this->view->errorMessage = 'Can\'t edit: Unknown Resource Provided';
            $this->resourceAction(true);
            return;
        }
        $form = new EditResourceForm();
        if ($this->_request->isPost() === false) {
            $form->setOldName($name);
            $form->setName($name);
        }
        $form->setRequest($this->_request);
        $form->setResource($resources->get($name));
        if ($form->isSubmittedAndValid()) {
            $oldName = $form->getOldName();
            $name = $form->getName();
            if ($oldName !== $name) {
                unset($resources->{$oldName});
            }
            $resources->{$name} = $form->getConfig();
            if ($this->writeConfigFile($resources, 'resources')) {
                $this->view->successMessage = 'Resource "' . $name . '" created.';
                $this->resourceAction(true);
            }
            return;
        }
        $this->view->form = $form;
        $this->view->name = $name;
        $this->render('resource/modify');
    }

    public function removeresourceAction()
    {
        $resources = ResourceFactory::getResourceConfigs()->toArray();
        $name =  $this->getParam('resource');
        if (!isset($resources[$name])) {
            $this->view->errorMessage = 'Can\'t remove: Unknown resource provided';
            $this->resourceAction(true);
            return;
        }

        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('resource', $name);
        if ($form->isSubmittedAndValid()) {
            unset($resources[$name]);
            if ($this->writeConfigFile($resources, 'resources')) {
                $this->view->successMessage = 'Resource "' . $name . '" removed';
                $this->resourceAction(true);
            }
            return;
        }

        $this->view->name = $name;
        $this->view->form = $form;
        $this->render('resource/remove');
    }

    /**
     * Write changes to an authentication file
     *
     * @param   array $config The configuration changes
     *
     * @return  bool True when persisting succeeded, otherwise false
     *
     * @see     writeConfigFile()
     */
    private function writeAuthenticationFile($config) {
        return $this->writeConfigFile($config, 'authentication');
    }

    /**
     * Write changes to a configuration file $file, using the supplied writer or PreservingIniWriter if none is set
     *
     * @param   array|Zend_Config   $config The configuration to write
     * @param   string              $file   The filename to write to (without .ini)
     * @param   Zend_Config_Writer  $writer An optional writer to use for persisting changes
     *
     * @return  bool True when persisting succeeded, otherwise false
     */
    private function writeConfigFile($config, $file, $writer = null)
    {
        if (is_array($config)) {
            $config = new Zend_Config($config);
        }
        if ($writer === null) {
            $writer = new PreservingIniWriter(
                array(
                    'config' => $config,
                    'filename' => IcingaConfig::app($file)->getConfigFile()
                )
            );
        }
        try {
            $writer->write();
            return true;
        } catch (Exception $exc) {
            $this->view->exceptionMessage = $exc->getMessage();
            $this->view->iniConfigurationString = $writer->render();
            $this->view->file = $file;
            $this->render('show-configuration');
            return false;
        }
    }
}
// @codingStandardsIgnoreEnd
