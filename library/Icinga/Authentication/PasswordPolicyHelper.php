<?php
/* Icinga Web 2 | (c) 2025 Icinga Development Team | GPLv2+ */

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
    /*
     * Default class for password policy
     */
    const DEFAULT_PASSWORD_POLICY = AnyPasswordPolicy::class;

    /**
     * Configuration section for password policy in the configuration file
     */
    const CONFIG_SECTION = 'global';

    /**
     * Configuration key for password policy in the configuration file
     */
    const CONFIG_KEY = 'password_policy';

    /**
     * Load the configured password policy, fall back to a warning if the policy configuration is invalid.
     * On success, attaches the policy validator to the given new-password form element.
     */
    public static function applyPasswordPolicy(
        Form $form,
        string $newPasswordElementName,
        ?string $oldPasswordElementName = null
    ) {
        if ($oldPasswordElementName !== null && $form->getElement($oldPasswordElementName) === null) {
            throw new LogicException();
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
                    'value' => t(
                        'There was a problem loading the configured password policy. '
                        . 'Please contact your administrator.'
                    ),
                ]
            );
        }
    }
/**
 * Create and validate a password policy instance from the given class name and ensure it implements the
 *  password policy interface.
 */
    public static function createPolicy(string $passwordPolicyClass): PasswordPolicy
    {
        if ($passwordPolicyClass === static::DEFAULT_PASSWORD_POLICY) {
            return new $passwordPolicyClass;
        }

        if (! class_exists($passwordPolicyClass)) {
            throw new ConfigurationError(t('Password policy class %s does not exist'), $passwordPolicyClass);
        }

        $passwordPolicy = new $passwordPolicyClass();

        if (! $passwordPolicy instanceof PasswordPolicy) {
            throw new ConfigurationError(
                t('Password policy %s is not an instance of %s'),
                $passwordPolicyClass,
                PasswordPolicy::class
            );
        }

        return $passwordPolicy;
    }

    public static function addPasswordPolicyDescription(Form $form, PasswordPolicy $passwordPolicy): void
    {
        $description = $passwordPolicy->getDescription();

        if ($description !== null) {
            $form->addDescription($description);
        }
    }
}
