<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BaseConfigController;
use Icinga\Web\Widget\AlertMessageBox;
use Icinga\Web\Notification;
use Icinga\Application\Modules\Module;
use Icinga\Web\Url;
use Icinga\Web\Form;
use Icinga\Web\Widget;
use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Form\Config\GeneralForm;
use Icinga\Form\Config\Authentication\LdapBackendForm;
use Icinga\Form\Config\Authentication\DbBackendForm;
use Icinga\Form\Config\Authentication\AutologinBackendForm;
use Icinga\Form\Config\ResourceForm;
use Icinga\Form\Config\LoggingForm;
use Icinga\Form\Config\ConfirmRemovalForm;
use Icinga\Config\PreservingIniWriter;
use Icinga\Exception\ConfigurationError;


/**
 * Application wide controller for application preferences
 */
class ConfigController extends BaseConfigController
{
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
        $request = $this->getRequest();
        $currentConfig = IcingaConfig::app();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $formConfig = $form->getConfiguration();
                $newConfig = new Zend_Config($currentConfig->toArray(), true);
                $newConfig->global = $formConfig->global;
                $newConfig->preferences = $formConfig->preferences;
                if ($this->writeConfigFile($newConfig, 'config')) {
                    Notification::success($this->translate('New configuration has successfully been stored'));
                    $this->redirectNow('config');
                }
            }
        } else {
            $form->setConfiguration($currentConfig);
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
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                $appConfig = IcingaConfig::app();
                $appConfig->logging = new Zend_Config($form->getValues());
                if ($this->writeConfigFile($appConfig, 'config')) {
                    $this->addSuccessMessage($this->translate('Logging configuration has sucessfully been stored'));
                    $this->redirectNow('config/logging');
                }
            }
        } else {
            $loggingConfig = Icinga::app()->getConfig()->logging;
            if ($loggingConfig === null) {
                $loggingConfig = new Zend_Config(array());
            }
            $form->populate($loggingConfig->toArray());
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
     * Action for reordering authentication backends
     */
    public function authenticationAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->tabs->activate('authentication');

        $config = IcingaConfig::app('authentication');
        $backendOrder = array_keys($config->toArray());
        $form = new Form();
        $form->setName('form_reorder_authbackend');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $requestData = $request->getPost();
            if ($form->isValid($requestData)) { // Validate the CSRF token
                $reordered = false;
                foreach ($backendOrder as $backendName) {
                    if (isset($requestData[$backendName])) {
                        array_splice($backendOrder, array_search($backendName, $backendOrder), 1);
                        array_splice($backendOrder, $requestData[$backendName], 0, $backendName);
                        $reordered = true;
                        break;
                    }
                }

                if ($reordered) {
                    $reorderedConfig = array();
                    foreach ($backendOrder as $backendName) {
                        $reorderedConfig[$backendName] = $config->{$backendName};
                    }

                    if ($this->writeAuthenticationFile($reorderedConfig)) {
                        Notification::success($this->translate('Authentication order updated!'));
                        $this->redirectNow('config/authentication');
                    }
                }
            }
        }

        $this->view->form = $form->create(); // Necessary in case its a GET request
        $this->view->backendNames = $backendOrder;
    }

    /**
     * Action for creating a new authentication backend
     */
    public function createauthenticationbackendAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->tabs->activate('authentication');

        $backendType = $this->getRequest()->getParam('type');
        $authenticationConfig = IcingaConfig::app('authentication')->toArray();
        try {
            switch ($backendType) {
                case 'ldap':
                    $form = new LdapBackendForm();
                    break;
                case 'db':
                    $form = new DbBackendForm();
                    break;
                case 'autologin':
                    foreach ($authenticationConfig as $ac) {
                        if (array_key_exists('backend', $ac) && $ac['backend'] === 'autologin') {
                            throw new ConfigurationError(
                                $this->translate('An autologin backend already exists')
                            );
                        }
                    }
                    $form = new AutologinBackendForm();
                    break;
                default:
                    $this->addErrorMessage(sprintf(
                        $this->translate('There is no backend type `%s\''),
                        $backendType
                    ));
                    $this->redirectNow('config/configurationerror');
            }
        } catch (ConfigurationError $e) {
            $this->addErrorMessage($e->getMessage());
            $this->redirectNow('config/configurationerror');
        }

        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            list($backendName, $backendConfig) = $form->getBackendConfig();
            if (isset($authenticationConfig[$backendName])) {
                $this->addErrorMessage(
                    $this->translate('Backend name already exists')
                );
            } else {
                $authenticationConfig[$backendName] = $backendConfig;
                if ($this->writeConfigFile($authenticationConfig, 'authentication')) {
                    $this->addSuccessMessage(
                        $this->translate('Backend Modification Written.')
                    );
                    $this->redirectNow('config/authentication');
                }
            }
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
        if (false === isset($configArray[$authBackend])) {
            $this->addErrorMessage(
                $this->translate('Can\'t edit: Unknown Authentication Backend Provided')
            );
            $this->redirectNow('config/configurationerror');
        }

        if (false === array_key_exists('backend', $configArray[$authBackend])) {
            $this->addErrorMessage(sprintf(
                $this->translate('Backend "%s" has no `backend\' setting'),
                $authBackend
            ));
            $this->redirectNow('config/configurationerror');
        }
        $type = $configArray[$authBackend]['backend'];
        switch ($type) {
            case 'ldap':
                $form = new LdapBackendForm();
                break;
            case 'db':
                $form = new DbBackendForm();
                break;
            case 'autologin':
                $form = new AutologinBackendForm();
                break;
            default:
                $this->addErrorMessage(sprintf(
                    $this->translate('Can\'t edit: backend type "%s" of given resource not supported.'),
                    $type
                ));
                $this->redirectNow('config/configurationerror');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                list($backendName, $backendConfig) = $form->getBackendConfig();
                $configArray[$backendName] = $backendConfig;
                if ($backendName != $authBackend) {
                    unset($configArray[$authBackend]);
                }
                if ($this->writeAuthenticationFile($configArray)) {
                    // redirect to overview with success message
                    Notification::success(sprintf(
                        $this->translate('Backend "%s" saved'),
                        $backendName
                    ));
                    $this->redirectNow('config/authentication');
                }
                return;
            }
        } else {
            $form->setBackendConfig($authBackend, $configArray[$authBackend]);
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
        if (false === array_key_exists($authBackend, $configArray)) {
            $this->addErrorMessage(
                $this->translate('Can\'t perform removal: Unknown authentication backend provided')
            );
            $this->redirectNow('config/configurationerror');
        }

        $form = new ConfirmRemovalForm();
        $request = $this->getRequest();

        if ($request->isPost() && $form->isValid($request->getPost())) {
            unset($configArray[$authBackend]);
            if ($this->writeAuthenticationFile($configArray)) {
                Notification::success(sprintf(
                    $this->translate('Authentication Backend "%s" Removed'),
                    $authBackend
                ));
                $this->redirectNow('config/authentication');
            }
        }

        $this->view->form = $form;
        $this->view->name = $authBackend;
        $this->render('authentication/remove');
    }

    /**
     * Display all available resources and a link to create a new one
     */
    public function resourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);
        $this->view->tabs->activate('resources');

        $this->view->resources = IcingaConfig::app('resources', true)->toArray();
    }

    /**
     * Display a form to create a new resource
     */
    public function createresourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

        $form = new ResourceForm();
        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            list($name, $config) = $form->getResourceConfig();
            $resources = IcingaConfig::app('resources')->toArray();
            if (array_key_exists($name, $resources)) {
                $this->addErrorMessage(sprintf($this->translate('Resource name "%s" already in use.'), $name));
            } else {
                $resources[$name] = $config;
                if ($this->writeConfigFile($resources, 'resources')) {
                    $this->addSuccessMessage(sprintf($this->translate('Resource "%s" successfully created.'), $name));
                    $this->redirectNow('config/resource');
                }
            }
        }

        $this->view->form = $form;
        $this->view->messageBox->addForm($form);
        $this->render('resource/create');
    }

    /**
     * Display a form to edit a existing resource
     */
    public function editresourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

        // Fetch the resource to be edited
        $resources = IcingaConfig::app('resources')->toArray();
        $name = $this->getParam('resource');
        if (false === array_key_exists($name, $resources)) {
            $this->addErrorMessage(sprintf($this->translate('Cannot edit "%s". Resource not found.'), $name));
            $this->redirectNow('config/configurationerror');
        }

        $form = new ResourceForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                list($newName, $config) = $form->getResourceConfig();

                if ($newName !== $name) {
                    // Resource name has changed
                    unset($resources[$name]); // We can safely use unset as all values are part of the form
                }

                $resources[$newName] = $config;
                if ($this->writeConfigFile($resources, 'resources')) {
                    $this->addSuccessMessage(sprintf($this->translate('Resource "%s" successfully edited.'), $name));
                    $this->redirectNow('config/resource');
                }
            }
        } else {
            $form->setResourceConfig($name, $resources[$name]);
        }

        $this->view->form = $form;
        $this->view->messageBox->addForm($form);
        $this->render('resource/modify');
    }

    /**
     * Display a confirmation form to remove a resource
     */
    public function removeresourceAction()
    {
        $this->view->messageBox = new AlertMessageBox(true);

        // Fetch the resource to be removed
        $resources = IcingaConfig::app('resources')->toArray();
        $name = $this->getParam('resource');
        if (false === array_key_exists($name, $resources)) {
            $this->addErrorMessage(sprintf($this->translate('Cannot remove "%s". Resource not found.'), $name));
            $this->redirectNow('config/configurationerror');
        }

        // Check if selected resource is currently used for authentication
        $authConfig = IcingaConfig::app('authentication')->toArray();
        foreach ($authConfig as $backendName => $config) {
            if (array_key_exists('resource', $config) && $config['resource'] === $name) {
                $this->addWarningMessage(
                    sprintf(
                        $this->translate(
                            'The resource "%s" is currently in use by the authentication backend "%s". ' .
                            'Removing the resource can result in noone being able to log in any longer.'
                        ),
                        $name,
                        $backendName
                    )
                );
            }
        }

        $form = new ConfirmRemovalForm();
        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            unset($resources[$name]);
            if ($this->writeConfigFile($resources, 'resources')) {
                $this->addSuccessMessage(sprintf($this->translate('Resource "%s" successfully removed.'), $name));
                $this->redirectNow('config/resource');
            }
        }

        $this->view->form = $form;
        $this->view->messageBox->addForm($form);
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
