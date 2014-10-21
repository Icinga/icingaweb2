<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Notification;
use Icinga\Data\ResourceFactory;
use Icinga\Form\ConfirmRemovalForm;
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Module\Monitoring\Form\Config\BackendConfigForm;
use Icinga\Module\Monitoring\Form\Config\InstanceConfigForm;
use Icinga\Module\Monitoring\Form\Config\SecurityConfigForm;

/**
 * Configuration controller for editing monitoring resources
 */
class Monitoring_ConfigController extends ModuleActionController
{
    /**
     * Display a list of available backends and instances
     */
    public function indexAction()
    {
        $this->view->backendsConfig = $this->Config('backends');
        $this->view->instancesConfig = $this->Config('instances');
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backends');
    }

    /**
     * Display a form to modify the backend identified by the 'backend' parameter of the request
     */
    public function editbackendAction()
    {
        $form = new BackendConfigForm();
        $form->setIniConfig($this->Config('backends'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a form to create a new backend
     */
    public function createbackendAction()
    {
        $form = new BackendConfigForm();
        $form->setIniConfig($this->Config('backends'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a confirmation form to remove the backend identified by the 'backend' parameter
     */
    public function removebackendAction()
    {
        $config = $this->Config('backends');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($request) use ($config) {
                $backendName = $request->getQuery('backend');
                $configForm = new BackendConfigForm();
                $configForm->setIniConfig($config);

                try {
                    $configForm->remove($backendName);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(mt('monitoring', 'Backend "%s" successfully removed.'), $backendName));
                } else {
                    return false;
                }
            }
        ));
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a confirmation form to remove the instance identified by the 'instance' parameter
     */
    public function removeinstanceAction()
    {
        $config = $this->Config('instances');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($request) use ($config) {
                $instanceName = $request->getQuery('instance');
                $configForm = new InstanceConfigForm();
                $configForm->setIniConfig($config);

                try {
                    $configForm->remove($instanceName);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(mt('monitoring', 'Instance "%s" successfully removed.'), $instanceName));
                } else {
                    return false;
                }
            }
        ));
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a form to edit the instance identified by the 'instance' parameter of the request
     */
    public function editinstanceAction()
    {
        $form = new InstanceConfigForm();
        $form->setIniConfig($this->Config('instances'));
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a form to create a new instance
     */
    public function createinstanceAction()
    {
        $form = new InstanceConfigForm();
        $form->setIniConfig($this->Config('instances'));
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Display a form to adjust security relevant settings
     */
    public function securityAction()
    {
        $form = new SecurityConfigForm();
        $form->setIniConfig($this->Config());
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('security');
    }
}
