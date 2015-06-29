<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Web\Notification;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Web\Controller;
use Icinga\Module\Monitoring\Forms\Config\BackendConfigForm;
use Icinga\Module\Monitoring\Forms\Config\InstanceConfigForm;
use Icinga\Module\Monitoring\Forms\Config\SecurityConfigForm;

/**
 * Configuration controller for editing monitoring resources
 */
class Monitoring_ConfigController extends Controller
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
        $form->setTitle($this->translate('Edit Existing Backend'));
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
        $form->setTitle($this->translate('Add New Backend'));
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
            'onSuccess' => function ($form) use ($config) {
                $backendName = $form->getRequest()->getQuery('backend');
                $configForm = new BackendConfigForm();
                $configForm->setIniConfig($config);

                try {
                    $configForm->remove($backendName);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(
                        $this->translate('Backend "%s" successfully removed.'),
                        $backendName
                    ));
                } else {
                    return false;
                }
            }
        ));
        $form->setTitle($this->translate('Remove Existing Backend'));
        $form->setRedirectUrl('monitoring/config');
        $form->handleRequest();

        $this->view->form = $form;
    }

    /**
     * Remove a monitoring instance
     */
    public function removeinstanceAction()
    {
        $instanceName = $this->params->getRequired('instance');

        $instanceForm = new InstanceConfigForm();
        $instanceForm->setIniConfig($this->Config('instances'));
        $form = new ConfirmRemovalForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Remove Monitoring Instance %s'), $instanceName));
        $form->addDescription($this->translate(
            'If you have still any environments or views referring to this instance, '
            . 'you won\'t be able to send commands anymore after deletion.'
        ));
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($instanceName, $instanceForm) {
            try {
                $instanceForm->delete($instanceName);
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($instanceForm->save()) {
                Notification::success(sprintf(t('Monitoring instance "%s" successfully removed'), $instanceName));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Edit a monitoring instance
     */
    public function editinstanceAction()
    {
        $instanceName = $this->params->getRequired('instance');

        $form = new InstanceConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Edit Monitoring Instance %s'), $instanceName));
        $form->setIniConfig($this->Config('instances'));
        $form->setOnSuccess(function (InstanceConfigForm $form) use ($instanceName) {
            try {
                $form->edit($instanceName, array_map(
                    function ($v) {
                        return $v !== '' ? $v : null;
                    },
                    $form->getValues()
                ));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(sprintf(t('Monitoring instance "%s" successfully updated'), $instanceName));
                return true;
            }

            return false;
        });

        try {
            $form->load($instanceName);
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('Monitoring instance "%s" not found'), $instanceName));
        }

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Create a new monitoring instance
     */
    public function createinstanceAction()
    {
        $form = new InstanceConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle($this->translate('Create New Monitoring Instance'));
        $form->setIniConfig($this->Config('instances'));
        $form->setOnSuccess(function (InstanceConfigForm $form) {
            try {
                $form->add(array_filter($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(t('Monitoring instance successfully created'));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
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
