<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\Config\AuthenticationBackendConfigForm;
use Icinga\Forms\Config\AuthenticationBackendReorderForm;
use Icinga\Forms\Config\GeneralConfigForm;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Web\Widget;

/**
 * Application and module configuration
 */
class ConfigController extends Controller
{
    /**
     * The first allowed config action according to the user's permissions
     *
     * @var string
     */
    protected $firstAllowedAction;

    /**
     * Initialize tabs and validate the user's permissions
     *
     * @throws SecurityException    If the user does not have any configuration permission
     */
    public function init()
    {
        $tabs = $this->getTabs();
        $auth = $this->Auth();
        $allowedActions = array();
        if ($auth->hasPermission('config/application/general')) {
            $tabs->add('application', array(
                'title' => $this->translate('Adjust the general configuration of Icinga Web 2'),
                'label' => $this->translate('Application'),
                'url'   => 'config/application'
            ));
            $allowedActions[] = 'application';
        }
        if ($auth->hasPermission('config/application/authentication')) {
            $tabs->add('authentication', array(
                'title' => $this->translate('Configure how users authenticate with and log into Icinga Web 2'),
                'label' => $this->translate('Authentication'),
                'url'   => 'config/authentication'
            ));
            $allowedActions[] = 'authentication';
        }
        if ($auth->hasPermission('config/application/resources')) {
            $tabs->add('resource', array(
                'title' => $this->translate('Configure which resources are being utilized by Icinga Web 2'),
                'label' => $this->translate('Resources'),
                'url'   => 'config/resource'
            ));
            $allowedActions[] = 'resource';
        }
        if ($auth->hasPermission('config/application/roles')) {
            $tabs->add('roles', array(
                'title' => $this->translate(
                    'Configure roles to permit or restrict users and groups accessing Icinga Web 2'
                ),
                'label' => $this->translate('Roles'),
                'url'   => 'roles'
            ));
            $allowedActions[] = 'roles';
        }
        $this->firstAllowedAction = array_shift($allowedActions);
    }

    public function devtoolsAction()
    {
        $this->view->tabs = null;
    }

    /**
     * Forward or redirect to the first allowed configuration action
     */
    public function indexAction()
    {
        if ($this->firstAllowedAction === null) {
            throw new SecurityException('No permission for configuration');
        }
        $action = $this->getTabs()->get($this->firstAllowedAction);
        if (substr($action->getUrl()->getPath(), 0, 7) === 'config/') {
            $this->forward($this->firstAllowedAction);
        } else {
            $this->redirectNow($action->getUrl());
        }
    }

    /**
     * Application configuration
     *
     * @throws SecurityException    If the user lacks the permission for configuring the application
     */
    public function applicationAction()
    {
        $this->assertPermission('config/application/general');
        $form = new GeneralConfigForm();
        $form->setIniConfig(Config::app());
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('application');
    }

    /**
     * Display the list of all modules
     */
    public function modulesAction()
    {
        // Overwrite tabs created in init
        // @TODO(el): This seems not natural to me. Module configuration should have its own controller.
        $this->view->tabs = Widget::create('tabs')
            ->add('modules', array(
                'label' => $this->translate('Modules'),
                'title' => $this->translate('List intalled modules'),
                'url'   => 'config/modules'
            ))
            ->activate('modules');
        $this->view->modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('enabled', 'desc')
            ->order('name')
            ->paginate();
        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->modules);
        // TODO: Not working
        /*$this->setupSortControl(array(
            'name'      => $this->translate('Modulename'),
            'path'      => $this->translate('Installation Path'),
            'enabled'   => $this->translate('State')
        ));*/
    }

    public function moduleAction()
    {
        $app = Icinga::app();
        $manager = $app->getModuleManager();
        $name = $this->getParam('name');
        if ($manager->hasInstalled($name)) {
            $this->view->moduleData = $manager->select()->from('modules')->where('name', $name)->fetchRow();
            if ($manager->hasLoaded($name)) {
                $module = $manager->getModule($name);
            } else {
                $module = new Module($app, $name, $manager->getModuleDir($name));
            }

            $this->view->module = $module;
            $this->view->tabs = $module->getConfigTabs()->activate('info');
        } else {
            $this->view->module = false;
            $this->view->tabs = null;
        }
    }

    /**
     * Enable a specific module provided by the 'name' param
     */
    public function moduleenableAction()
    {
        $this->assertPermission('config/modules');
        $module = $this->getParam('name');
        $manager = Icinga::app()->getModuleManager();
        try {
            $manager->enableModule($module);
            Notification::success(sprintf($this->translate('Module "%s" enabled'), $module));
            $this->rerenderLayout()->reloadCss()->redirectNow('config/modules');
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
        $this->assertPermission('config/modules');
        $module = $this->getParam('name');
        $manager = Icinga::app()->getModuleManager();
        try {
            $manager->disableModule($module);
            Notification::success(sprintf($this->translate('Module "%s" disabled'), $module));
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
        $this->assertPermission('config/application/authentication');
        $form = new AuthenticationBackendReorderForm();
        $form->setIniConfig(Config::app('authentication'));
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
        $this->assertPermission('config/application/authentication');
        $form = new AuthenticationBackendConfigForm();
        $form->setTitle($this->translate('Create New Authentication Backend'));
        $form->addDescription($this->translate(
            'Create a new backend for authenticating your users. This backend'
            . ' will be added at the end of your authentication order.'
        ));
        $form->setIniConfig(Config::app('authentication'));
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
        $this->assertPermission('config/application/authentication');
        $form = new AuthenticationBackendConfigForm();
        $form->setTitle($this->translate('Edit Backend'));
        $form->setIniConfig(Config::app('authentication'));
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
        $this->assertPermission('config/application/authentication');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($form) {
                $configForm = new AuthenticationBackendConfigForm();
                $configForm->setIniConfig(Config::app('authentication'));
                $authBackend = $form->getRequest()->getQuery('auth_backend');

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
        $form->setTitle($this->translate('Remove Backend'));
        $form->setRedirectUrl('config/authentication');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs->activate('authentication');
        $this->render('authentication/remove');
    }

    /**
     * Display all available resources and a link to create a new one and to remove existing ones
     */
    public function resourceAction()
    {
        $this->assertPermission('config/application/resources');
        $this->view->resources = Config::app('resources', true)->keys();
        $this->view->tabs->activate('resource');
    }

    /**
     * Display a form to create a new resource
     */
    public function createresourceAction()
    {
        $this->assertPermission('config/application/resources');
        $form = new ResourceConfigForm();
        $form->setTitle($this->translate('Create A New Resource'));
        $form->addDescription($this->translate('Resources are entities that provide data to Icinga Web 2.'));
        $form->setIniConfig(Config::app('resources'));
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('resource/create');
    }

    /**
     * Display a form to edit a existing resource
     */
    public function editresourceAction()
    {
        $this->assertPermission('config/application/resources');
        $form = new ResourceConfigForm();
        $form->setTitle($this->translate('Edit Existing Resource'));
        $form->setIniConfig(Config::app('resources'));
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('resource/modify');
    }

    /**
     * Display a confirmation form to remove a resource
     */
    public function removeresourceAction()
    {
        $this->assertPermission('config/application/resources');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($form) {
                $configForm = new ResourceConfigForm();
                $configForm->setIniConfig(Config::app('resources'));
                $resource = $form->getRequest()->getQuery('resource');

                try {
                    $configForm->remove($resource);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(t('Resource "%s" has been successfully removed'), $resource));
                } else {
                    return false;
                }
            }
        ));
        $form->setTitle($this->translate('Remove Existing Resource'));
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        // Check if selected resource is currently used for authentication
        $resource = $this->getRequest()->getQuery('resource');
        $authConfig = Config::app('authentication');
        foreach ($authConfig as $backendName => $config) {
            if ($config->get('resource') === $resource) {
                $form->addDescription(sprintf(
                    $this->translate(
                        'The resource "%s" is currently in use by the authentication backend "%s". ' .
                        'Removing the resource can result in noone being able to log in any longer.'
                    ),
                    $resource,
                    $backendName
                ));
            }
        }

        $this->view->form = $form;
        $this->render('resource/remove');
    }
}
