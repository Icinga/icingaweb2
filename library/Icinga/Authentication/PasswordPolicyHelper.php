<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form;
use ipl\I18n\Translation;
use LogicException;

class PasswordPolicyHelper
{
    use Translation;

    /** @var class-string<PasswordPolicy> Default password policy class */
    const DEFAULT_PASSWORD_POLICY = AnyPasswordPolicy::class;

    /** @var string INI configuration section for password policy */
    const CONFIG_SECTION = 'global';

    /** @var string INI configuration key for password policy */
    const CONFIG_KEY = 'password_policy';

    /**
     * Load the configured password policy, fall back to a warning if the policy configuration is invalid.
     * On success, attaches the policy validator to the given new-password form element.
     *
     * @param Form $form
     * @param string $newPasswordElementName
     * @param string|null $oldPasswordElementName Optional name of the old password form element for comparison
     *
     * @return void
     *
     * @throws LogicException If the old password element is specified but does not exist in the form
     * @throws ConfigurationError If the password policy class is misconfigured
     */
 
    public static function applyPasswordPolicy(
        Form $form,
        string $newPasswordElementName,
        ?string $oldPasswordElementName = null
    ) {
        if ($oldPasswordElementName !== null && $form->getElement($oldPasswordElementName) === null) {
            throw new LogicException(sprintf(
                $this->translate('Form element "%s" was specified but does not exist in the form'),
                $oldPasswordElementName
            ));
        }

        try {
            $passwordPolicyClass = Config::app()->get(
                'global',
                'password_policy',
                static::DEFAULT_PASSWORD_POLICY
            );

            $passwordPolicy = static::createPolicy($passwordPolicyClass);
            // getElement() may return null if the element does not exist, causing this call to fail.
            $form->getElement($newPasswordElementName)->addValidator(
                new PasswordPolicyValidator($passwordPolicy, $oldPasswordElementName)
            );
            static::addPasswordPolicyDescription($form, $passwordPolicy);
        } catch (ConfigurationError $e) {
            Logger::error($e);

            $form->addElement(
                'note',
                'bogus',
                [
                    'decorators' => [
                        'ViewHelper',
                        [['HtmlTag#text' => 'HtmlTag'], ['tag' => 'div']],
                        [
                            ['HtmlTag#i' => 'HtmlTag'],
                            [
                                'tag'       => 'i',
                                'class'     => 'form-notification-icon icon fa fa-circle-exclamation',
                                'placement' => 'prepend',
                            ],
                        ],
                        [['HtmlTag#div' => 'HtmlTag'], ['tag' => 'div', 'class' => 'form-notifications error']],
                    ],
                    'value' => $this->translate(
                        'There was a problem loading the configured password policy. '
                        . 'Please contact your administrator.'
                    ),
                ]
            );
        }
    }

    /**
     * Create a {@link PasswordPolicy} instance from the given class name.
     *
     * @param class-string<PasswordPolicy> $passwordPolicyClass
     *
     * @return PasswordPolicy
     *
     * @throws ConfigurationError If class does not exist or does not implement {@link PasswordPolicy}
     */
    public static function createPolicy(string $passwordPolicyClass): PasswordPolicy
    {
        if ($passwordPolicyClass === static::DEFAULT_PASSWORD_POLICY) {
            return new $passwordPolicyClass;
        }

        if (! class_exists($passwordPolicyClass)) {
            throw new ConfigurationError(
                $this->translate('Password policy class %s does not exist'), 
                $passwordPolicyClass
            );
        }

        $passwordPolicy = new $passwordPolicyClass();

        if (! $passwordPolicy instanceof PasswordPolicy) {
            throw new ConfigurationError(
                $this->translate('Password policy %s is not an instance of %s'),
                $passwordPolicyClass,
                PasswordPolicy::class
            );
        }

        return $passwordPolicy;
    }

    /**
     * Retrieve the description from the given password policy and add it to the form for display to the user.
     *
     * @param Form $form
     * @param PasswordPolicy $passwordPolicy
     * @return void
     */
    public static function addPasswordPolicyDescription(Form $form, PasswordPolicy $passwordPolicy): void
    {
        $description = $passwordPolicy->getDescription();

        if ($description !== null) {
            $form->addDescription($description);
        }
    }
}
