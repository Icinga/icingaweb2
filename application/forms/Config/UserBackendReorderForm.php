<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use Icinga\Application\Config;
use Icinga\Forms\ConfigForm;
use Icinga\Exception\NotFoundError;
use Icinga\Web\Notification;

class UserBackendReorderForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_reorder_authbackend');
        $this->setViewScript('form/reorder-authbackend.phtml');
    }

    /**
     * Return the ordered backend names
     *
     * @return  array
     */
    public function getBackendOrder()
    {
        return $this->config->keys();
    }

    /**
     * Return the ordered backend configuration
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        // This adds just a dummy element to be able to utilize Form::getValue as part of onSuccess()
        $this->addElement('hidden', 'backend_newpos');
    }

    /**
     * Update the user backend order and save the configuration
     */
    public function onSuccess()
    {
        $newPosData = $this->getValue('backend_newpos');
        if ($newPosData) {
            $configForm = $this->getConfigForm();
            list($backendName, $position) = explode('|', $newPosData, 2);

            try {
                if ($configForm->move($backendName, $position)->save()) {
                    Notification::success($this->translate('Authentication order updated'));
                } else {
                    return false;
                }
            } catch (NotFoundError $_) {
                 Notification::error(sprintf($this->translate('User backend "%s" not found'), $backendName));
            }
        }
    }

    /**
     * Return the config form for user backends
     *
     * @return  ConfigForm
     */
    protected function getConfigForm()
    {
        $form = new UserBackendConfigForm();
        $form->setIniConfig($this->config);
        return $form;
    }
}
