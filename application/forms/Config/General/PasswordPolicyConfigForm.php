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
use ipl\Html\Contract\Form;
use ipl\Html\FormElement\SelectElement;
use ipl\Web\Common\FormUid;
use Throwable;

/**
 * Configuration form for password policy selection
 */
class PasswordPolicyConfigForm extends ConfigForm
{
    use FormUid;

    protected bool $policiesLoadable = true;

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
            $this->policiesLoadable = false;
            $this->on(Form::ON_VALIDATE, fn() => $this->isValid = false);

            return;
        }

        $this->addElement('select', $elementName, [
            'class'        => 'autosubmit',
            'description'  => $this->translate('Enforce password requirements for new passwords'),
            'label'        => $this->translate('Password Policy'),
            'multiOptions' => $policies,
            'value'        => $defaultPolicy,
        ]);

        $selectedPolicy = $this->getPopulatedValue($elementName, $defaultPolicy);

        try {
            $policy = PasswordPolicyHook::fromCanonicalName($selectedPolicy);
        } catch (Throwable $e) {
            // Offer the unavailable policy as a disabled option, so that the browser does
            // not show the first available one as current and storing does not silently
            // replace the configuration. Disabled options fail the select's own
            // validation, which keeps the form unstorable until another policy is chosen.
            $unknownPolicy = [$selectedPolicy => sprintf($this->translate('%s (unknown)'), $selectedPolicy)];

            /** @var SelectElement $element */
            $element = $this->getElement($elementName);
            $element
                ->setOptions($unknownPolicy + $policies)
                ->setDisabledOptions([$selectedPolicy]);

            Logger::error("%s\n%s", $e, IcingaException::getConfidentialTraceAsString($e));
            PasswordPolicyHelper::addError($this, true);
            $this->policiesLoadable = false;

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

    protected function addRequiredElements(): void
    {
        parent::addRequiredElements();

        if (! $this->policiesLoadable) {
            $this->getSubmitButton()->setAttribute('disabled', true);
        }
    }
}
