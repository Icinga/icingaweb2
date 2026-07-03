<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\General;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\Logger;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form;
use Throwable;

/**
 * Configuration form for password policy selection
 *
 * This form is not used directly but as subform for the {@see GeneralConfigForm}.
 */
class PasswordPolicyConfigForm extends Form
{
    /**
     * @param Config $config The config to load the configured policy from
     */
    public function __construct(protected Config $config)
    {
        parent::__construct();
    }

    public function init(): void
    {
        $this->setName('form_config_general_password_policy');
    }

    public function createElements(array $formData): static
    {
        $defaultPolicy = PasswordPolicyHook::DEFAULT_PASSWORD_POLICY;
        $elementName = sprintf('%s_%s', PasswordPolicyHook::CONFIG_SECTION, PasswordPolicyHook::CONFIG_KEY);
        $this->addElement('select', $elementName, [
            'description'  => $this->translate('Enforce password requirements for new passwords'),
            'label'        => $this->translate('Password Policy'),
            'value'        => $defaultPolicy,
            'multiOptions' => iterator_to_array(PasswordPolicyHook::yieldPolicies()),
            'autosubmit'   => true,
        ]);

        try {
            $policy = PasswordPolicyHook::fromCanonicalName($formData[$elementName] ?? $defaultPolicy);
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));
            PasswordPolicyHelper::addError($this, true);

            return $this;
        }

        PasswordPolicyHelper::addDescription($this, $policy);

        // Surface a load error if the saved policy is unavailable, e.g. because
        // its providing module was disabled. The result is intentionally discarded.
        try {
            PasswordPolicyHook::loadConfigured($this->config);
        } catch (Throwable) {
            PasswordPolicyHelper::addError($this, true);
        }

        return $this;
    }
}
