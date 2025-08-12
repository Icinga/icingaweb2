<?php

namespace Icinga\Forms\Config\General;

use Icinga\Web\Form;

class PasswordPolicyConfigForm extends Form
{
    public function init()
    {
        $this->setName('form_config_general_password_policy');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'global_password_policy',
            array(
                'label' => $this->translate('Password Policy'),
                'value' => true,
                'description' => $this->translate(
                    'Enforce strong password requirements for new passwords'
                ),
            )
        );
        return $this;
    }
}
