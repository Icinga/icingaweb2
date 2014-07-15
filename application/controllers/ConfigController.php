<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BaseConfigController;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\AlertMessageBox;
use Icinga\Web\Notification;
use Icinga\Application\Modules\Module;
use Icinga\Web\Url;
use Icinga\Web\Widget;
use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Form\Config\GeneralForm;
use Icinga\Form\Config\Authentication\ReorderForm;
use Icinga\Form\Config\Authentication\LdapBackendForm;
use Icinga\Form\Config\Authentication\DbBackendForm;
use Icinga\Form\Config\ResourceForm;
use Icinga\Form\Config\LoggingForm;
use Icinga\Form\Config\ConfirmRemovalForm;
use Icinga\Config\PreservingIniWriter;


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

    public function init()
    {
        $this->view->tabs = Widget::create('tabs')->add('index', array(
            'title' => 'Application',
            'url'   => 'config'
        ))->add('authentication', array(
            'title' => 'Authentication',
            'url'   => 'config/authentication'
        ))->add('resources', array(
            'title' => 'Resources',
            'url'   => 'config/resource'
        ))->add('logging', array(
            'title' => 'Logging',
            'url'   => 'config/logging'
        ))->add('modules', array(
            'title' => 'Modules',
            'url'   => 'config/modules'
        ));
    }

    public function devtoolsAction()
    {
        $this->view->tabs = null;
    }

    /**
     * Index action, entry point for configuration
     */
    public function indexAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->tabs->activate('index');
        $form = new GeneralForm();
        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            if (!$this->writeConfigFile($form->getConfig(), 'config'))  {
                return;
            }
            Notification::success('New configuration has successfully been stored');
            $form->setConfiguration(IcingaConfig::app(), true);
            $this->redirectNow('config/index');
        }
        $this->view->form = $form;
    }

    /**
     * Form for modifying the logging configuration
     */
    public function loggingAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->tabs->activate('logging');

        $form = new LoggingForm();
        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            if (!$this->writeConfigFile($form->getConfig(), 'config')) {
                return;
            }
            Notification::success('New configuration has sucessfully been stored');
            $form->setConfiguration(IcingaConfig::app(), true);
            $this->redirectNow('config/logging');
        }
        $this->view->form = $form;
    }

    /**
     * Display the list of all modules
     */
    public function modulesAction()
    {
        $this->view->tabs->activate('modules');
        $this->view->modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('enabled', 'desc')
            ->order('name')->paginate();
    }

    public function moduleAction()
    {
        $name = $this->getParam('name');
        $app = Icinga::app();
        $manager = $app->getModuleManager();
        if ($manager->hasInstalled($name)) {
            $this->view->moduleData = Icinga::app()->getModuleManager()->select()
            ->from('modules')->where('name', $name)->fetchRow();
            $module = new Module($app, $name, $manager->getModuleDir($name));
            $this->view->module = $module;
        } else {
            $this->view->module = false;
        }
        $this->view->tabs = $module->getConfigTabs()->activate('info');
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
            Notification::success('Module "' . $module . '" enabled');
            $this->rerenderLayout()->reloadCss()->redirectNow('config/modules');
        } catch (Exception $e) {
            $this->view->exceptionMesssage = $e->getMessage();
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
            Notification::success('Module "' . $module . '" disabled');
            $this->rerenderLayout()->reloadCss()->redirectNow('config/modules');
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
    public function authenticationAction()
    {
        $config = IcingaConfig::app('authentication', true);
        $this->view->tabs->activate('authentication');

        $order = array_keys($config->toArray());
        $this->view->messageBox = new AlertMessageBox(true);

        $backends = array();
        foreach ($order as $backend) {
            $form = new ReorderForm();
            $form->setName('form_reorder_backend_' . $backend);
            $form->setBackendName($backend);
            $form->setCurrentOrder($order);
            $form->setRequest($this->_request);

            if ($form->isSubmittedAndValid()) {
                if ($this->writeAuthenticationFile($form->getReorderedConfig($config))) {
                    Notification::success('Authentication Order Updated');
                    $this->redirectNow('config/authentication');
                }

                return;
            }

            $backends[] = (object) array(
                'name'          => $backend,
                'reorderForm'   => $form
            );
        }

        $this->view->backends = $backends;
    }

    /**
     * Action for creating a new authentication backend
     */
    public function createauthenticationbackendAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

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
                    $this->addErrorMessage('Backend name already exists');
                    $this->view->form = $form;
                    $this->render('authentication/create');
                    return;
                }
                $backendCfg[$backendName] = $settings;
            }
            if ($this->writeAuthenticationFile($backendCfg)) {
                // redirect to overview with success message
                Notification::success('Backend Modification Written.');
                $this->redirectNow("config/authentication");
            }
            return;
        }

        $this->view->messageBox->addForm($form);
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
        $this->view->messageBox = new AlertMessageBox(true);

        $configArray = IcingaConfig::app('authentication', true)->toArray();
        $authBackend =  $this->getParam('auth_backend');
        if (!isset($configArray[$authBackend])) {
            $this->addErrorMessage('Can\'t edit: Unknown Authentication Backend Provided');
            $this->configurationerrorAction();
            return;
        }
        if (!array_key_exists('resource', $configArray[$authBackend])) {
            $this->addErrorMessage('Configuration error: Backend "' . $authBackend . '" has no Resource');
            $this->configurationerrorAction();
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
                $this->addErrorMessage('Can\'t edit: backend type "' . $type . '" of given resource not supported.');
                $this->configurationerrorAction();
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
                Notification::success('Backend "' . $authBackend . '" created');
                $this->redirectNow("config/authentication");
            }
            return;
        }

        $this->view->messageBox->addForm($form);
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
        $this->view->messageBox = new AlertMessageBox(true);

        $configArray = IcingaConfig::app('authentication', true)->toArray();
        $authBackend =  $this->getParam('auth_backend');
        if (!isset($configArray[$authBackend])) {
            Notification::error('Can\'t perform removal: Unknown Authentication Backend Provided');
            $this->render('authentication/remove');
            return;
        }

        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('auth_backend', $authBackend);

        if ($form->isSubmittedAndValid()) {
            unset($configArray[$authBackend]);
            if ($this->writeAuthenticationFile($configArray)) {
                Notification::success('Authentication Backend "' . $authBackend . '" Removed');
                $this->redirectNow("config/authentication");
            }
            return;
        }

        $this->view->form = $form;
        $this->view->name = $authBackend;
        $this->render('authentication/remove');
    }

    public function resourceAction($showOnly = false)
    {
        $this->view->tabs->activate('resources');

        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->resources = IcingaConfig::app('resources', true)->toArray();
        $this->render('resource');
    }

    public function createresourceAction()
    {
        $this->view->resourceTypes = $this->resourceTypes;
        $resources = IcingaConfig::app('resources', true);
        $form = new ResourceForm();
        $form->setRequest($this->_request);
        if ($form->isSubmittedAndValid()) {
            $name = $form->getName();
            if (isset($resources->{$name})) {
                $this->addErrorMessage('Resource name "' . $name .'" already in use.');
            } else {
                $resources->{$name} = $form->getConfig();
                if ($this->writeConfigFile($resources, 'resources')) {
                    $this->addSuccessMessage('Resource "' . $name . '" created.');
                    $this->redirectNow("config/resource");
                }
            }
        }

        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->messageBox->addForm($form);
        $this->view->form = $form;
        $this->render('resource/create');
    }

    public function editresourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

        $resources = ResourceFactory::getResourceConfigs();
        $name =  $this->getParam('resource');
        if ($resources->get($name) === null) {
            $this->addErrorMessage('Can\'t edit: Unknown Resource Provided');
            $this->render('resource/modify');
            return;
        }
        $form = new ResourceForm();
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
                $this->addSuccessMessage('Resource "' . $name . '" edited.');
                $this->redirectNow("config/resource");
            }
            return;
        }

        $this->view->messageBox->addForm($form);
        $this->view->form = $form;
        $this->view->name = $name;
        $this->render('resource/modify');
    }

    public function removeresourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

        $resources = ResourceFactory::getResourceConfigs()->toArray();
        $name =  $this->getParam('resource');
        if (!isset($resources[$name])) {
            $this->addSuccessMessage('Can\'t remove: Unknown resource provided');
            $this->render('resource/remove');
            return;
        }

        $form = new ConfirmRemovalForm();
        $form->setRequest($this->getRequest());
        $form->setRemoveTarget('resource', $name);

        // Check if selected resource is currently used for authentication
        $authConfig = IcingaConfig::app('authentication', true)->toArray();
        foreach ($authConfig as $backendName => $config) {
           if (array_key_exists('resource', $config) && $config['resource'] === $name) {
              $this->addErrorMessage(
				'Warning: The resource "' . $name . '" is currently used for user authentication by "' . $backendName  . '". ' .
				' Deleting it could eventally make login impossible.'
              );
           }
        }

        if ($form->isSubmittedAndValid()) {
            unset($resources[$name]);
            if ($this->writeConfigFile($resources, 'resources')) {
                $this->addSuccessMessage('Resource "' . $name . '" removed.');
                $this->redirectNow('config/resource');
            }
            return;
        }

        $this->view->name = $name;
        $this->view->form = $form;
        $this->render('resource/remove');
    }

    /**
     * Redirect target only for error-states
     *
     * When an error is opened in the side-pane, redirecting this request to the index or the overview will look
     * weird. This action returns a clear page containing only an AlertMessageBox.
     */
    public function configurationerrorAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->render('error/error', null, true);
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
