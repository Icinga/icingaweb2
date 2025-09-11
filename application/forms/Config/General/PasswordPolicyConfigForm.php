<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Hook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
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
        $passwordPolicies = [];
        foreach (Hook::all('passwordpolicy') as $class => $policy) {
            $passwordPolicies[$class] = $policy->getName();
        }
        asort($passwordPolicies);

        $this->addElement(
            'select',
            'global_password_policy',
            [
                'description'  => $this->translate('Enforce password requirements for new passwords'),
                'label'        => $this->translate('Password Policy'),
                'value'        => AnyPasswordPolicy::class,
                'multiOptions' => $passwordPolicies
            ]
        );

        return $this;
    }
}
