<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use Icinga\Application\Version;
use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ActionForm;
use Icinga\Forms\Config\GeneralConfigForm;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\Config\UserBackendConfigForm;
use Icinga\Forms\Config\UserBackendReorderForm;
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
        if ($this->hasPermission('config/general')) {
            $tabs->add('general', array(
                'title' => $this->translate('Adjust the general configuration of Icinga Web 2'),
                'label' => $this->translate('General'),
                'url'   => 'config/general',
                'baseTarget' => '_main'
            ));
        }
        if ($this->hasPermission('config/resources')) {
            $tabs->add('resource', array(
                'title' => $this->translate('Configure which resources are being utilized by Icinga Web 2'),
                'label' => $this->translate('Resources'),
                'url'   => 'config/resource',
                'baseTarget' => '_main'
            ));
        }
        if ($this->hasPermission('config/access-control/users')
            || $this->hasPermission('config/access-control/groups')
        ) {
            $tabs->add('authentication', array(
                'title' => $this->translate('Configure the user and group backends'),
                'label' => $this->translate('Access Control Backends'),
                'url'   => 'config/userbackend',
                'baseTarget' => '_main'
            ));
        }

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
        if ($this->hasPermission('config/general')) {
            $this->redirectNow('config/general');
        } elseif ($this->hasPermission('config/resources')) {
            $this->redirectNow('config/resource');
        } elseif ($this->hasPermission('config/access-control/*')) {
            $this->redirectNow('config/userbackend');
        } else {
            throw new SecurityException('No permission to configure Icinga Web 2');
        }
    }

    /**
     * General configuration
     *
     * @throws SecurityException    If the user lacks the permission for configuring the general configuration
     */
    public function generalAction()
    {
        $this->assertPermission('config/general');
        $form = new GeneralConfigForm();
        $form->setIniConfig(Config::app());
        $form->setOnSuccess(function (GeneralConfigForm $form) {
            $config = Config::app();
            $useStrictCsp = (bool) $config->get('security', 'use_strict_csp', false);
            if ($form->onSuccess() === false) {
                return false;
            }

            $appConfigForm = $form->getSubForm('form_config_general_application');
            if ($appConfigForm && (bool) $appConfigForm->getValue('security_use_strict_csp') !== $useStrictCsp) {
                $this->getResponse()->setReloadWindow(true);
            }
        })->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('General');
        $this->createApplicationTabs()->activate('general');
    }

    /**
     * Display the list of all modules
     */
    public function modulesAction()
    {
        $this->assertPermission('config/modules');
        // Overwrite tabs created in init
        // @TODO(el): This seems not natural to me. Module configuration should have its own controller.
        $this->view->tabs = Widget::create('tabs')
            ->add('modules', array(
                'label' => $this->translate('Modules'),
                'title' => $this->translate('List intalled modules'),
                'url'   => 'config/modules'
            ))
            ->activate('modules');
        $modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('enabled', 'desc')
            ->order('installed', 'asc')
            ->order('name');
        $this->setupLimitControl();
        $this->setupPaginationControl($modules);
        $this->view->modules = $modules;
        $this->view->title = $this->translate('Modules');
    }

    public function moduleAction()
    {
        $this->assertPermission('config/modules');
        $app = Icinga::app();
        $manager = $app->getModuleManager();
        $name = $this->getParam('name');
        if ($manager->hasInstalled($name) || $manager->hasEnabled($name)) {
            $this->view->moduleData = $manager->select()->from('modules')->where('name', $name)->fetchRow();
            if ($manager->hasLoaded($name)) {
                $module = $manager->getModule($name);
            } else {
                $module = new Module($app, $name, $manager->getModuleDir($name));
            }

            $toggleForm = new ActionForm();
            $toggleForm->setDefaults(['identifier' => $name]);
            if (! $this->view->moduleData->enabled) {
                $toggleForm->setAction(Url::fromPath('config/moduleenable'));
                $toggleForm->setDescription(sprintf($this->translate('Enable the %s module'), $name));
            } elseif ($this->view->moduleData->loaded) {
                $toggleForm->setAction(Url::fromPath('config/moduledisable'));
                $toggleForm->setDescription(sprintf($this->translate('Disable the %s module'), $name));
            } else {
                $toggleForm = null;
            }

            $this->view->module = $module;
            $this->view->libraries = $app->getLibraries();
            $this->view->moduleManager = $manager;
            $this->view->toggleForm = $toggleForm;
            $this->view->title = $module->getName();
            $this->view->tabs = $module->getConfigTabs()->activate('info');
            $this->view->moduleGitCommitId = Version::getGitHead($module->getBaseDir());
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

        $form = new ActionForm();
        $form->setOnSuccess(function (ActionForm $form) {
            $moduleName = $form->getValue('identifier');
            $module = Icinga::app()->getModuleManager()
                ->enableModule($moduleName)
                ->getModule($moduleName);
            Notification::success(sprintf($this->translate('Module "%s" enabled'), $moduleName));
            $form->onSuccess();

            if ($module->hasJs()) {
                $this->getResponse()
                    ->setReloadWindow(true)
                    ->sendResponse();
            } else {
                if ($module->hasCss()) {
                    $this->reloadCss();
                }

                $this->rerenderLayout()->redirectNow('config/modules');
            }
        });

        try {
            $form->handleRequest();
        } catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();
            $this->view->moduleName = $form->getValue('identifier');
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

        $form = new ActionForm();
        $form->setOnSuccess(function (ActionForm $form) {
            $mm = Icinga::app()->getModuleManager();
            $moduleName = $form->getValue('identifier');
            $module = $mm->getModule($moduleName);
            $mm->disableModule($moduleName);
            Notification::success(sprintf($this->translate('Module "%s" disabled'), $moduleName));
            $form->onSuccess();

            if ($module->hasJs()) {
                $this->getResponse()
                    ->setReloadWindow(true)
                    ->sendResponse();
            } else {
                if ($module->hasCss()) {
                    $this->reloadCss();
                }

                $this->rerenderLayout()->redirectNow('config/modules');
            }
        });

        try {
            $form->handleRequest();
        } catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();
            $this->view->moduleName = $form->getValue('identifier');
            $this->view->action = 'disable';
            $this->render('module-configuration-error');
        }
    }

    /**
     * Action for listing user and group backends
     */
    public function userbackendAction()
    {
        if ($this->hasPermission('config/access-control/users')) {
            $form = new UserBackendReorderForm();
            $form->setIniConfig(Config::app('authentication'));
            $form->handleRequest();
            $this->view->form = $form;
        }

        if ($this->hasPermission('config/access-control/groups')) {
            $this->view->backendNames = Config::app('groups');
        }

        $this->createApplicationTabs()->activate('authentication');
        $this->view->title = $this->translate('Authentication');
        $this->render('userbackend/reorder');
    }

    /**
     * Create a new user backend
     */
    public function createuserbackendAction()
    {
        $this->assertPermission('config/access-control/users');
        $form = new UserBackendConfigForm();
        $form
            ->setRedirectUrl('config/userbackend')
            ->addDescription($this->translate(
                'Create a new backend for authenticating your users. This backend'
                . ' will be added at the end of your authentication order.'
            ))
            ->setIniConfig(Config::app('authentication'));

        try {
            $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        } catch (ConfigurationError $e) {
            if ($this->hasPermission('config/resources')) {
                Notification::error($e->getMessage());
                $this->redirectNow('config/createresource');
            }

            throw $e; // No permission for resource configuration, show the error
        }

        $form->setOnSuccess(function (UserBackendConfigForm $form) {
            try {
                $form->add($form::transformEmptyValuesToNull($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(t('User backend successfully created'));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->title = $this->translate('Authentication');
        $this->renderForm($form, $this->translate('New User Backend'));
    }

    /**
     * Edit a user backend
     */
    public function edituserbackendAction()
    {
        $this->assertPermission('config/access-control/users');
        $backendName = $this->params->getRequired('backend');

        $form = new UserBackendConfigForm();
        $form->setRedirectUrl('config/userbackend');
        $form->setIniConfig(Config::app('authentication'));
        $form->setOnSuccess(function (UserBackendConfigForm $form) use ($backendName) {
            try {
                $form->edit($backendName, $form::transformEmptyValuesToNull($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(sprintf(t('User backend "%s" successfully updated'), $backendName));
                return true;
            }

            return false;
        });

        try {
            $form->load($backendName);
            $form->setResourceConfig(ResourceFactory::getResourceConfigs());
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('User backend "%s" not found'), $backendName));
        }

        $this->view->title = $this->translate('Authentication');
        $this->renderForm($form, $this->translate('Update User Backend'));
    }

    /**
     * Display a confirmation form to remove the backend identified by the 'backend' parameter
     */
    public function removeuserbackendAction()
    {
        $this->assertPermission('config/access-control/users');
        $backendName = $this->params->getRequired('backend');

        $backendForm = new UserBackendConfigForm();
        $backendForm->setIniConfig(Config::app('authentication'));
        $form = new ConfirmRemovalForm();
        $form->setRedirectUrl('config/userbackend');
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($backendName, $backendForm) {
            try {
                $backendForm->delete($backendName);
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($backendForm->save()) {
                Notification::success(sprintf(t('User backend "%s" successfully removed'), $backendName));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->title = $this->translate('Authentication');
        $this->renderForm($form, $this->translate('Remove User Backend'));
    }

    /**
     * Display all available resources and a link to create a new one and to remove existing ones
     */
    public function resourceAction()
    {
        $this->assertPermission('config/resources');
        $this->view->resources = Config::app('resources', true)->getConfigObject()
            ->setKeyColumn('name')
            ->select()
            ->order('name');
        $this->view->title = $this->translate('Resources');
        $this->createApplicationTabs()->activate('resource');
    }

    /**
     * Display a form to create a new resource
     */
    public function createresourceAction()
    {
        $this->assertPermission('config/resources');
        $this->getTabs()->add('resources/new', array(
            'label' => $this->translate('New Resource'),
            'url'   => Url::fromRequest()
        ))->activate('resources/new');
        $form = new ResourceConfigForm();
        $form->addDescription($this->translate('Resources are entities that provide data to Icinga Web 2.'));
        $form->setIniConfig(Config::app('resources'));
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('Resources');
        $this->render('resource/create');
    }

    /**
     * Display a form to edit a existing resource
     */
    public function editresourceAction()
    {
        $this->assertPermission('config/resources');
        $this->getTabs()->add('resources/update', array(
            'label' => $this->translate('Update Resource'),
            'url'   => Url::fromRequest()
        ))->activate('resources/update');
        $form = new ResourceConfigForm();
        $form->setIniConfig(Config::app('resources'));
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('Resources');
        $this->render('resource/modify');
    }

    /**
     * Display a confirmation form to remove a resource
     */
    public function removeresourceAction()
    {
        $this->assertPermission('config/resources');
        $this->getTabs()->add('resources/remove', array(
            'label' => $this->translate('Remove Resource'),
            'url'   => Url::fromRequest()
        ))->activate('resources/remove');
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
        $form->setRedirectUrl('config/resource');
        $form->handleRequest();

        // Check if selected resource is currently used for authentication
        $resource = $this->getRequest()->getQuery('resource');
        $authConfig = Config::app('authentication');
        foreach ($authConfig as $backendName => $config) {
            if ($config->get('resource') === $resource) {
                $form->warning(sprintf(
                    $this->translate(
                        'The resource "%s" is currently utilized for authentication by user backend "%s".'
                        . ' Removing the resource can result in noone being able to log in any longer.'
                    ),
                    $resource,
                    $backendName
                ));
            }
        }

        // Check if selected resource is currently used as user preferences backend
        if (Config::app()->get('global', 'config_resource') === $resource) {
            $form->warning(sprintf(
                $this->translate(
                    'The resource "%s" is currently utilized to store user preferences. Removing the'
                    . ' resource causes all current user preferences not being available any longer.'
                ),
                $resource
            ));
        }

        $this->view->form = $form;
        $this->view->title = $this->translate('Resources');
        $this->render('resource/remove');
    }
}
