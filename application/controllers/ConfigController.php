<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\Config\UserBackendConfigForm;
use Icinga\Forms\Config\UserBackendReorderForm;
use Icinga\Forms\Config\GeneralConfigForm;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget;

/**
 * Application and module configuration
 */
class ConfigController extends Controller
{
    /**
     * Create and return the tabs to display when showing application configuration
     */
    public function createApplicationTabs()
    {
        $tabs = $this->getTabs();
        $tabs->add('general', array(
            'title' => $this->translate('Adjust the general configuration of Icinga Web 2'),
            'label' => $this->translate('General'),
            'url'   => 'config/general'
        ));
        $tabs->add('resource', array(
            'title' => $this->translate('Configure which resources are being utilized by Icinga Web 2'),
            'label' => $this->translate('Resources'),
            'url'   => 'config/resource'
        ));
        return $tabs;
    }

    /**
     * Create and return the tabs to display when showing authentication configuration
     */
    public function createAuthenticationTabs()
    {
        $tabs = $this->getTabs();
        $tabs->add('userbackend', array(
            'title' => $this->translate('Configure how users authenticate with and log into Icinga Web 2'),
            'label' => $this->translate('User Backends'),
            'url'   => 'config/userbackend'
        ));
        $tabs->add('usergroupbackend', array(
            'title' => $this->translate('Configure how users are associated with groups by Icinga Web 2'),
            'label' => $this->translate('User Group Backends'),
            'url'   => 'usergroupbackend/list'
        ));
        return $tabs;
    }

    public function devtoolsAction()
    {
        $this->view->tabs = null;
    }

    /**
     * Redirect to the general configuration
     */
    public function indexAction()
    {
        $this->redirectNow('config/general');
    }

    /**
     * General configuration
     *
     * @throws SecurityException    If the user lacks the permission for configuring the general configuration
     */
    public function generalAction()
    {
        $this->assertPermission('config/application/general');
        $form = new GeneralConfigForm();
        $form->setIniConfig(Config::app());
        $form->handleRequest();

        $this->view->form = $form;
        $this->createApplicationTabs()->activate('general');
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
            ->order('name');
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
     * Action for listing and reordering user backends
     */
    public function userbackendAction()
    {
        $this->assertPermission('config/application/userbackend');
        $form = new UserBackendReorderForm();
        $form->setIniConfig(Config::app('authentication'));
        $form->handleRequest();

        $this->view->form = $form;
        $this->createAuthenticationTabs()->activate('userbackend');
        $this->render('userbackend/reorder');
    }

    /**
     * Action for creating a new user backend
     */
    public function createuserbackendAction()
    {
        $this->assertPermission('config/application/userbackend');
        $form = new UserBackendConfigForm();
        $form->setTitle($this->translate('Create New User Backend'));
        $form->addDescription($this->translate(
            'Create a new backend for authenticating your users. This backend'
            . ' will be added at the end of your authentication order.'
        ));
        $form->setIniConfig(Config::app('authentication'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('config/userbackend');
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('userbackend/create');
    }

    /**
     * Action for editing user backends
     */
    public function edituserbackendAction()
    {
        $this->assertPermission('config/application/userbackend');
        $form = new UserBackendConfigForm();
        $form->setTitle($this->translate('Edit User Backend'));
        $form->setIniConfig(Config::app('authentication'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('config/userbackend');
        $form->setAction(Url::fromRequest());
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('userbackend/modify');
    }

    /**
     * Action for removing a user backend
     */
    public function removeuserbackendAction()
    {
        $this->assertPermission('config/application/userbackend');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($form) {
                $configForm = new UserBackendConfigForm();
                $configForm->setIniConfig(Config::app('authentication'));
                $authBackend = $form->getRequest()->getQuery('backend');

                try {
                    $configForm->remove($authBackend);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return false;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(
                        t('User backend "%s" has been successfully removed'),
                        $authBackend
                    ));
                } else {
                    return false;
                }
            }
        ));
        $form->setTitle($this->translate('Remove User Backend'));
        $form->setRedirectUrl('config/userbackend');
        $form->setAction(Url::fromRequest());
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('userbackend/remove');
    }

    /**
     * Display all available resources and a link to create a new one and to remove existing ones
     */
    public function resourceAction()
    {
        $this->assertPermission('config/application/resources');
        $this->view->resources = Config::app('resources', true)->keys();
        $this->createApplicationTabs()->activate('resource');
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
                    return false;
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
                        'The resource "%s" is currently utilized for authentication by user backend "%s". ' .
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
