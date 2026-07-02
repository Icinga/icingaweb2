<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\General;

use Exception;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Web\Form;
use ipl\Web\Common\CalloutType;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\Callout;

/**
 * Configuration form for password policy selection
 *
 * This form is not used directly but as subform for the {@see GeneralConfigForm}.
 */
class PasswordPolicyConfigForm extends Form
{
    public function init(): void
    {
        $this->setName('form_config_general_password_policy');
    }

    public function createElements(array $formData): static
    {
        $options = [];
        foreach (PasswordPolicyHook::all() as $policy) {
            $options[$policy->getName()] = $policy->getDisplayName();
        }

        $this->addElement(
            'select',
            sprintf('%s_%s', PasswordPolicyHelper::CONFIG_SECTION, PasswordPolicyHelper::CONFIG_KEY),
            [
                'description'  => $this->translate('Enforce password requirements for new passwords'),
                'label'        => $this->translate('Password Policy'),
                'value'        => PasswordPolicyHelper::DEFAULT_PASSWORD_POLICY,
                'multiOptions' => $options,
            ]
        );

        try {
            PasswordPolicyHelper::create();
        } catch (Exception $e) {
            $this->addElement(
                'note',
                'bogus',
                [
                    'decorators' => ['ViewHelper'],
                    'value' => (new DisplayFormElement(new Callout(
                        CalloutType::Error,
                        t('There was a problem loading the configured password policy.'),
                    )))->render(),
                ]
            );
        }

        return $this;
    }
}
