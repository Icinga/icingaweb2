<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Web\Form;

/**
 * Configuration form for password policy selection
 *
 * This form is not used directly but as subform for the {@link GeneralConfigForm}.
 */
class PasswordPolicyConfigForm extends Form
{
    public function init(): void
    {
        $this->setName('form_config_general_password_policy');
    }

    public function createElements(array $formData): static
    {
        $this->addElement(
            'select',
            sprintf('%s_%s', PasswordPolicyHelper::CONFIG_SECTION, PasswordPolicyHelper::CONFIG_KEY),
            [
                'description'  => $this->translate('Enforce password requirements for new passwords'),
                'label'        => $this->translate('Password Policy'),
                'value'        => PasswordPolicyHelper::DEFAULT_PASSWORD_POLICY,
                'multiOptions' => PasswordPolicyHook::all()
            ]
        );

        return $this;
    }
}
