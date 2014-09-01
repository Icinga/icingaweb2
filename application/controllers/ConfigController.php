<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\BaseConfigController;
use Icinga\Web\Widget\AlertMessageBox;
use Icinga\Web\Notification;
use Icinga\Application\Modules\Module;
use Icinga\Web\Widget;
use Icinga\Application\Icinga;
use Icinga\Application\Config as IcingaConfig;
use Icinga\Form\Config\GeneralForm;
use Icinga\Form\Config\AuthenticationBackendReorderForm;
use Icinga\Form\Config\AuthenticationBackendConfigForm;
use Icinga\Form\Config\ResourceForm;
use Icinga\Form\ConfirmRemovalForm;
use Icinga\Config\PreservingIniWriter;
use Icinga\Data\ResourceFactory;


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
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                if ($this->writeConfigFile($form->getConfiguration(), 'config')) {
                    Notification::success($this->translate('New configuration has successfully been stored'));
                    $this->redirectNow('config');
                }
            }
        } else {
            $form->setConfiguration(IcingaConfig::app());
        }

        $this->view->form = $form;
    }

    /**
     * Display the list of all modules
     */
    public function modulesAction()
    {
        $this->view->tabs = Widget::create('tabs')->add('modules', array(
            'title' => 'Modules',
            'url'   => 'config/modules'
        ));

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
     * Action for listing and reordering authentication backends
     */
    public function authenticationAction()
    {
        $form = new AuthenticationBackendReorderForm();
        $form->setConfig(IcingaConfig::app('authentication'));
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('authentication');
        $this->render('authentication/reorder');
    }

    /**
     * Action for creating a new authentication backend
     */
    public function createauthenticationbackendAction()
    {
        $form = new AuthenticationBackendConfigForm();
        $form->setConfig(IcingaConfig::app('authentication'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('config/authentication');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('authentication');
        $this->render('authentication/create');
    }

    /**
     * Action for editing authentication backends
     */
    public function editauthenticationbackendAction()
    {
        $form = new AuthenticationBackendConfigForm();
        $form->setConfig(IcingaConfig::app('authentication'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('config/authentication');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('authentication');
        $this->render('authentication/modify');
    }

    /**
     * Action for removing a backend from the authentication list
     */
    public function removeauthenticationbackendAction()
    {
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($request) {
                $configForm = new AuthenticationBackendConfigForm();
                $configForm->setConfig(IcingaConfig::app('authentication'));
                $authBackend = $request->getQuery('auth_backend');

                try {
                    $configForm->remove($authBackend);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(
                        t('Authentication backend "%s" has been successfully removed'),
                        $authBackend
                    ));
                } else {
                    return false;
                }
            }
        ));
        $form->setRedirectUrl('config/authentication');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('authentication');
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
