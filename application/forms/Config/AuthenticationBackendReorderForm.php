<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use InvalidArgumentException;
use Icinga\Web\Request;
use Icinga\Web\Notification;
use Icinga\Form\ConfigForm;

class AuthenticationBackendReorderForm extends ConfigForm
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
     * Update the authentication backend order and save the configuration
     *
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        $formData = $this->getRequestData($request);
        if (isset($formData['backend_newpos'])) {
            $configForm = $this->getConfigForm();
            list($backendName, $position) = explode('|', $formData['backend_newpos'], 2);

            try {
                if ($configForm->move($backendName, $position)->save()) {
                    Notification::success(t('Authentication order updated!'));
                } else {
                    return false;
                }
            } catch (InvalidArgumentException $e) {
                Notification::error($e->getMessage());
            }
        }
    }

    /**
     * Return the config form for authentication backends
     *
     * @return  ConfigForm
     */
    protected function getConfigForm()
    {
        $form = new AuthenticationBackendConfigForm();
        $form->setIniConfig($this->config);
        return $form;
    }
}
