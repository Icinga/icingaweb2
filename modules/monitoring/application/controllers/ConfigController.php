<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Forms\Config\BackendConfigForm;
use Icinga\Module\Monitoring\Forms\Config\SecurityConfigForm;
use Icinga\Module\Monitoring\Forms\Config\TransportConfigForm;

/**
 * Configuration controller for editing monitoring resources
 */
class ConfigController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->assertPermission('config/modules');
        parent::init();
    }

    /**
     * Display a list of available backends and command transports
     */
    public function indexAction()
    {
        $this->view->backendsConfig = $this->Config('backends');
        $this->view->transportConfig = $this->Config('commandtransports');
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backends');
    }

    /**
     * Edit a monitoring backend
     */
    public function editbackendAction()
    {
        $backendName = $this->params->getRequired('backend-name');

        $form = new BackendConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Edit Monitoring Backend %s'), $backendName));
        $form->setIniConfig($this->Config('backends'));
        $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        $form->setOnSuccess(function (BackendConfigForm $form) use ($backendName) {
            try {
                $form->edit($backendName, array_map(
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
                Notification::success(sprintf(t('Monitoring backend "%s" successfully updated'), $backendName));
                return true;
            }

            return false;
        });

        try {
            $form->load($backendName);
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('Monitoring backend "%s" not found'), $backendName));
        }

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Create a new monitoring backend
     */
    public function createbackendAction()
    {
        $form = new BackendConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle($this->translate('Create New Monitoring Backend'));
        $form->setIniConfig($this->Config('backends'));

        try {
            $form->setResourceConfig(ResourceFactory::getResourceConfigs());
        } catch (ConfigurationError $e) {
            if ($this->hasPermission('config/application/resources')) {
                Notification::error($e->getMessage());
                $this->redirectNow('config/createresource');
            }

            throw $e; // No permission for resource configuration, show the error
        }

        $form->setOnSuccess(function (BackendConfigForm $form) {
            try {
                $form->add($form::transformEmptyValuesToNull($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(t('Monitoring backend successfully created'));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Display a confirmation form to remove the backend identified by the 'backend' parameter
     */
    public function removebackendAction()
    {
        $backendName = $this->params->getRequired('backend-name');

        $backendForm = new BackendConfigForm();
        $backendForm->setIniConfig($this->Config('backends'));
        $form = new ConfirmRemovalForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Remove Monitoring Backend %s'), $backendName));
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($backendName, $backendForm) {
            try {
                $backendForm->delete($backendName);
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($backendForm->save()) {
                Notification::success(sprintf(t('Monitoring backend "%s" successfully removed'), $backendName));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Remove a command transport
     */
    public function removetransportAction()
    {
        $transportName = $this->params->getRequired('transport');

        $transportForm = new TransportConfigForm();
        $transportForm->setIniConfig($this->Config('commandtransports'));
        $form = new ConfirmRemovalForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Remove Command Transport %s'), $transportName));
        $form->info(
            $this->translate(
                'If you still have any environments or views referring to this transport, '
                . 'you won\'t be able to send commands anymore after deletion.'
            ),
            false
        );
        $form->setOnSuccess(function (ConfirmRemovalForm $form) use ($transportName, $transportForm) {
            try {
                $transportForm->delete($transportName);
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($transportForm->save()) {
                Notification::success(sprintf(t('Command transport "%s" successfully removed'), $transportName));
                return true;
            }

            return false;
        });
        $form->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Edit a command transport
     */
    public function edittransportAction()
    {
        $transportName = $this->params->getRequired('transport');

        $form = new TransportConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle(sprintf($this->translate('Edit Command Transport %s'), $transportName));
        $form->setIniConfig($this->Config('commandtransports'));
        $form->setInstanceNames(
            Backend::createBackend()->select()->from('instance', array('instance_name'))->fetchColumn()
        );
        $form->setOnSuccess(function (TransportConfigForm $form) use ($transportName) {
            try {
                $form->edit($transportName, array_map(
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
                Notification::success(sprintf(t('Command transport "%s" successfully updated'), $transportName));
                return true;
            }

            return false;
        });

        try {
            $form->load($transportName);
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('Command transport "%s" not found'), $transportName));
        }

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Create a new command transport
     */
    public function createtransportAction()
    {
        $form = new TransportConfigForm();
        $form->setRedirectUrl('monitoring/config');
        $form->setTitle($this->translate('Create New Command Transport'));
        $form->setIniConfig($this->Config('commandtransports'));
        $form->setInstanceNames(
            Backend::createBackend()->select()->from('instance', array('instance_name'))->fetchColumn()
        );
        $form->setOnSuccess(function (TransportConfigForm $form) {
            try {
                $form->add($form::transformEmptyValuesToNull($form->getValues()));
            } catch (Exception $e) {
                $form->error($e->getMessage());
                return false;
            }

            if ($form->save()) {
                Notification::success(t('Command transport successfully created'));
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
