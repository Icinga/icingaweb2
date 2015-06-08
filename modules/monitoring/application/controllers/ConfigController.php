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
     * Display a confirmation form to remove the instance identified by the 'instance' parameter
     */
    public function removeinstanceAction()
    {
        $config = $this->Config('instances');
        $form = new ConfirmRemovalForm(array(
            'onSuccess' => function ($form) use ($config) {
                $instanceName = $form->getRequest()->getQuery('instance');
                $configForm = new InstanceConfigForm();
                $configForm->setIniConfig($config);

                try {
                    $configForm->remove($instanceName);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return;
                }

                if ($configForm->save()) {
                    Notification::success(sprintf(
                        $this->translate('Instance "%s" successfully removed.'),
                        $instanceName
                    ));
                } else {
                    return false;
                }
            }
        ));
        $form->setTitle($this->translate('Remove Existing Instance'));
        $form->addDescription($this->translate(
            'If you have still any environments or views referring to this instance, '
            . 'you won\'t be able to send commands anymore after deletion.'
        ));
        $form->addElement(
            'note',
            'question',
            array(
                'value'         => $this->translate('Are you sure you want to remove this instance?'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'p'))
                )
            )
        );
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
        $form->setTitle($this->translate('Edit Existing Instance'));
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
        $form->setTitle($this->translate('Add New Instance'));
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
