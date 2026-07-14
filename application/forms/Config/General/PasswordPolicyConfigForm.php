<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\General;

use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\Logger;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Exception\IcingaException;
use Icinga\Web\Form\ConfigForm;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use Throwable;

/**
 * Configuration form for password policy selection
 */
class PasswordPolicyConfigForm extends ConfigForm
{
    use FormUid;

    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->setAttribute('name', 'form_password_policy_config');
        $this->applyDefaultElementDecorators();
    }

    protected function assemble(): void
    {
        $this->addElement($this->createUidElement());

        $defaultPolicy = PasswordPolicyHook::DEFAULT_PASSWORD_POLICY;
        $elementName = sprintf('%s__%s', PasswordPolicyHook::CONFIG_SECTION, PasswordPolicyHook::CONFIG_KEY);

        try {
            $policies = iterator_to_array(PasswordPolicyHook::yieldPolicies());
        } catch (Throwable $e) {
            $this->logAndShowError($e, $this->translate('Could not load password policies: {error}'));

            return;
        }

        $this->addElement('select', $elementName, [
            'class'        => 'autosubmit',
            'description'  => $this->translate('Enforce password requirements for new passwords'),
            'label'        => $this->translate('Password Policy'),
            'multiOptions' => $policies,
            'value'        => $defaultPolicy,
        ]);

        try {
            $policy = PasswordPolicyHook::fromCanonicalName($this->getPopulatedValue($elementName, $defaultPolicy));
        } catch (Throwable $e) {
            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));
            PasswordPolicyHelper::addError($this, true);

            return;
        }

        PasswordPolicyHelper::addDescription($this, $policy);

        // Surface a load error if the saved policy is unavailable, e.g. because
        // its providing module was disabled. The result is intentionally discarded.
        try {
            PasswordPolicyHook::loadConfigured($this->config);
        } catch (Throwable) {
            PasswordPolicyHelper::addError($this, true);
        }
    }
}
