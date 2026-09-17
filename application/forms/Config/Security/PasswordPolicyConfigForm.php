<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\Security;

use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Authentication\PasswordPolicyHelper;
use Icinga\Web\Form\ConfigForm;
use ipl\Html\Contract\Form;
use ipl\Html\FormElement\SelectElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Web\Common\FormUid;
use Throwable;

/**
 * Configuration form for password policy selection
 */
class PasswordPolicyConfigForm extends ConfigForm
{
    use FormUid;

    /**
     * Whether the available policies and the selected one could be loaded
     *
     * If not, the Store button is disabled.
     *
     * @var bool
     */
    protected bool $policiesLoadable = true;

    /**
     * Create a new PasswordPolicyConfigForm
     *
     * @param Config $config The config to load the configured policy from and to store the selection in
     */
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
            // Nothing that could fail validation was added before assembly stopped, so the
            // form would validate and store, reporting success while nothing was configured.
            // ON_VALIDATE is emitted after the elements have been validated, making it the
            // last point that can reject the submit. Disabling the button only prevents
            // submitting the form via the browser.
            $this->on(Form::ON_VALIDATE, fn() => $this->isValid = false);

            return;
        }

        $this->addElement('select', $elementName, [
            'class'        => 'autosubmit',
            'description'  => $this->translate('Enforce password requirements for new passwords'),
            'label'        => $this->translate('Password Policy'),
            'multiOptions' => $policies,
            'required'     => true,
            'value'        => $defaultPolicy,
        ]);

        $selectedPolicy = $this->getPopulatedValue($elementName, $defaultPolicy);

        if (! is_string($selectedPolicy)) {
            // Keep malformed saved values visible as an invalid selection until an
            // administrator explicitly chooses a replacement. The empty value of the
            // required select also rejects malformed requests without storing a
            // fallback policy.
            /** @var SelectElement $element */
            $element = $this->getElement($elementName);
            $element
                ->setOptions(['' => $this->translate('Invalid password policy')] + $policies)
                ->setDisabledOptions([''])
                ->setValue(null);

            $this->logAndShowError($this->translate('The password policy must be a string'), '{error}');
            $this->policiesLoadable = false;

            return;
        }

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

            $this->logAndShowError($e, $this->translate('Could not load the password policy: {error}'));
            $this->policiesLoadable = false;

            return;
        }

        PasswordPolicyHelper::addDescription($this, $policy);

        // Surface a load error if the saved policy is unavailable, e.g. because
        // its providing module was disabled. The result is intentionally discarded.
        try {
            PasswordPolicyHook::loadConfigured($this->config);
        } catch (Throwable $e) {
            $this->logAndShowError($e, $this->translate('Could not load the configured password policy: {error}'));
        }
    }

    protected function addRequiredElements(): void
    {
        parent::addRequiredElements();

        if (! $this->policiesLoadable) {
            /** @var SubmitElement $submitButton Added by the parent implementation */
            $submitButton = $this->getSubmitButton();
            $submitButton->setAttribute('disabled', true);
        }
    }
}
