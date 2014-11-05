<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config;

use Icinga\Web\Request;
use Icinga\Web\Notification;
use Icinga\Form\ConfigForm;

/**
 * Form for modifying security relevant settings
 */
class SecurityConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_security');
        $this->setSubmitLabel(mt('monitoring', 'Save Changes'));
    }

    /**
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        $this->config->security = $this->getValues();

        if ($this->save()) {
            Notification::success(mt('monitoring', 'New security configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * @see Form::onRequest()
     */
    public function onRequest(Request $request)
    {
        if (isset($this->config->security)) {
            $this->populate($this->config->security->toArray());
        }
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'protected_customvars',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Protected Custom Variables'),
                'description'   => mt('monitoring',
                    'Comma separated case insensitive list of protected custom variables.'
                    . ' Use * as a placeholder for zero or more wildcard characters.'
                    . ' Existance of those custom variables will be shown, but their values will be masked.'
                )
            )
        );
    }
}
