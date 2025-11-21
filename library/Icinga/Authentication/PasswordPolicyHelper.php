<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form;

/*
 * Helper for loading and applying the configured password policy and
 * falls back to a warning if the policy configuration is invalid.
 *
 */
class PasswordPolicyHelper
{
    /*
     * Default class for password policy
     */
    const DEFAULT_PASSWORD_POLICY = AnyPasswordPolicy::class;

    /*
     * Configuration section for password policy in the configuration file
     */
    const CONFIG_SECTION = 'global';

    /*
     * Configuration key for password policy in the configuration file
     */
    const CONFIG_KEY = 'password_policy';

    public static function applyPasswordPolicy(Form $form, string $element)
    {
        try {
            $passwordPolicyClass = Config::app()->get(
                'global',
                'password_policy',
                static::DEFAULT_PASSWORD_POLICY
            );

            $passwordPolicy = static::createPolicy($passwordPolicyClass);
            $form->getElement($element)->addValidator(new PasswordPolicyValidator($passwordPolicy));
            static::addPasswordPolicyDescription($form, $passwordPolicy);
        } catch (ConfigurationError $e) {
            Logger::error($e);

            $form->addElement(
                'note',
                'error message',
                [
                    'escape'     => false,
                    'decorators' => ['ViewHelper'],
                    'value'      => sprintf(
                        '<div class="error-message-password-policy">%s
                                    <div>%s</div>
                                </div>',
                        $form->getView()->icon('warning-empty'),
                        t('There was a problem loading the configured password policy.' .
                        'Please contact your administrator.')
                    ),
                ]
            );
        }
    }

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
            throw new ConfigurationError(t('Password policy is not an instance of %s'), PasswordPolicy::class);
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
